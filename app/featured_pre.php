<?php
// app/featured_pre.php

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

function featured_pages_pagination(array $allPages, int $perPage = 64, string $pageParam = 'p'): array {
  $totalItems = count($allPages);
  $totalPages = max(1, (int) ceil($totalItems / $perPage));

  $page = 1;
  if (isset($_GET[$pageParam])) {
    $page = (int) $_GET[$pageParam];
    if ($page < 1) $page = 1;
  }
  if ($page > $totalPages) $page = $totalPages;

  $offset = ($page - 1) * $perPage;
  $items = array_slice($allPages, $offset, $perPage);

  return [
    'page' => $page,
    'per_page' => $perPage,
    'total_items' => $totalItems,
    'total_pages' => $totalPages,
    'items' => $items,
    'has_prev' => $page > 1,
    'has_next' => $page < $totalPages,
  ];
}

function featured_url(?int $p = null): string {
  if ($p === null || $p <= 1) return 'https://g55.co/?c=exclusive';
  return 'https://g55.co/?c=exclusive&p=' . (int) $p;
}

$featuredPages = [];
foreach ($categories as $c) {
  $catId = $c['id'];
  list($_, $pages) = load_category_pages($catId);

  foreach ($pages as $p) {
    if (str_contains($p['iframe'], 'g55.co') || str_contains($p['iframe'], 'gamemonetize.co')) {
      $featuredPages[] = [
        'id' => $p['id'],
        'title' => $p['title'],
        'image' => 'https://cdn.g55.co/' . $p['id'] . '.png',
        'category' => $catId,
      ];
    }
  }
}

$pager = featured_pages_pagination($featuredPages, 64, 'p');
$pageNum = $pager['page'];
$cid = 'exclusive';

$canonical = featured_url($pageNum);
$prevUrl = $pager['has_prev'] ? featured_url($pageNum - 1) : null;
$nextUrl = $pager['has_next'] ? featured_url($pageNum + 1) : null;

$gridItems = $pager['items'];
$count = count($featuredPages);

$h1 = ($count > 0 ? number_format($count) . ' ' : '') . 'Exclusive Games';
if ($pageNum > 1) $h1 .= ' Page ' . $pageNum;

$desc = $site['exclusive'];
$title = $h1 . ' ▶ Play Free Online';
$metaDesc = $desc;

$cat = [
  'id' => $cid,
  'name' => 'Exclusive',
  'description' => $desc,
];

$currentCluster = null;
$seriesBlocks = [];
