<?php
// app/game_pre.php
require_once 'common.php';
require_once 'desc.php';

$index = load_site_index();
$site = $index['site'];
$categories = get_categories_sorted($index);

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

$pageTitle = $page['title'];
$title = $pageTitle;

$metaDesc = trim((string)($page['description'] ?? ''));
if ($metaDesc === '' && function_exists('generate_gd_description')) {
  $metaDesc = generate_gd_description($cid, $pageTitle, $id);
}

$canonical = 'https://g55.co/game.php?id=' . rawurlencode($id) . '&c=' . rawurlencode($cid);
$imageSrc = 'https://cdn.g55.co/' . $page['id'] . '.png';
$iframeSrc = $page['iframe'];
$sandbox = str_ends_with(parse_url($iframeSrc, PHP_URL_HOST), 'g55.co') ? '' : ' sandbox="allow-scripts allow-same-origin allow-pointer-lock"';

$h1 = $pageTitle;
$desc = $metaDesc;

$prevPage = null;
$nextPage = null;
$prevUrl = null;
$nextUrl = null;

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

$pool = [];
foreach ($pages as $p) {
  if (($p['id'] ?? '') === $id) continue;
  $pool[] = $p;
}

$limit = min(6, count($pool));
$similar = [];

if ($limit > 0) {
  $keys = array_rand($pool, $limit);
  if (!is_array($keys)) $keys = [$keys];

  foreach ($keys as $k) {
    $similar[] = $pool[$k];
  }
}

$moreText = 'More ' . $cat['name'] . ' Games';
$moreHref = '/?c=' . rawurlencode($cid);