<?php
// app/game_pre.php
require_once 'common.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);
$grouped = get_categories_clustered($index);

if (!isset($_GET['id'], $_GET['c'])) {
  header('Location: /', true, 302);
  exit;
}

$id = clean_slug($_GET['id']);
$cid = clean_slug($_GET['c']);

if ($id === '' || $cid === '') {
  header('Location: /', true, 302);
  exit;
}

$cat = null;
foreach ($categories as $c) {
  if ($c['id'] === $cid) {
    $cat = $c;
    break;
  }
}

if ($cat === null) {
  header('Location: /', true, 302);
  exit;
}

$currentCluster = find_cluster_for_category($grouped, $cid);

list($_, $pages) = load_category_pages($cid);

$page = null;
$pageIndex = -1;

for ($i = 0; $i < count($pages); $i++) {
  if (($pages[$i]['id'] ?? '') === $id) {
    $page = $pages[$i];
    $pageIndex = $i;
    break;
  }
}

if ($page === null) {
  header('Location: /', true, 302);
  exit;
}

$seriesClusters = build_game_series_clusters($pages);
$currentSeriesCluster = find_series_cluster_for_page($seriesClusters, $page['id']);
$currentSeriesTitle = $currentSeriesCluster ? series_cluster_title($currentSeriesCluster) : '';

$seriesLinks = [];

if ($currentSeriesCluster) {
  foreach ($currentSeriesCluster as $p) {
    if ($p['id'] !== $page['id']) {
      $seriesLinks[] = $p;
    }
  }
}

$pageTitle = $page['title'];
$title = $pageTitle . ' ▶ Play Free ' . $cat['name'] . ' Game Online';

$metaDesc = trim(preg_replace('/\s+/', ' ', preg_split('/key features/i', $page['description'])[0]));
$canonical = 'https://g55.co/game.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);
$imageSrc = 'https://cdn.g55.co/' . $page['id'] . '.png';
$iframeSrc = $page['iframe'];
$sandbox = str_ends_with(parse_url($iframeSrc, PHP_URL_HOST), 'g55.co') ? '' : ' sandbox="allow-scripts allow-same-origin allow-pointer-lock"';

$h1 = $pageTitle;
$desc = $page['description'];

$prevPage = null;
$nextPage = null;
$prevUrl = null;
$nextUrl = null;

function get_nav_game_title(?array $page): string {
  return trim((string)($page['title']));
}

if ($pageIndex !== -1) {
  if ($pageIndex > 0) {
    $prevPage = $pages[$pageIndex - 1];
    $prevUrl = '/game.php?id=' . rawurlencode($prevPage['id']) . '&c=' . rawurlencode($cid);
  }

  if ($pageIndex < count($pages) - 1) {
    $nextPage = $pages[$pageIndex + 1];
    $nextUrl = '/game.php?id=' . rawurlencode($nextPage['id']) . '&c=' . rawurlencode($cid);
  }
}

$prevTitle = get_nav_game_title($prevPage);
$nextTitle = get_nav_game_title($nextPage);

$similar = [];

if ($pageIndex !== -1) {
    $used = [$id => true];
    $radius = 1;
    $max = count($pages);

    while (count($similar) < 6 && (($pageIndex - $radius) >= 0 || ($pageIndex + $radius) < $max)) {
        $left = $pageIndex - $radius;
        $right = $pageIndex + $radius;

        if ($left >= 0) {
            $p = $pages[$left];
            $pid = $p['id'] ?? '';
            if ($pid !== '' && !isset($used[$pid])) {
                $similar[] = $p;
                $used[$pid] = true;
                if (count($similar) >= 6) {
                    break;
                }
            }
        }

        if ($right < $max) {
            $p = $pages[$right];
            $pid = $p['id'] ?? '';
            if ($pid !== '' && !isset($used[$pid])) {
                $similar[] = $p;
                $used[$pid] = true;
                if (count($similar) >= 6) {
                    break;
                }
            }
        }

        $radius++;
    }
}

$moreText = 'More ' . $cat['name'] . ' Games';
$moreHref = '/?c=' . rawurlencode($cid);
