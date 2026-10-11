<?php
/**
 * Colesteroids OG+ – jakokuva yhdelle tulokselle (1200×630 PNG).
 * share-image.php?id=<jakotunniste>
 *
 * Piirtää nimimerkin ja pisteet pohjakuvan (og-base.png) päälle. Valmis kuva
 * tallennetaan välimuistiin data/img/-kansioon. Jos PHP:n GD-kirjastoa ei ole,
 * palautetaan pelin yleinen jakokuva.
 *
 * Fontti: Inter (SIL Open Font License 1.1, fonts/OFL.txt).
 * MIT License, (c) 2026 Hessu Järvinen
 */
declare(strict_types=1);
require __DIR__ . '/share_lib.php';

function send_png(string $file): void {
    header('Content-Type: image/png');
    header('Cache-Control: public, max-age=31536000, immutable');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

$sid = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$s = get_share($sid);
if (!$s || !function_exists('imagecreatefrompng') || !function_exists('imagettftext')) {
    send_png(__DIR__ . '/og-image.png');
}

$cache = SHARE_IMG_DIR . '/' . $sid . '.png';
if (is_file($cache)) send_png($cache);

$black = __DIR__ . '/fonts/InterDisplay-Black.otf';
$semi  = __DIR__ . '/fonts/Inter-SemiBold.otf';
$im = imagecreatefrompng(__DIR__ . '/og-base.png');
imagesavealpha($im, false);

$pink  = imagecolorallocate($im, 255, 105, 180);
$white = imagecolorallocate($im, 255, 255, 255);
$gold  = imagecolorallocate($im, 255, 225, 0);
$muted = imagecolorallocate($im, 169, 163, 173);

$X = 610; $MAXW = 520;

/** Piirtää tekstin; pienentää fonttia, kunnes teksti mahtuu leveyteen. */
function fit_text($im, string $font, float $size, float $min, int $x, int $y, int $color, string $text, int $maxw): void {
    while ($size > $min) {
        $box = imagettfbbox($size, 0, $font, $text);
        if ($box[2] - $box[0] <= $maxw) break;
        $size -= 2;
    }
    imagettftext($im, $size, 0, $x, $y, $color, $font, $text);
}

// Koko px -> pt: GD käyttää pisteitä (96 dpi), 1 px = 0.75 pt
fit_text($im, $black, 22, 14, $X, 110, $gold, 'COLESTEROIDS OG+', $MAXW);
fit_text($im, $black, 60, 16, $X, 200, $pink, $s['nick'], $MAXW);
fit_text($im, $black, 66, 30, $X, 300, $white, fmt_points($s['score']) . ' pistettä', $MAXW);
fit_text($im, $semi, 22, 14, $X, 360, $white, 'Kenttä ' . $s['level'] . ' · ' . $s['eaten'] . ' hyvistä syöty', $MAXW);
fit_text($im, $semi, 22, 14, $X, 400, $gold, 'Diagnoosi: ' . share_diagnosis($s) . ' · ' . fmt_time($s['seconds']), $MAXW);
fit_text($im, $semi, 20, 14, $X, 470, $muted, 'Pystytkö parempaan? Pelaa selaimessa', $MAXW);
fit_text($im, $semi, 20, 14, $X, 508, $pink, 'hessujarvinen.com/softa/colesteroids', $MAXW);

if (ensure_data_dir() && (is_dir(SHARE_IMG_DIR) || mkdir(SHARE_IMG_DIR, 0750, true))) {
    imagepng($im, $cache, 6);
    imagedestroy($im);
    send_png($cache);
}
header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');
imagepng($im);
imagedestroy($im);
