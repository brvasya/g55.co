<?php
// app/index_pre.php
require_once 'common.php';

if (!function_exists('category_pages_pagination')) {
  function category_pages_pagination(array $allPages, int $perPage = 100, string $pageParam = 'p'): array {
    $totalItems = count($allPages);
    $totalPages = max(1, (int)ceil($totalItems / max(1, $perPage)));

    $page = 1;
    if (isset($_GET[$pageParam])) {
      $page = (int)$_GET[$pageParam];
    }
    if ($page < 1) $page = 1;
    if ($page > $totalPages) $page = $totalPages;

    $offset = ($page - 1) * $perPage;
    $items = array_slice($allPages, $offset, $perPage);

    return [
      'page' => $page,
      'per_page' => $perPage,
      'total_items' => $totalItems,
      'total_pages' => $totalPages,
      'items' => $items,
      'has_prev' => ($page > 1),
      'has_next' => ($page < $totalPages),
    ];
  }
}

if (!function_exists('category_url')) {
  function category_url(string $cid, $p = null): string {
    $cidEnc = rawurlencode($cid);
    $p = ($p === null) ? null : (int)$p;

    if ($p === null || $p <= 1) {
      return 'https://g55.co/?c=' . $cidEnc;
    }
    return 'https://g55.co/?c=' . $cidEnc . '&p=' . $p;
  }
}

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

$catMap = [];
foreach ($categories as $c) {
  $catMap[$c['id']] = $c;
}

$hasC = isset($_GET['c']);

if ($hasC) {
  $cid = clean_slug($_GET['c']);
  if ($cid === '' || !isset($catMap[$cid])) {
    header('Location: /', true, 302);
    exit;
  }

  $cat = $catMap[$cid];

  list($_, $pages) = load_category_pages($cid);

  $pager = category_pages_pagination($pages, 100, 'p');
  $pageNum = $pager['page'];

  $gridItems = [];
  foreach ($pager['items'] as $p) {
    $gridItems[] = [
      'id' => $p['id'],
      'title' => $p['title'],
      'image' => 'https://cdn.g55.co/' . $p['id'] . '.png',
      'category' => $cid
    ];
  }

  $count = (int)$pager['total_items'];
  $h1 = ($count > 0 ? number_format($count) . ' ' : '') . $cat['name'] . ' Games';
  $desc = $cat['description'];

  $title = $h1 . ($pageNum > 1 ? ' Page ' . $pageNum : '');
  $metaDesc = $desc;

  $canonical = category_url($cid, $pageNum);

  $prevUrl = null;
  $nextUrl = null;

  if (!empty($pager['has_prev'])) {
    $prevUrl = category_url($cid, $pageNum - 1);
  }
  if (!empty($pager['has_next'])) {
    $nextUrl = category_url($cid, $pageNum + 1);
  }
} else {
  $totalCount = 0;
  $gridItems = [];

  foreach ($categories as $c) {
    $catId = $c['id'];

    list($_, $pages) = load_category_pages($catId);
    $totalCount += count($pages);

    $newest = newest_page($pages);
    $gridItems[] = [
      'id' => $newest['id'],
      'title' => $newest['title'],
      'image' => 'https://cdn.g55.co/' . $newest['id'] . '.png',
      'category' => $catId
    ];
  }

  $h1 = ($totalCount > 0 ? number_format($totalCount) . ' ' : '') . $site['title'];
  $desc = $site['description'];

  $title = $h1;
  $metaDesc = $desc;
  $canonical = 'https://g55.co/';
}