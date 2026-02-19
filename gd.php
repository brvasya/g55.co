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
        if ($md5 !== '') {
            return "https://html5.gamedistribution.com/" . $md5 . "/";
        }
    }
    return '';
}

$base = 'https://catalog.api.gamedistribution.com/api/v2.0/rss/All/';
$url  = $base . '?categories=' . rawurlencode($category) . '&page=' . $page;

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 20,
        'header'  => "User-Agent: g55-gd-bot/1.0\r\nAccept: application/json, */*;q=0.8\r\n",
    ]
]);

$body = @file_get_contents($url, false, $ctx);

if ($body === false || $body === '') {
    http_response_code(502);
    echo json_encode(["ok" => false, "error" => "fetch_failed", "url" => $url], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$data = json_decode($body, true);

if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        "ok" => false,
        "error" => "invalid_json",
        "url" => $url,
        "body_preview" => substr($body, 0, 500)
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$candidates = [];
$seenIds = [];

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $title = isset($item['Title']) ? trim((string)$item['Title']) : '';
    if ($title === '') continue;

    $id = make_id_from_title($title);
    if ($id === '') continue;

    if (isset($seenIds[$id])) {
        continue;
    }
    $seenIds[$id] = true;

    $iframe = pick_iframe($item);
    if ($iframe === '') continue;

    $description = isset($item['Description']) ? trim((string)$item['Description']) : '';
    if ($description === '') $description = $title;

    $candidates[] = [
        "id" => $id,
        "title" => $title,
        "iframe" => $iframe,
        "description" => $description
    ];
}

echo json_encode([
    "ok" => true,
    "category" => $category,
    "page" => $page,
    "source_url" => $url,
    "count" => count($candidates),
    "items" => $candidates
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);