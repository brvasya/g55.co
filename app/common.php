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
  usort($cats, fn($a,$b)=>($a['id']=='exclusive'? -1:($b['id']=='exclusive'?1:strcasecmp($a['name'],$b['name']))));
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

function extract_category_links_from_description(string $html): array {
    if ($html === '') {
        return [];
    }

    preg_match_all('/href=["\']\/?\?c=([^"\']+)["\']/i', $html, $m);

    $links = array_map('clean_slug', $m[1] ?? []);
    $links = array_values(array_unique(array_filter($links)));

    return $links;
}

function build_category_clusters(array $categories): array {
    $map = [];
    $graph = [];
    $order = [];

    foreach ($categories as $cat) {
        if (!is_array($cat) || empty($cat['id']) || empty($cat['name'])) {
            continue;
        }

        $id = clean_slug($cat['id']);
        if ($id === '') {
            continue;
        }

        $cat['_cluster_links'] = extract_category_links_from_description((string)($cat['description'] ?? ''));

        $map[$id] = $cat;
        $graph[$id] = [];
        $order[] = $id;
    }

    $n = count($order);

    for ($i = 0; $i < $n; $i++) {
        $a = $order[$i];
        $linksA = $map[$a]['_cluster_links'];

        if (!$linksA) {
            continue;
        }

        for ($j = $i + 1; $j < $n; $j++) {
            $b = $order[$j];
            $linksB = $map[$b]['_cluster_links'];

            if (!$linksB) {
                continue;
            }

            $shared = array_intersect($linksA, $linksB);
            $aLinksToB = in_array($b, $linksA, true);
            $bLinksToA = in_array($a, $linksB, true);

            if ($shared || $aLinksToB || $bLinksToA) {
                $graph[$a][] = $b;
                $graph[$b][] = $a;
            }
        }
    }

    $visited = [];
    $clusters = [];
    $browseMore = [];

    foreach ($order as $id) {
        if (isset($visited[$id])) {
            continue;
        }

        if (empty($map[$id]['_cluster_links'])) {
            $visited[$id] = true;
            $browseMore[] = $map[$id];
            continue;
        }

        $queue = [$id];
        $visited[$id] = true;
        $cluster = [];

        while ($queue) {
            $cur = array_shift($queue);
            $cluster[] = $map[$cur];

            foreach ($graph[$cur] as $next) {
                if (!isset($visited[$next])) {
                    $visited[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        if (count($cluster) > 1) {
            $clusters[] = $cluster;
        } else {
            $browseMore[] = $cluster[0];
        }
    }

    return [
        'clusters' => $clusters,
        'browse_more' => $browseMore,
    ];
}

function get_categories_clustered(array $index): array {
    return build_category_clusters($index['categories'] ?? []);
}

function find_cluster_for_category(array $clustered, string $categoryId): array {
    foreach ($clustered['clusters'] as $cluster) {
        foreach ($cluster as $cat) {
            if ($cat['id'] === $categoryId) {
                return $cluster;
            }
        }
    }
    return [];
}

function normalize_game_series_title(string $title): string {
    $title = strtolower(trim($title));
    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $title);
    return preg_replace('/\s+/', ' ', $title);
}

function detect_game_series_key(string $title): string {
    $words = array_values(array_filter(explode(' ', normalize_game_series_title($title))));

    if (!$words) {
        return '';
    }

    if (count($words) >= 2 && preg_match('/^\d+$/', $words[1])) {
        return $words[0];
    }

    if (count($words) >= 2) {
        return $words[0] . ' ' . $words[1];
    }

    return '';
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

    foreach ($clusters as $key => $group) {
        if (count($group) < 3) {
            unset($clusters[$key]);
        }
    }

    return array_values($clusters);
}

function find_series_cluster_for_page(array $clusters, string $pageId): array {
    foreach ($clusters as $cluster) {
        foreach ($cluster as $page) {
            if ($page['id'] === $pageId) {
                return $cluster;
            }
        }
    }

    return [];
}

function series_cluster_title(array $cluster): string {
    return ucwords($cluster[0]['_series_key']);
}
