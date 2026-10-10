<?php
/**
 * Colesteroids OG+ – yhteinen tulostaulu.
 * GET  scores.php            -> {"scores":[...]}  (kymmenen parasta)
 * POST scores.php  {nick, score, seconds, eaten} -> {"ok":true,"id":"…","scores":[...]}
 *
 * Tulokset tallentuvat tiedostoon data/scores.json. Kansio suojataan .htaccessilla,
 * joten tiedostoa ei voi ladata suoraan selaimella.
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
const MAX_KEEP  = 500;    // montako säilytetään
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

function top_list(array $scores): array {
    usort($scores, function ($a, $b) {
        return ($b['score'] <=> $a['score']) ?: ($a['at'] <=> $b['at']);
    });
    $out = [];
    foreach (array_slice($scores, 0, TOP) as $s) {
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

if ($nick === '') respond(422, ['error' => 'Nimimerkki puuttuu.']);
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

$now = time();
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

$entry = [
    'id' => bin2hex(random_bytes(6)),
    'nick' => $nick,
    'score' => $score,
    'seconds' => $seconds,
    'eaten' => $eaten,
    'at' => (int) round(microtime(true) * 1000),
];
$store['scores'][] = $entry;
usort($store['scores'], function ($a, $b) {
    return ($b['score'] <=> $a['score']) ?: ($a['at'] <=> $b['at']);
});
$store['scores'] = array_slice($store['scores'], 0, MAX_KEEP);

ftruncate($fh, 0);
rewind($fh);
fwrite($fh, json_encode($store, JSON_UNESCAPED_UNICODE));
fflush($fh);
flock($fh, LOCK_UN);
fclose($fh);

respond(201, ['ok' => true, 'id' => $entry['id'], 'scores' => top_list($store['scores'])]);
