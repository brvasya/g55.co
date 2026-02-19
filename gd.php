<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['c']) || !isset($_GET['p'])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "missing_parameters",
        "usage" => "gd.php?c=CATEGORY_NAME&p=PAGE_NUMBER"
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$category = trim((string)$_GET['c']);
$page     = (int)$_GET['p'];

if ($category === '' || !preg_match('/^[a-z0-9_]+$/i', $category)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "invalid_category"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($page < 1 || $page > 500) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "invalid_page"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function make_id_from_title(string $title): string {
    $s = trim($title);
    if ($s === '') return '';

    $s = mb_strtolower($s, 'UTF-8');

    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== '') $s = $t;
    }

    $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
    $s = trim($s, '-');
    $s = preg_replace('/-+/', '-', $s);

    if (strlen($s) > 120) {
        $s = substr($s, 0, 120);
        $s = rtrim($s, '-');
    }

    return $s;
}

function pick_iframe(array $item): string {
    if (!empty($item['Url']) && is_string($item['Url'])) {
        return trim($item['Url']);
    }
    if (!empty($item['Md5']) && is_string($item['Md5'])) {
        $md5 = trim($item['Md5']);
        if ($md5 !== '') return "https://html5.gamedistribution.com/" . $md5 . "/";
    }
    return '';
}

function read_category_ids(string $path): array {
    if (!is_file($path)) return [[], "file_not_found"];
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return [[], "read_failed"];

    $json = json_decode($raw, true);
    if (!is_array($json)) return [[], "invalid_json"];

    $pages = null;

    if (isset($json['pages']) && is_array($json['pages'])) {
        $pages = $json['pages'];
    } elseif (isset($json['items']) && is_array($json['items'])) {
        $pages = $json['items'];
    } elseif (array_is_list($json)) {
        $pages = $json;
    } else {
        $pages = [];
    }

    $ids = [];
    foreach ($pages as $p) {
        if (!is_array($p)) continue;
        $id = isset($p['id']) ? trim((string)$p['id']) : '';
        if ($id !== '') $ids[$id] = true;
    }

    return [$ids, "ok"];
}

$base = 'https://catalog.api.gamedistribution.com/api/v2.0/rss/All/';
$sourceUrl = $base . '?categories=' . rawurlencode($category) . '&page=' . $page;

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 20,
        'header'  => "User-Agent: g55-gd-bot/1.0\r\nAccept: application/json, */*;q=0.8\r\n",
    ]
]);

$body = @file_get_contents($sourceUrl, false, $ctx);

if ($body === false || $body === '') {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => "fetch_failed", "source_url" => $sourceUrl], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$data = json_decode($body, true);

if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        "ok" => false,
        "error" => "invalid_source_json",
        "source_url" => $sourceUrl,
        "body_preview" => substr($body, 0, 500)
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$categoryFile = __DIR__ . '/categories/' . $category . '.json';
list($existingIds, $categoryReadStatus) = read_category_ids($categoryFile);

$pages = [];
$seenIdsInRun = [];

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $title = isset($item['Title']) ? trim((string)$item['Title']) : '';
    if ($title === '') continue;

    $id = make_id_from_title($title);
    if ($id === '') continue;

    if (isset($seenIdsInRun[$id])) continue;
    $seenIdsInRun[$id] = true;

    $iframe = pick_iframe($item);
    if ($iframe === '') continue;

    $description = isset($item['Description']) ? trim((string)$item['Description']) : '';
    if ($description === '') $description = $title;

    $isDuplicate = isset($existingIds[$id]);

    $pages[] = [
        "id" => $id,
        "title" => $title,
        "iframe" => $iframe,
        "description" => $description,
        "duplicate_in_category" => $isDuplicate
    ];
}

$dupIds = [];
$newIds = [];

foreach ($pages as $p) {
    if (!empty($p['duplicate_in_category'])) $dupIds[] = $p['id'];
    else $newIds[] = $p['id'];
}

echo json_encode([
    "ok" => true,
    "category" => $category,
    "page" => $page,
    "source_url" => $sourceUrl,
    "category_file" => $categoryFile,
    "category_read_status" => $categoryReadStatus,
    "existing_ids_count" => count($existingIds),
    "candidates_count" => count($pages),
    "duplicates_count" => count($dupIds),
    "new_count" => count($newIds),
    "duplicate_ids" => $dupIds,
    "new_ids" => $newIds,
    "pages" => $pages
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);