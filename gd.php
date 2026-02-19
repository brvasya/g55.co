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
$rssUrl = $base . '?categories=' . rawurlencode($category) . '&page=' . $page;

$ctx = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'timeout' => 15,
        'header'  => "User-Agent: g55-gd-bot/1.0\r\nAccept: application/rss+xml, application/xml;q=0.9, */*;q=0.8\r\n",
    ]
]);

$xmlString = @file_get_contents($rssUrl, false, $ctx);

if ($xmlString === false || $xmlString === '') {
    http_response_code(502);
    echo "error: fetch_failed\n";
    echo "rss_url: {$rssUrl}\n";
    exit;
}

libxml_use_internal_errors(true);
$xml = simplexml_load_string($xmlString);

if ($xml === false) {
    http_response_code(502);
    echo "error: invalid_xml\n";
    echo "rss_url: {$rssUrl}\n";
    foreach (libxml_get_errors() as $e) {
        $msg = trim($e->message);
        if ($msg !== '') echo "xml_error: {$msg}\n";
    }
    exit;
}

echo "status: ok\n";
echo "category: {$category}\n";
echo "page: {$page}\n";
echo "rss_url: {$rssUrl}\n\n";

$items = [];
if (isset($xml->channel->item)) {
    foreach ($xml->channel->item as $item) {
        $title = trim((string)$item->title);
        if ($title !== '') $items[] = $title;
        if (count($items) >= 10) break;
    }
}

echo "items_preview_count: " . count($items) . "\n";
for ($i = 0; $i < count($items); $i++) {
    $n = $i + 1;
    echo "{$n}. {$items[$i]}\n";
}