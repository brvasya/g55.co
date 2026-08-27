<?php
// app/common.php

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function read_json(string $path): array {
  static $cache = [];

  if (isset($cache[$path])) {
    return $cache[$path];
  }

  $raw = file_get_contents($path);
  if ($raw === false) {
    http_response_code(500);
    exit;
  }

  $data = json_decode($raw, true);
  if (!is_array($data)) {
    http_response_code(500);
    exit;
  }

  $cache[$path] = $data;
  return $data;
}

function load_site_index(): array {
  $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'categories.json';
  return read_json($path);
}

function load_category_pages(string $cid): array {
  if ($cid === '' || !preg_match('/^[a-z0-9_-]+$/i', $cid)) {
    http_response_code(400);
    exit;
  }

  $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'categories' . DIRECTORY_SEPARATOR . $cid . '.json';
  $data = read_json($path);

  if (!isset($data['pages']) || !is_array($data['pages'])) {
    http_response_code(500);
    exit;
  }

  return [$cid, $data['pages']];
}

function sort_categories_alpha(array $cats): array {
  usort($cats, fn($a, $b) => strcasecmp($a['name'], $b['name']));
  return $cats;
}

function newest_page(array $pages): array {
  $n = count($pages);
  if ($n === 0) {
    http_response_code(500);
    exit;
  }

  return $pages[0];
}

function get_categories_sorted(array $index): array {
  return sort_categories_alpha($index['categories']);
}

function clean_slug($s): string {
  return preg_replace('/[^a-z0-9_-]/i', '', (string)$s);
}

function normalize_game_series_title(string $title): string {
    $title = strtolower(trim($title));
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title);
    return preg_replace('/\s+/', ' ', $title);
}

function detect_game_series_key(string $title): string {
    $words = array_values(array_filter(explode(' ', normalize_game_series_title($title))));
    return $words[0] ?? '';
}

function build_game_series_clusters(array $pages): array {
    $clusters = [];

    foreach ($pages as $page) {
        $key = detect_game_series_key($page['title']);

        if ($key === '') {
            continue;
        }

        $page['_series_key'] = $key;
        $clusters[$key][] = $page;
    }

    return keep_multi_game_clusters($clusters);
}

function keep_multi_game_clusters(array $clusters): array {
    return array_values(array_filter(
        $clusters,
        fn($group) => count($group) >= 2
    ));
}

function find_game_cluster_for_page(array $clusters, string $pageId): array {
    foreach ($clusters as $cluster) {
        foreach ($cluster as $page) {
            if (($page['id'] ?? '') === $pageId) {
                return $cluster;
            }
        }
    }

    return [];
}

function cluster_links_except_current(array $cluster, string $pageId): array {
    return array_values(array_filter(
        $cluster,
        fn($page) => ($page['id'] ?? '') !== $pageId
    ));
}

function series_cluster_key(array $cluster): string {
    return (string)($cluster[0]['_series_key'] ?? '');
}

function series_cluster_title(array $cluster): string {
    return ucwords(series_cluster_key($cluster));
}

function find_game_cluster_for_series_key(array $clusters, string $seriesKey): array {
    $seriesKey = detect_game_series_key($seriesKey);

    if ($seriesKey === '') {
        return [];
    }

    foreach ($clusters as $cluster) {
        if (series_cluster_key($cluster) === $seriesKey) {
            return $cluster;
        }
    }

    return [];
}

function build_game_series_categories(array $clusters, string $categoryId): array {
    $categories = [];

    foreach ($clusters as $cluster) {
        $key = series_cluster_key($cluster);

        if ($key === '') {
            continue;
        }

        $categories[] = [
            'title' => series_cluster_title($cluster),
            'url' => '/?c=' . rawurlencode($categoryId) . '&t=' . rawurlencode($key),
        ];
    }

    return $categories;
}

function build_creator_clusters(array $pages): array {
    $clusters = [];

    foreach ($pages as $page) {
        $creator = trim((string)($page['creator'] ?? ''));

        if ($creator === '') {
            continue;
        }

        $clusters[strtolower($creator)][] = $page;
    }

    return keep_multi_game_clusters($clusters);
}
