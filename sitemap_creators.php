<?php
// sitemap_creators.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function q($s): string {
  return rawurlencode((string)$s);
}

$base = 'https://g55.co';

$creatorClusters = build_creator_clusters(load_all_games());

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($creatorClusters as $cluster) {
  $creator = trim((string)($cluster[0]['creator'] ?? ''));
  if ($creator === '') {
    continue;
  }

  $loc = $base . "/?by=" . q($creator);

  echo "  <url>\n";
  echo "    <loc>" . xml_e($loc) . "</loc>\n";
  echo "    <lastmod>" . xml_e($today) . "</lastmod>\n";
  echo "  </url>\n";
}

echo "</urlset>\n";
