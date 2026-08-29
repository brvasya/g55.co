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

function category_url(string $cid, ?int $p = null, string $seriesKey = '', string $creator = ''): string {
  if ($creator !== '') {
    $url = 'https://g55.co/?by=' . rawurlencode($creator);
  } else {
    $url = 'https://g55.co/?c=' . rawurlencode($cid);
    if ($seriesKey !== '') $url .= '&t=' . rawurlencode($seriesKey);
  }
  if ($p !== null && $p > 1) $url .= '&p=' . (int) $p;
  return $url;
}

$hasC = isset($_GET['c']);
$hasCreator = isset($_GET['by']);
$seriesCategories = [];

if ($hasCreator || $hasC) {
  $activeSeriesKey = '';
  $activeSeriesTitle = '';
  $creatorName = '';

  if ($hasCreator) {
    if (!is_string($_GET['by'])) {
      header('Location: /', true, 302);
      exit;
    }

    $creatorClusters = build_creator_clusters(load_all_games());
    $creatorCluster = find_game_cluster_for_creator($creatorClusters, $_GET['by']);

    if (!$creatorCluster) {
      header('Location: /', true, 302);
      exit;
    }

    $creatorName = trim((string)$creatorCluster[0]['creator']);
    $displayPages = $creatorCluster;
    $headingName = $creatorName;
  } else {
    $cid = clean_slug($_GET['c']);
    if ($cid === '' || !isset($catMap[$cid])) {
      header('Location: /', true, 302);
      exit;
    }

    $cat = $catMap[$cid];
    list(, $pages) = load_category_pages($cid);

    $seriesClusters = build_game_series_clusters($pages);
    $seriesCategories = build_game_series_categories($seriesClusters, $cid);
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

    $headingName = $cat['name'];

    if ($activeSeriesTitle !== '') {
      $normalizedCategoryName = normalize_game_series_title($cat['name']);
      $seriesAlreadyInCategoryName = $normalizedCategoryName === $activeSeriesKey
        || strpos($normalizedCategoryName, $activeSeriesKey . ' ') === 0;

      if (!$seriesAlreadyInCategoryName) {
        $headingName = $activeSeriesTitle . ' ' . $headingName;
      }
    }
  }

  $pager = category_pages_pagination($displayPages, 64, 'p');
  $pageNum = $pager['page'];

  if ($hasCreator) {
    $canonical = category_url('', $pageNum, '', $creatorName);
    $prevUrl = $pager['has_prev'] ? category_url('', $pageNum - 1, '', $creatorName) : null;
    $nextUrl = $pager['has_next'] ? category_url('', $pageNum + 1, '', $creatorName) : null;
  } else {
    $canonical = category_url($cid, $pageNum, $activeSeriesKey);
    $prevUrl = $pager['has_prev'] ? category_url($cid, $pageNum - 1, $activeSeriesKey) : null;
    $nextUrl = $pager['has_next'] ? category_url($cid, $pageNum + 1, $activeSeriesKey) : null;
  }

  $gridItems = [];
  foreach ($pager['items'] as $p) {
    $gridItems[] = [
      'id' => $p['id'],
      'title' => $p['title'],
      'image' => 'https://cdn.g55.co/' . $p['id'] . '.png',
      'category' => $hasCreator ? category_id_from_name($p['categories'][0]) : $cid,
    ];
  }

  $count = $pager['total_items'];
  $h1 = ($count > 0 ? number_format($count) . ' ' : '') . $headingName . ' Games';
  if ($pageNum > 1) $h1 .= ' Page ' . $pageNum;

  $desc = 'Play free ' . $headingName . ' games online on G55.CO.';
  $title = $h1 . ' ▶ Play Free Online';
  $metaDesc = strip_tags($desc);

} else {
  $totalCount = count(load_all_games());
  $gridItems = [];
  $usedHomeIds = [];

  foreach ($categories as $c) {
    $catId = $c['id'];
    list(, $pages) = load_category_pages($catId);
    $addedForCategory = 0;

    foreach ($pages as $newest) {
      $gameId = (string)($newest['id'] ?? '');
      if ($gameId === '' || isset($usedHomeIds[$gameId])) {
        continue;
      }

      $gridItems[] = [
        'id' => $gameId,
        'title' => $newest['title'],
        'image' => 'https://cdn.g55.co/' . $gameId . '.png',
        'category' => $catId,
      ];

      $usedHomeIds[$gameId] = true;
      $addedForCategory++;

      if ($addedForCategory >= 4) {
        break;
      }
    }
  }

  $h1 = ($totalCount > 0 ? number_format($totalCount) . ' ' : '') . $site['title'];
  $desc = $site['description'];

  $title = $h1 . ' ▶ Play Now';
  $metaDesc = strip_tags($desc);
  $canonical = 'https://g55.co/';
}
