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

function is_valid_category_id(string $cid): bool {
  return (bool) preg_match('/^(?:\.[a-z0-9_-]+|[a-z0-9_-]+)$/i', $cid);
}

function clean_category_id($s): string {
  $cid = trim((string)$s);

  // keep dot, letters, numbers, underscore, hyphen only
  $cid = preg_replace('/[^\.a-z0-9_-]/i', '', $cid);

  // compress leading dots to a single dot
  if ($cid !== '' && $cid[0] === '.') {
    $cid = '.' . ltrim($cid, '.');
  }

  if ($cid === '' || !is_valid_category_id($cid)) return '';
  return $cid;
}

function load_category_pages(string $cid): array {
  if ($cid === '' || !is_valid_category_id($cid)) {
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
  $idx = array_rand($pages);
  return $pages[$idx];
}

function get_categories_sorted(array $index): array {
  return sort_categories_alpha($index['categories']);
}

function clean_slug($s): string {
  return preg_replace('/[^a-z0-9_-]/i', '', (string)$s);
}