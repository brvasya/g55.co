<?php
// sitemap.php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'common.php';

header('Content-Type: application/xml; charset=utf-8');

function xml_e($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$base = 'https://g55.co';

$allPageCount = count(load_all_games());

$perSitemap = 10000;
$pageSitemaps = (int)ceil($allPageCount / $perSitemap);
if ($pageSitemaps < 1) $pageSitemaps = 1;

$today = date('Y-m-d');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

echo "<sitemap><loc>" . xml_e($base . "/sitemap_categories.php") . "</loc><lastmod>" . xml_e($today) . "</lastmod></sitemap>\n";

echo "<sitemap><loc>" . xml_e($base . "/sitemap_creators.php") . "</loc><lastmod>" . xml_e($today) . "</lastmod></sitemap>\n";

echo "<sitemap><loc>" . xml_e($base . "/sitemap_series.php") . "</loc><lastmod>" . xml_e($today) . "</lastmod></sitemap>\n";

for ($i = 1; $i <= $pageSitemaps; $i++) {
  echo "<sitemap><loc>" . xml_e($base . "/sitemap_games.php?n=" . $i) . "</loc><lastmod>" . xml_e($today) . "</lastmod></sitemap>\n";
}

echo "</sitemapindex>\n";
