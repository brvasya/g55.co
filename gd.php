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
    echo "allowed: letters numbers underscore\n";
    exit;
}

if ($page < 1 || $page > 500) {
    http_response_code(400);
    echo "error: invalid_page\n";
    echo "allowed: 1 to 500\n";
    exit;
}

$base = 'https://catalog.api.gamedistribution.com/api/v2.0/rss/All/';
$url  = $base . '?categories=' . rawurlencode($category) . '&page=' . $page;

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 20,
        'header'  => "User-Agent: g55-gd-bot/1.0\r\nAccept: application/json, application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
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
    $snippet = substr($body, 0, 300);
    echo "body_preview: " . str_replace(["\r", "\n"], [' ', ' '], $snippet) . "\n";
    exit;
}

echo "status: ok\n";
echo "category: {$category}\n";
echo "page: {$page}\n";
echo "url: {$url}\n";
echo "items_total_on_page: " . count($data) . "\n\n";

$limit = 10;
$count = 0;

foreach ($data as $row) {
    if (!is_array($row)) continue;

    $title = isset($row['Title']) ? trim((string)$row['Title']) : '';
    $md5   = isset($row['Md5']) ? trim((string)$row['Md5']) : '';
    $gameUrl = isset($row['Url']) ? trim((string)$row['Url']) : '';
    $thumb = '';

    if (isset($row['Asset']) && is_array($row['Asset']) && !empty($row['Asset'])) {
        $thumb = (string)$row['Asset'][0];
    }

    if ($title === '' || $md5 === '') continue;

    $count++;
    echo $count . ". " . $title . "\n";
    echo "md5: " . $md5 . "\n";
    if ($gameUrl !== '') echo "game_url: " . $gameUrl . "\n";
    if ($thumb !== '') echo "thumb: " . $thumb . "\n";
    echo "\n";

    if ($count >= $limit) break;
}

if ($count === 0) {
    echo "note: no_items_parsed\n";
}