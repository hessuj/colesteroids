<?php
/**
 * Colesteroids OG+ – yhteiset apufunktiot tulostaululle ja Facebook-jaolle.
 * MIT License, (c) 2026 Hessu Järvinen
 */
declare(strict_types=1);

const DATA_DIR    = __DIR__ . '/data';
const SHARES_FILE = DATA_DIR . '/shares.json';
const SHARE_IMG_DIR = DATA_DIR . '/img';
const MAX_SHARES  = 5000;

function ensure_data_dir(): bool {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0750, true) && !is_dir(DATA_DIR)) return false;
    $ht = DATA_DIR . '/.htaccess';
    if (!file_exists($ht)) {
        file_put_contents($ht, "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
    return true;
}

/**
 * Tallentaa yhden pelin tuloksen jakoa varten ja palauttaa jakotunnisteen.
 * Jakosivu näyttää aina palvelimen tarkistamat arvot, ei selaimen lähettämiä.
 */
function add_share(string $nick, int $score, int $level, int $eaten, int $seconds): ?string {
    if (!ensure_data_dir()) return null;
    $fh = fopen(SHARES_FILE, 'c+');
    if (!$fh) return null;
    flock($fh, LOCK_EX);
    rewind($fh);
    $raw = stream_get_contents($fh);
    $list = $raw ? json_decode($raw, true) : [];
    if (!is_array($list)) $list = [];
    $sid = bin2hex(random_bytes(6));
    $list[$sid] = ['nick' => $nick, 'score' => $score, 'level' => $level, 'eaten' => $eaten,
                   'seconds' => $seconds, 'at' => time()];
    if (count($list) > MAX_SHARES) $list = array_slice($list, -MAX_SHARES, null, true);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($list, JSON_UNESCAPED_UNICODE));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $sid;
}

function get_share(string $sid): ?array {
    if (!preg_match('/^[a-f0-9]{12}$/', $sid) || !file_exists(SHARES_FILE)) return null;
    $fh = fopen(SHARES_FILE, 'r');
    if (!$fh) return null;
    flock($fh, LOCK_SH);
    $list = json_decode((string) stream_get_contents($fh), true);
    flock($fh, LOCK_UN);
    fclose($fh);
    return is_array($list) && isset($list[$sid]) ? $list[$sid] : null;
}

/** Pelin julkinen osoite tämän tiedoston sijainnin perusteella, esim. https://hessujarvinen.com/softa/colesteroids/ */
function base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = preg_replace('/[^a-zA-Z0-9.:\-]/', '', $_SERVER['HTTP_HOST'] ?? 'hessujarvinen.com');
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir . '/';
}

function fmt_points(int $n): string {
    return number_format($n, 0, ',', ' ');
}

function fmt_time(int $sec): string {
    return intdiv($sec, 60) . ':' . str_pad((string) ($sec % 60), 2, '0', STR_PAD_LEFT);
}

/** Hauska otsikko tuloksen suuruuden mukaan. Nimimerkkiä ei taivuteta, jotta suomi pysyy oikeana. */
function share_title(array $s): string {
    $n = $s['nick']; $p = fmt_points($s['score']); $l = $s['level'];
    if ($s['score'] < 500)  return "Sydänosaston tiedote: $n kävi verisuonissa ja toi $p pistettä. Lääkäri määrää uusintakierroksen! 🩺";
    if ($s['score'] < 2000) return "$n pumppasi $p pistettä Colesteroids OG+:ssa! 💗";
    if ($s['score'] < 6000) return "Kolesteroli kauhuissaan: $n teki $p pistettä ja pääsi kenttään $l!";
    if ($l < 20)            return "Hälytys sydänosastolle! $n jyräsi $p pistettä ja kenttään $l 🫀";
    return "Verisuonten legenda $n: $p pistettä ja kenttä $l. Kolesteroli harkitsee alan vaihtoa. 🏆";
}

function share_description(array $s): string {
    return "Söi {$s['eaten']} hyvää lipoproteiinia ja selvisi verisuonissa " . fmt_time($s['seconds'])
        . ". Diagnoosi: " . share_diagnosis($s) . ". Pystytkö parempaan? Pelaa Colesteroids OG+ selaimessa.";
}

/** Leikkimielinen "diagnoosi" tuloksen mukaan jakokuvaan ja -kuvaukseen. */
function share_diagnosis(array $s): string {
    if ($s['score'] < 500)  return 'lisää harjoitusta';
    if ($s['score'] < 2000) return 'lupaava syke';
    if ($s['score'] < 6000) return 'rautainen sydän';
    if ($s['level'] < 20)   return 'kolesterolin painajainen';
    return 'verisuonten legenda';
}
