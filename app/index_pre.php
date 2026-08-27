<?php
// app/index_pre.php
require_once 'common.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

$catMap = [];
foreach ($categories as $c) {
  $catMap[$c['id']] = $c;
}

function category_pages_pagination(array $allPages, int $perPage = 64, string $pageParam = 'p'): array {
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

function category_url(string $cid, ?int $p = null, string $seriesKey = ''): string {
  $url = 'https://g55.co/?c=' . rawurlencode($cid);
  if ($seriesKey !== '') $url .= '&t=' . rawurlencode($seriesKey);
  if ($p !== null && $p > 1) $url .= '&p=' . (int) $p;
  return $url;
}

$hasC = isset($_GET['c']);
$seriesCategories = [];

if ($hasC) {
  $cid = clean_slug($_GET['c']);
  if ($cid === '' || !isset($catMap[$cid])) {
    header('Location: /', true, 302);
    exit;
  }

  $cat = $catMap[$cid];
  list(, $pages) = load_category_pages($cid);

  $seriesClusters = build_game_series_clusters($pages);
  $seriesCategories = build_game_series_categories($seriesClusters, $cid);
  $activeSeriesKey = '';
  $activeSeriesTitle = '';
  $displayPages = $pages;

  if (isset($_GET['t'])) {
    if (!is_string($_GET['t'])) {
      header('Location: /?c=' . rawurlencode($cid), true, 302);
      exit;
    }

    $activeSeriesCluster = find_game_cluster_for_series_key($seriesClusters, $_GET['t']);

    if (!$activeSeriesCluster) {
      header('Location: /?c=' . rawurlencode($cid), true, 302);
      exit;
    }

    $activeSeriesKey = series_cluster_key($activeSeriesCluster);
    $activeSeriesTitle = series_cluster_title($activeSeriesCluster);
    $displayPages = $activeSeriesCluster;
  }

  $pager = category_pages_pagination($displayPages, 64, 'p');
  $pageNum = $pager['page'];

  $canonical = category_url($cid, $pageNum, $activeSeriesKey);
  $prevUrl = $pager['has_prev'] ? category_url($cid, $pageNum - 1, $activeSeriesKey) : null;
  $nextUrl = $pager['has_next'] ? category_url($cid, $pageNum + 1, $activeSeriesKey) : null;

  $gridItems = [];
  foreach ($pager['items'] as $p) {
    $gridItems[] = [
      'id' => $p['id'],
      'title' => $p['title'],
      'image' => 'https://cdn.g55.co/' . $p['id'] . '.png',
      'category' => $cid,
    ];
  }

  $count = $pager['total_items'];
  $headingName = $cat['name'];

  if ($activeSeriesTitle !== '') {
    $normalizedCategoryName = normalize_game_series_title($cat['name']);
    $seriesAlreadyInCategoryName = $normalizedCategoryName === $activeSeriesKey
      || strpos($normalizedCategoryName, $activeSeriesKey . ' ') === 0;

    if (!$seriesAlreadyInCategoryName) {
      $headingName = $activeSeriesTitle . ' ' . $headingName;
    }
  }

  $h1 = ($count > 0 ? number_format($count) . ' ' : '') . $headingName . ' Games';
  if ($pageNum > 1) $h1 .= ' Page ' . $pageNum;

  $desc = $cat['description'];
  $title = $h1 . ' ▶ Play Free Online';
  $metaDesc = strip_tags($desc);

} else {
  $totalCount = 0;
  $gridItems = [];

  foreach ($categories as $c) {
    $catId = $c['id'];

    list(, $pages) = load_category_pages($catId);
    $totalCount += count($pages);

    $newest = newest_page($pages);
    $gridItems[] = [
      'id' => $newest['id'],
      'title' => $newest['title'],
      'image' => 'https://cdn.g55.co/' . $newest['id'] . '.png',
      'category' => $catId,
    ];
  }

  $h1 = ($totalCount > 0 ? number_format($totalCount) . ' ' : '') . $site['title'];
  $desc = $site['description'];

  $title = $h1 . ' ▶ Play Now';
  $metaDesc = strip_tags($desc);
  $canonical = 'https://g55.co/';
}
