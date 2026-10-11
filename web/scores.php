<?php
/**
 * Colesteroids OG+ – yhteinen tulostaulu.
 * GET  scores.php -> {"scores":[...]}  (kymmenen parasta, yksi rivi per nimimerkki)
 * POST scores.php  {nick, score, seconds, eaten, token}
 *   201 {"ok":true,"id":"…","improved":true|false,"best":n,"scores":[...]}
 *   409 {"error":"…","code":"nick_taken"}  nimimerkki kuuluu toiselle selaimelle
 *
 * Jokaisesta nimimerkistä säilytetään vain paras tulos (issue #1). Nimimerkki varataan
 * ensimmäisen tallennuksen tehneelle selaimelle: selain lähettää satunnaisen avaimen,
 * josta palvelin tallentaa vain tiivisteen. Samalla nimimerkillä voi parantaa tulosta
 * vain sama selain.
 *
 * Tulokset tallentuvat tiedostoon data/scores.json. Kansio suojataan .htaccessilla.
 *
 * MIT License, (c) 2026 Hessu Järvinen
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const DATA_DIR  = __DIR__ . '/data';
const DATA_FILE = DATA_DIR . '/scores.json';
const TOP       = 10;     // montako näytetään
const MAX_KEEP  = 1000;   // montako nimimerkkiä säilytetään
const MAX_BODY  = 2048;   // tavua

// Samat säännöt kuin pelissä (index.html)
const GAME_TIME         = 60;
const HYVIS_POINTS      = 100;
const POINTS_PER_SECOND = 10;
const MAX_EATEN         = 80;
const RATE_SECONDS      = 15; // sama IP voi tallentaa korkeintaan näin usein

function respond(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_data_dir(): void {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true) && !is_dir(DATA_DIR)) {
        respond(500, ['error' => 'Tulostaulun tallennuskansiota ei voitu luoda.']);
    }
    $ht = DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        file_put_contents($ht, "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
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
            'seconds' => $s['seconds'], 'eaten' => $s['eaten'], 'at' => $s['at'],
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

$nick    = clean_nick($in['nick'] ?? '');
$score   = filter_var($in['score'] ?? null, FILTER_VALIDATE_INT);
$seconds = filter_var($in['seconds'] ?? null, FILTER_VALIDATE_INT);
$eaten   = filter_var($in['eaten'] ?? null, FILTER_VALIDATE_INT);
$token   = $in['token'] ?? '';

if ($nick === '') respond(422, ['error' => 'Nimimerkki puuttuu.']);
if (!is_string($token) || !preg_match('/^[a-f0-9]{32,64}$/', $token)) {
    respond(422, ['error' => 'Selaimen tunniste puuttuu. Lataa sivu uudelleen.']);
}
if ($score === false || $seconds === false || $eaten === false
    || $seconds < 0 || $seconds > GAME_TIME
    || $eaten < 0 || $eaten > MAX_EATEN
    || $score !== $eaten * HYVIS_POINTS + $seconds * POINTS_PER_SECOND) {
    respond(422, ['error' => 'Tulos ei vastaa pelin sääntöjä.']);
}

// --- Tallennus lukon alla ---
ensure_data_dir();
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
            'scores' => top_list($scores),
        ]);
    }
    $scores[$idx] = array_merge($scores[$idx], [
        'id' => bin2hex(random_bytes(6)), 'nick' => $nick, 'score' => $score,
        'seconds' => $seconds, 'eaten' => $eaten, 'at' => $atMs,
    ]);
    $entryId = $scores[$idx]['id'];
} else {
    $entryId = bin2hex(random_bytes(6));
    $scores[] = [
        'id' => $entryId, 'nick' => $nick, 'key' => $key, 'tokenHash' => $tokenHash,
        'score' => $score, 'seconds' => $seconds, 'eaten' => $eaten, 'at' => $atMs,
    ];
}
usort($scores, 'by_score');

finish($fh, $store, $scores, 201, [
    'ok' => true, 'id' => $entryId, 'improved' => true, 'best' => $score,
    'scores' => top_list($scores),
]);
