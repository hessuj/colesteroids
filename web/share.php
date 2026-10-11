<?php
/**
 * Colesteroids OG+ – yhden tuloksen jakosivu Facebookia varten.
 * share.php?id=<jakotunniste>
 *
 * Facebook lukee tältä sivulta otsikon, kuvauksen ja kuvan (Open Graph).
 * Ihmiset ohjataan JavaScriptillä suoraan peliin. Facebookin lukija ei aja
 * JavaScriptiä, joten se näkee tämän sivun tiedot eikä pelin yleisiä tietoja.
 *
 * MIT License, (c) 2026 Hessu Järvinen
 */
declare(strict_types=1);
require __DIR__ . '/share_lib.php';

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$sid = is_string($_GET['id'] ?? null) ? $_GET['id'] : '';
$s = get_share($sid);
if (!$s) {
    header('Location: ./', true, 302);
    exit;
}

$base  = base_url();
$e     = fn(string $t): string => htmlspecialchars($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$title = share_title($s);
$desc  = share_description($s);
$url   = $base . 'share.php?id=' . $sid;
$img   = $base . 'share-image.php?id=' . $sid;
?><!doctype html>
<html lang="fi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($title) ?></title>
<meta name="description" content="<?= $e($desc) ?>">
<meta name="robots" content="noindex">
<link rel="canonical" href="<?= $e($url) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $e($url) ?>">
<meta property="og:site_name" content="hessujarvinen.com">
<meta property="og:locale" content="fi_FI">
<meta property="og:title" content="<?= $e($title) ?>">
<meta property="og:description" content="<?= $e($desc) ?>">
<meta property="og:image" content="<?= $e($img) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= $e($s['nick'] . ': ' . fmt_points($s['score']) . ' pistettä Colesteroids OG+ -pelissä') ?>">
<meta name="twitter:card" content="summary_large_image">
<style>body{margin:0;background:#000;color:#fff;font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;text-align:center;padding:16px}a{color:#ff69b4}</style>
<script>location.replace("./");</script>
</head>
<body>
<p><?= $e($title) ?><br><br><a href="./">Pelaa Colesteroids OG+</a></p>
</body>
</html>
