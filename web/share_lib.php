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
function add_share(string $nick, int $score, int $level, int $eaten, int $seconds, string $lang = 'fi'): ?string {
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
                   'seconds' => $seconds, 'lang' => share_lang($lang), 'at' => time()];
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

/** Jaon kieli: tuetut kielet, muuten suomi. */
function share_lang($lang): string {
    return in_array($lang, ['fi', 'en'], true) ? $lang : 'fi';
}

function fmt_points(int $n, string $lang = 'fi'): string {
    return $lang === 'en' ? number_format($n, 0, '.', ',') : number_format($n, 0, ',', ' ');
}

function fmt_time(int $sec): string {
    return intdiv($sec, 60) . ':' . str_pad((string) ($sec % 60), 2, '0', STR_PAD_LEFT);
}

/** Jakosivun ja -kuvan tekstit kielittäin. Nimimerkkiä ei taivuteta, jotta suomi pysyy oikeana. */
const SHARE_TEXT = [
    'fi' => [
        'title' => [
            "Sydänosaston tiedote: {n} kävi verisuonissa ja toi {p} pistettä. Lääkäri määrää uusintakierroksen! 🩺",
            "{n} pumppasi {p} pistettä Colesteroids OG+:ssa! 💗",
            "Kolesteroli kauhuissaan: {n} teki {p} pistettä ja pääsi kenttään {l}!",
            "Hälytys sydänosastolle! {n} jyräsi {p} pistettä ja kenttään {l} 🫀",
            "Verisuonten legenda {n}: {p} pistettä ja kenttä {l}. Kolesteroli harkitsee alan vaihtoa. 🏆",
        ],
        'diagnosis' => ['lisää harjoitusta', 'lupaava syke', 'rautainen sydän', 'kolesterolin painajainen', 'verisuonten legenda'],
        'description' => "Söi {e} hyvää lipoproteiinia ja selvisi verisuonissa {t}. Diagnoosi: {d}. Pystytkö parempaan? Pelaa Colesteroids OG+ selaimessa.",
        'points' => '{p} pistettä',
        'line1' => 'Kenttä {l} · {e} hyvistä syöty',
        'line2' => 'Diagnoosi: {d} · {t}',
        'cta' => 'Pystytkö parempaan? Pelaa selaimessa',
        'alt' => '{n}: {p} pistettä Colesteroids OG+ -pelissä',
        'play' => 'Pelaa Colesteroids OG+',
        'locale' => 'fi_FI',
    ],
    'en' => [
        'title' => [
            "Cardiology ward bulletin: {n} took a quick tour of the arteries and brought back {p} points. Doctor's orders: play again! 🩺",
            "{n} pumped {p} points in Colesteroids OG+! 💗",
            "Cholesterol is terrified: {n} scored {p} points and reached level {l}!",
            "Code red on the cardiology ward! {n} crushed {p} points and reached level {l} 🫀",
            "Artery legend {n}: {p} points and level {l}. Cholesterol is considering a career change. 🏆",
        ],
        'diagnosis' => ['needs more practice', 'promising pulse', 'iron heart', "cholesterol's nightmare", 'artery legend'],
        'description' => "Ate {e} good lipoproteins and survived in the arteries for {t}. Diagnosis: {d}. Can you do better? Play Colesteroids OG+ in your browser.",
        'points' => '{p} points',
        'line1' => 'Level {l} · {e} good ones eaten',
        'line2' => 'Diagnosis: {d} · {t}',
        'cta' => 'Can you do better? Play in your browser',
        'alt' => '{n}: {p} points in Colesteroids OG+',
        'play' => 'Play Colesteroids OG+',
        'locale' => 'en_US',
    ],
];

/** Tuloksen taso 0-4: määrää otsikon ja diagnoosin. */
function share_band(array $s): int {
    if ($s['score'] < 500)  return 0;
    if ($s['score'] < 2000) return 1;
    if ($s['score'] < 6000) return 2;
    if ($s['level'] < 20)   return 3;
    return 4;
}

/** Hakee jaon tekstin sen kielellä ja täyttää paikat {n} nimimerkki, {p} pisteet, {l} kenttä, {e} syödyt, {t} aika, {d} diagnoosi. */
function share_text(array $s, string $key): string {
    $L = SHARE_TEXT[share_lang($s['lang'] ?? 'fi')];
    $text = $L[$key];
    if (is_array($text)) $text = $text[share_band($s)];
    return strtr($text, [
        '{n}' => $s['nick'], '{p}' => fmt_points($s['score'], share_lang($s['lang'] ?? 'fi')), '{l}' => (string) $s['level'],
        '{e}' => (string) $s['eaten'], '{t}' => fmt_time($s['seconds']), '{d}' => share_diagnosis($s),
    ]);
}

function share_title(array $s): string {
    return share_text($s, 'title');
}

function share_description(array $s): string {
    return share_text($s, 'description');
}

/** Leikkimielinen "diagnoosi" tuloksen mukaan jakokuvaan ja -kuvaukseen. */
function share_diagnosis(array $s): string {
    return SHARE_TEXT[share_lang($s['lang'] ?? 'fi')]['diagnosis'][share_band($s)];
}
