<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_GET['c']) || !isset($_GET['p'])) {
    http_response_code(400);
    echo "error: missing_parameters\n";
    echo "usage: gd.php?c=CATEGORY_NAME&p=PAGE_NUMBER\n";
    exit;
}

$category = trim((string)$_GET['c']);
$page     = (int)$_GET['p'];

if ($category === '' || !preg_match('/^[a-z0-9_]+$/i', $category)) {
    http_response_code(400);
    echo "error: invalid_category\n";
    exit;
}

if ($page < 1 || $page > 500) {
    http_response_code(400);
    echo "error: invalid_page\n";
    exit;
}

function slugify(string $s): string {
    $s = trim($s);
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
    echo "error: fetch_failed\n";
    echo "url: {$url}\n";
    exit;
}

$data = json_decode($body, true);

if (!is_array($data)) {
    http_response_code(502);
    echo "error: invalid_json\n";
    echo "url: {$url}\n";
    $snippet = substr($body, 0, 500);
    echo "body_preview:\n{$snippet}\n";
    exit;
}

echo "status: ok\n";
echo "category: {$category}\n";
echo "page: {$page}\n";
echo "items_count: " . count($data) . "\n\n";

$idx = 0;

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $title = isset($item['Title']) ? trim((string)$item['Title']) : '';
    if ($title === '') continue;

    $slug = slugify($title);
    if ($slug === '') continue;

    $id = $slug;

    $idx++;
    echo "candidate: {$idx}\n";
    echo "Title: {$title}\n";
    echo "slug: {$slug}\n";
    echo "id: {$id}\n\n";

    if ($idx >= 50) break;
}