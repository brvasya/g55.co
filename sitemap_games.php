<?php
// sitemap_games.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($s): string {
  return rawurlencode((string)$s);
}

$base = 'https://g55.co';

$n = max(1, (int)($_GET['n'] ?? 1));

$perSitemap = 10000;
$pages = array_slice(load_all_games(), ($n - 1) * $perSitemap, $perSitemap);

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $p) {
  $loc = $base . "/game.php?id=" . q($p['id']);

  echo "<url><loc>" . xml_e($loc) . "</loc><lastmod>" . xml_e($today) . "</lastmod></url>\n";
}

echo "</urlset>\n";
