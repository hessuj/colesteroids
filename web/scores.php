<?php
/**
 * Colesteroids OG+ – yhteinen tulostaulu.
 * GET  scores.php -> {"scores":[...]}  (kymmenen parasta, yksi rivi per nimimerkki)
 * POST scores.php  {nick, score, levels: [{eaten, seconds}, ...], token}
 *   Palvelin laskee pisteet kentittäin samalla kaavalla kuin peli. Vanha muoto
 *   {nick, score, seconds, eaten, token} tulkitaan yhdeksi kentäksi.
 *   201 {"ok":true,"id":"…","improved":true|false,"best":n,"scores":[...]}
 *   409 {"error":"…","code":"nick_taken"}  nimimerkki kuuluu toiselle selaimelle
 *
 * Jokaisesta nimimerkistä säilytetään vain paras tulos (issue #1). Nimimerkki varataan
 * ensimmäisen tallennuksen tehneelle selaimelle: selain lähettää satunnaisen avaimen,
 * josta palvelin tallentaa vain tiivisteen. Samalla nimimerkillä voi parantaa tulosta
 * vain sama selain.
 *
 * Tulokset tallentuvat tiedostoon data/scores.json. Kansio suojataan .htaccessilla.
 * Jokaisesta tallennuksesta luodaan myös jakotunniste (share), jolla share.php
 * näyttää tuloksen Facebook-jaossa.
 *
 * MIT License, (c) 2026 Hessu Järvinen
 */
declare(strict_types=1);
require __DIR__ . '/share_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DATA_FILE = DATA_DIR . '/scores.json';
const TOP       = 10;     // montako näytetään
const MAX_KEEP  = 1000;   // montako nimimerkkiä säilytetään
const MAX_BODY  = 2048;   // tavua

// Samat säännöt kuin pelissä (index.html)
const GAME_TIME         = 60;   // yhden kentän kesto
const POINTS_PER_SECOND = 10;
const MAX_EATEN         = 80;   // syötyjä hyviksiä per kenttä enintään
const MAX_LEVELS        = 500;

/** Vihreän arvo kentässä n (1, 2, 3, ...), sama kuin levelCfg(n).greenPoints pelissä. */
function green_points(int $n): int {
    return 100 + 50 * ($n - 1);
}
const RATE_SECONDS      = 15; // sama IP voi tallentaa korkeintaan näin usein

function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}


/** Nimimerkin vertailuavain: kirjainkoko ja ylimääräiset välilyönnit eivät erota pelaajia. */
function nick_key(string $nick): string {
    $nick = trim(preg_replace('/\s+/u', ' ', $nick) ?? '');
    return function_exists('mb_strtolower') ? mb_strtolower($nick, 'UTF-8') : strtolower($nick);
}

function by_score(array $a, array $b): int {
    return ($b['score'] <=> $a['score']) ?: ($a['at'] <=> $b['at']);
}

/**
 * Yksi rivi per nimimerkki, paras tulos voimassa. Siivoaa myös vanhat,
 * ennen tätä muutosta tallentuneet päällekkäiset rivit.
 */
function dedupe(array $scores): array {
    usort($scores, 'by_score');
    $seen = [];
    $out = [];
    foreach ($scores as $s) {
        if (!is_array($s) || !isset($s['nick'], $s['score'])) continue;
        $key = $s['key'] ?? nick_key((string) $s['nick']);
        if (isset($seen[$key])) {
            // säilytä varaus, vaikka paras tulos olisi tullut vanhalta riviltä
            if (empty($out[$seen[$key]]['tokenHash']) && !empty($s['tokenHash'])) {
                $out[$seen[$key]]['tokenHash'] = $s['tokenHash'];
            }
            continue;
        }
        $s['key'] = $key;
        $seen[$key] = count($out);
        $out[] = $s;
    }
    return $out;
}

function top_list(array $scores): array {
    $out = [];
    foreach (array_slice(dedupe($scores), 0, TOP) as $s) {
        $out[] = [
            'id' => $s['id'], 'nick' => $s['nick'], 'score' => $s['score'],
            'seconds' => $s['seconds'], 'eaten' => $s['eaten'], 'level' => $s['level'] ?? 1, 'at' => $s['at'],
        ];
    }
    return $out;
}

function read_store($fh): array {
    rewind($fh);
    $raw = stream_get_contents($fh);
    $data = $raw ? json_decode($raw, true) : null;
    if (!is_array($data)) $data = [];
    return [
        'scores' => isset($data['scores']) && is_array($data['scores']) ? $data['scores'] : [],
        'rate'   => isset($data['rate']) && is_array($data['rate']) ? $data['rate'] : [],
    ];
}

function clean_nick($nick): string {
    if (!is_string($nick)) return '';
    $nick = preg_replace('/[\p{C}<>]/u', '', $nick) ?? '';
    $nick = trim(preg_replace('/\s+/u', ' ', $nick) ?? '');
    return function_exists('mb_substr') ? mb_substr($nick, 0, 16, 'UTF-8') : substr($nick, 0, 16);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    if (!file_exists(DATA_FILE)) respond(200, ['scores' => []]);
    $fh = fopen(DATA_FILE, 'r');
    if (!$fh) respond(200, ['scores' => []]);
    flock($fh, LOCK_SH);
    $store = read_store($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    respond(200, ['scores' => top_list($store['scores'])]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(405, ['error' => 'Vain GET ja POST ovat sallittuja.']);
}

// --- Uuden tuloksen tarkistus ---
$body = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if ($body === false || strlen($body) > MAX_BODY) respond(413, ['error' => 'Pyyntö on liian suuri.']);
$in = json_decode($body, true);
if (!is_array($in)) respond(400, ['error' => 'Virheellinen pyyntö.']);

$nick   = clean_nick($in['nick'] ?? '');
$token  = $in['token'] ?? '';
$levels = $in['levels'] ?? null;
if (!is_array($levels)) {
    // vanha, kentätön muoto
    $levels = [['eaten' => $in['eaten'] ?? null, 'seconds' => $in['seconds'] ?? null]];
}

if ($nick === '') respond(422, ['error' => 'Nimimerkki puuttuu.']);
if (!is_string($token) || !preg_match('/^[a-f0-9]{32,64}$/', $token)) {
    respond(422, ['error' => 'Selaimen tunniste puuttuu. Lataa sivu uudelleen.']);
}
// Pisteet lasketaan kentittäin: läpäistyt kentät kestävät täydet 60 s, viimeinen voi päättyä kesken
$invalid = ['error' => 'Tulos ei vastaa pelin sääntöjä.'];
$count = count($levels);
if ($count < 1 || $count > MAX_LEVELS || array_keys($levels) !== range(0, $count - 1)) respond(422, $invalid);
$score = 0; $seconds = 0; $eaten = 0;
foreach ($levels as $i => $lv) {
    if (!is_array($lv)) respond(422, $invalid);
    $e = filter_var($lv['eaten'] ?? null, FILTER_VALIDATE_INT);
    $t = filter_var($lv['seconds'] ?? null, FILTER_VALIDATE_INT);
    if ($e === false || $t === false || $e < 0 || $e > MAX_EATEN || $t < 0 || $t > GAME_TIME) respond(422, $invalid);
    if ($i < $count - 1 && $t !== GAME_TIME) respond(422, $invalid);
    $score += $e * green_points($i + 1) + $t * POINTS_PER_SECOND;
    $seconds += $t;
    $eaten += $e;
}
$level = $count;
if (isset($in['score']) && filter_var($in['score'], FILTER_VALIDATE_INT) !== $score) respond(422, $invalid);

// --- Tallennus lukon alla ---
if (!ensure_data_dir()) respond(500, ['error' => 'Tulostaulun tallennuskansiota ei voitu luoda.']);
$fh = fopen(DATA_FILE, 'c+');
if (!$fh) respond(500, ['error' => 'Tulostaulua ei voitu avata.']);
flock($fh, LOCK_EX);
$store = read_store($fh);
$scores = dedupe($store['scores']);

function finish($fh, array $store, array $scores, int $code, array $data): void {
    $store['scores'] = array_slice($scores, 0, MAX_KEEP);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($store, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    respond($code, $data);
}

$now = time();
$key = nick_key($nick);
$tokenHash = hash('sha256', $token);

// Onko nimimerkki jo jonkun toisen selaimen?
$idx = null;
foreach ($scores as $i => $s) {
    if ($s['key'] === $key) { $idx = $i; break; }
}
if ($idx !== null && !empty($scores[$idx]['tokenHash']) && !hash_equals($scores[$idx]['tokenHash'], $tokenHash)) {
    flock($fh, LOCK_UN);
    fclose($fh);
    respond(409, ['error' => 'Nimimerkki on jo toisen pelaajan käytössä. Valitse toinen nimimerkki.', 'code' => 'nick_taken']);
}

// Tallennustahti
$ipKey = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . __FILE__);
foreach ($store['rate'] as $k => $t) {
    if (!is_int($t) || $now - $t > 3600) unset($store['rate'][$k]);
}
if (isset($store['rate'][$ipKey]) && $now - $store['rate'][$ipKey] < RATE_SECONDS) {
    flock($fh, LOCK_UN);
    fclose($fh);
    respond(429, ['error' => 'Odota hetki ennen seuraavaa tallennusta.']);
}
$store['rate'][$ipKey] = $now;

$atMs = (int) round(microtime(true) * 1000);

if ($idx !== null) {
    $old = $scores[$idx];
    $scores[$idx]['tokenHash'] = $tokenHash; // varaa vanha, varaamaton nimimerkki
    if ($score <= $old['score']) {
        // Huonompi tai yhtä hyvä tulos: tulostaulu ei muutu
        finish($fh, $store, $scores, 201, [
            'ok' => true, 'id' => $old['id'], 'improved' => false, 'best' => $old['score'],
            'share' => add_share($nick, $score, $level, $eaten, $seconds),
            'scores' => top_list($scores),
        ]);
    }
    $scores[$idx] = array_merge($scores[$idx], [
        'id' => bin2hex(random_bytes(6)), 'nick' => $nick, 'score' => $score,
        'seconds' => $seconds, 'eaten' => $eaten, 'level' => $level, 'at' => $atMs,
    ]);
    $entryId = $scores[$idx]['id'];
} else {
    $entryId = bin2hex(random_bytes(6));
    $scores[] = [
        'id' => $entryId, 'nick' => $nick, 'key' => $key, 'tokenHash' => $tokenHash,
        'score' => $score, 'seconds' => $seconds, 'eaten' => $eaten, 'level' => $level, 'at' => $atMs,
    ];
}
usort($scores, 'by_score');

finish($fh, $store, $scores, 201, [
    'ok' => true, 'id' => $entryId, 'improved' => true, 'best' => $score,
    'share' => add_share($nick, $score, $level, $eaten, $seconds),
    'scores' => top_list($scores),
]);
