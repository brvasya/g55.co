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

$itemIndex = 0;

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $itemIndex++;
    echo "============================\n";
    echo "ITEM {$itemIndex}\n";
    echo "============================\n";

    foreach ($item as $key => $value) {

        echo "KEY: {$key}\n";

        if (is_array($value)) {
            echo "TYPE: array\n";
            echo "VALUE:\n";
            print_r($value);
        } else {
            echo "TYPE: " . gettype($value) . "\n";
            echo "VALUE: " . $value . "\n";
        }

        echo "\n";
    }

    echo "\n";
}