<?php
// sitemap_series.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$base = 'https://g55.co';

$categories = get_categories_sorted(load_site_index());

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($categories as $c) {
  $cid = $c['id'];
  [, $pages] = load_category_pages($cid);
  $clusters = build_game_series_clusters($pages);
  $series = build_game_series_categories($clusters, $cid);

  foreach ($series as $s) {
    $loc = $base . $s['url'];

    echo "<url><loc>" . xml_e($loc) . "</loc><lastmod>" . xml_e($today) . "</lastmod></url>\n";
  }
}

echo "</urlset>\n";
