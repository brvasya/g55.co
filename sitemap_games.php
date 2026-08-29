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

if (!isset($_GET['n'])) {
  http_response_code(400);
  echo 'Missing n';
  exit;
}

$n = (int)$_GET['n'];
if ($n < 1) {
  http_response_code(400);
  echo 'Invalid n';
  exit;
}

$games = load_all_games();

$perSitemap = 10000;
$start = ($n - 1) * $perSitemap;
$pages = array_slice($games, $start, $perSitemap);

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $p) {
  $loc = $base . "/game.php?id=" . q($p['id']);

  echo "  <url>\n";
  echo "    <loc>" . xml_e($loc) . "</loc>\n";
  echo "    <lastmod>" . xml_e($today) . "</lastmod>\n";
  echo "  </url>\n";
}

echo "</urlset>\n";
