<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['c']) || !isset($_GET['p'])) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "missing_parameters",
        "usage" => "gd.php?c=CATEGORY_NAME&p=PAGE_NUMBER&type=categories|tags"
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$categoryRaw = trim((string)$_GET['c']);
$categoryRaw = strtolower($categoryRaw);
$categoryMap = [
'tower'      => 'tower-defense',
'defence'    => 'tower-defense',
'amongus'    => 'among-us',
'2players'   => '2-player',
'cards'      => 'card',
'jewels'     => 'bejeweled',
'squidgame'  => 'squid-game',
'guns'       => 'gun',
'mahjong & connect' => 'mahjong',
'racing & driving'  => 'racing',
];
$category = $categoryMap[$categoryRaw] ?? $categoryRaw;
$category = str_replace(' ', '-', $category);
$category = ltrim($category, '.');
$page     = (int)$_GET['p'];

$type = isset($_GET['type']) ? trim((string)$_GET['type']) : 'categories';
if (!in_array($type, ['categories', 'tags'], true)) {
    http_response_code(400);
    echo json_encode([
        "ok" => false,
        "error" => "invalid_type",
        "allowed" => ["categories", "tags"]
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($category === '' || !preg_match('/^[a-z0-9\-]+$/i', $category)) {
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
    if (!empty($item['Url']) && is_string($item['Url'])) return trim($item['Url']);
    if (!empty($item['Md5']) && is_string($item['Md5'])) {
        $md5 = trim($item['Md5']);
        if ($md5 !== '') return "https://html5.gamedistribution.com/" . $md5 . "/";
    }
    return '';
}

function pick_asset_512x384(array $item): string {
    if (empty($item['Asset']) || !is_array($item['Asset'])) return '';
    $assets = $item['Asset'];

    foreach ($assets as $a) {
        if (!is_string($a)) continue;
        if (strpos($a, '512x384') !== false) return trim($a);
    }

    foreach ($assets as $a) {
        if (is_string($a) && trim($a) !== '') return trim($a);
    }

    return '';
}

function read_json_file(string $path): array {
    if (!is_file($path)) return [null, "file_not_found"];
    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') return [null, "read_failed"];
    $json = json_decode($raw, true);
    if (!is_array($json)) return [null, "invalid_json"];
    return [$json, "ok"];
}

function extract_pages_array(array $json): array {
    if (isset($json['pages']) && is_array($json['pages'])) return $json['pages'];
    if (isset($json['items']) && is_array($json['items'])) return $json['items'];
    if (array_is_list($json)) return $json;
    return [];
}

function pages_to_id_set(array $pages): array {
    $ids = [];
    foreach ($pages as $p) {
        if (!is_array($p)) continue;
        $id = isset($p['id']) ? trim((string)$p['id']) : '';
        if ($id !== '') $ids[$id] = true;
    }
    return $ids;
}

function http_get_bytes(string $url, int $timeoutSeconds = 20, int $maxBytes = 8000000): array {
    if (!function_exists('curl_init')) return [null, "curl_missing"];

    $ch = curl_init($url);
    if ($ch === false) return [null, "curl_init_failed"];

    $data = '';
    $err = null;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'g55-gd-bot/1.0',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_WRITEFUNCTION => function($ch, $chunk) use (&$data, &$err, $maxBytes) {
            $len = strlen($chunk);
            if (strlen($data) + $len > $maxBytes) {
                $err = "image_too_large";
                return 0;
            }
            $data .= $chunk;
            return $len;
        },
    ]);

    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    if ($ok === false) {
        $e = curl_error($ch);
        curl_close($ch);
        return [null, $err ? $err : ("curl_error:" . $e)];
    }

    curl_close($ch);

    if ($code < 200 || $code >= 300) return [null, "http_status:" . $code];
    if ($data === '') return [null, "empty_body"];

    return [$data, "ok"];
}

function image_from_bytes(string $bytes): array {
    if (!function_exists('imagecreatefromstring')) return [null, "gd_missing"];
    $im = @imagecreatefromstring($bytes);
    if ($im === false) return [null, "image_decode_failed"];
    return [$im, "ok"];
}

function resize_cover_to_png($srcIm, int $dstW, int $dstH, string $outPath): array {
    $srcW = imagesx($srcIm);
    $srcH = imagesy($srcIm);
    if ($srcW <= 0 || $srcH <= 0) return [false, "bad_source_dimensions"];

    $srcAspect = $srcW / $srcH;
    $dstAspect = $dstW / $dstH;

    if ($srcAspect > $dstAspect) {
        $cropH = $srcH;
        $cropW = (int)round($srcH * $dstAspect);
        $srcX = (int)floor(($srcW - $cropW) / 2);
        $srcY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int)round($srcW / $dstAspect);
        $srcX = 0;
        $srcY = (int)floor(($srcH - $cropH) / 2);
    }

    $dstIm = imagecreatetruecolor($dstW, $dstH);
    if ($dstIm === false) return [false, "dst_create_failed"];

    imagealphablending($dstIm, false);
    imagesavealpha($dstIm, true);
    $transparent = imagecolorallocatealpha($dstIm, 0, 0, 0, 127);
    imagefilledrectangle($dstIm, 0, 0, $dstW, $dstH, $transparent);

    $ok = imagecopyresampled($dstIm, $srcIm, 0, 0, $srcX, $srcY, $dstW, $dstH, $cropW, $cropH);
    if (!$ok) {
        imagedestroy($dstIm);
        return [false, "resample_failed"];
    }

    $dir = dirname($outPath);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            imagedestroy($dstIm);
            return [false, "mkdir_failed"];
        }
    }

    $saved = @imagepng($dstIm, $outPath, 6);
    imagedestroy($dstIm);

    if (!$saved) return [false, "png_save_failed"];
    return [true, "ok"];
}

function read_pool_lines(string $path): array {
    if (!is_file($path)) return [];
    $raw = (string)@file_get_contents($path);
    if ($raw === '') return [];

    $lines = preg_split("/\r\n|\n|\r/", $raw);
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if ($line[0] === '#') continue;
        $out[] = $line;
    }
    return $out;
}

function next_seed(int &$seed): int {
    $seed = (int)(($seed * 1103515245 + 12345) & 0x7fffffff);
    return $seed;
}

function pick_one_stable(array $arr, int &$seed): string {
    $n = count($arr);
    if ($n === 0) return '';
    $idx = next_seed($seed) % $n;
    return (string)$arr[$idx];
}

function load_gd_pools(string $category): array {
    $base = __DIR__ . '/categories';
    $app = __DIR__ . '/app';

    $p = [];
    $p['openers'] = read_pool_lines($app . '/common/openers.txt');
    $p['value_props'] = read_pool_lines($app . '/common/value_props.txt');
    $p['cta'] = read_pool_lines($app . '/common/cta.txt');
    $p['usage_templates'] = read_pool_lines($app . '/common/usage_templates.txt');

    $catDir = $base . '/' . $category;
    $p['modes'] = read_pool_lines($catDir . '/modes.txt');
    $p['skills'] = read_pool_lines($catDir . '/skills.txt');
    $p['adjectives'] = read_pool_lines($catDir . '/adjectives.txt');

    return $p;
}

function normalize_category_label(string $category): string {
    $c = strtolower($category);
    $c = str_replace('_', ' ', $c);
    return $c;
}

function ensure_sentence(string $s): string {
    $s = trim($s);
    if ($s === '') return '';
    if (!preg_match('/[.!?]$/', $s)) $s .= '.';
    return $s;
}

function generate_gd_description(string $category, string $title, string $id): string {
    $pools = load_gd_pools($category);

    $seed = (int)(crc32($category . '|' . $id) & 0x7fffffff);
    $catLabel = normalize_category_label($category);

    $patterns = [
        ['intro', 'usage', 'ease', 'benefit'],
        ['intro', 'usage', 'benefit', 'ease'],
        ['intro', 'ease', 'usage', 'benefit'],
        ['intro', 'ease', 'benefit', 'usage'],
        ['intro', 'benefit', 'usage', 'ease'],
        ['intro', 'benefit', 'ease', 'usage'],
    ];

    $patternIndex = 0;
    if (count($patterns) > 0) $patternIndex = next_seed($seed) % count($patterns);
    $pattern = $patterns[$patternIndex];

    $adj = pick_one_stable($pools['adjectives'], $seed);

    $mode = pick_one_stable($pools['modes'], $seed);
    $skill = pick_one_stable($pools['skills'], $seed);

    $opener = pick_one_stable($pools['openers'], $seed);
    $vp = pick_one_stable($pools['value_props'], $seed);
    $cta = pick_one_stable($pools['cta'], $seed);

    $intro = ensure_sentence($opener);

    $usageTpl = pick_one_stable($pools['usage_templates'], $seed);
    $usageRaw = str_replace(['{mode}', '{skill}'], [$mode, $skill], $usageTpl);
    $usage = ensure_sentence($usageRaw);

    $ease = ensure_sentence($vp);
    $benefit = ensure_sentence($cta);

    $parts = [
        'intro' => $intro,
        'usage' => $usage,
        'ease' => $ease,
        'benefit' => $benefit,
    ];

    $out = [];
    foreach ($pattern as $key) {
        if (!isset($parts[$key])) continue;
        $v = trim((string)$parts[$key]);
        if ($v === '') continue;
        $out[] = $v;
    }

    $text = trim(implode(' ', $out));

    $text = str_replace(
        ['{title}', '{category}', '{adj}'],
        [$title, $catLabel, $adj],
        $text
    );

    $text = preg_replace('/\s+/', ' ', $text);

    return $text;
}

function atomic_write_json(string $path, array $data): array {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($json)) return [false, "json_encode_failed"];

    $json .= "\n";

    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) return [false, "mkdir_failed"];
    }

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    $ok = @file_put_contents($tmp, $json, LOCK_EX);
    if ($ok === false) return [false, "write_tmp_failed"];

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return [false, "rename_failed"];
    }

    return [true, "ok"];
}

function append_pages_with_lock_top(string $categoryFile, array $newPages): array {
    $lockPath = $categoryFile . '.lock';
    $lockFp = @fopen($lockPath, 'c+');
    if ($lockFp === false) return [false, "lock_open_failed", 0, 0];

    $locked = @flock($lockFp, LOCK_EX);
    if (!$locked) {
        @fclose($lockFp);
        return [false, "lock_failed", 0, 0];
    }

    list($catJson, $st) = read_json_file($categoryFile);

    if (!is_array($catJson)) {
        $catJson = ["pages" => []];
    }

    $pages = extract_pages_array($catJson);
    $existingIds = pages_to_id_set($pages);

    $filtered = [];
    foreach ($newPages as $p) {
        if (!is_array($p)) continue;
        $id = isset($p['id']) ? trim((string)$p['id']) : '';
        if ($id === '') continue;
        if (isset($existingIds[$id])) continue;

        $filtered[] = $p;
        $existingIds[$id] = true;
    }

    $appended = count($filtered);
    if ($appended > 0) $pages = array_merge($filtered, $pages);

    if (isset($catJson['pages']) && is_array($catJson['pages'])) {
        $catJson['pages'] = $pages;
    } elseif (isset($catJson['items']) && is_array($catJson['items'])) {
        $catJson['items'] = $pages;
    } elseif (array_is_list($catJson)) {
        $catJson = $pages;
    } else {
        $catJson['pages'] = $pages;
    }

    list($okWrite, $stWrite) = atomic_write_json($categoryFile, $catJson);

    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);

    if (!$okWrite) return [false, $stWrite, $appended, count($pages)];
    return [true, "ok", $appended, count($pages)];
}

$sourceBase = 'https://catalog.api.gamedistribution.com/api/v2.0/rss/All/';
$sourceUrl  = $sourceBase . '?' . $type . '=' . rawurlencode($categoryRaw) . '&page=' . $page;

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
list($categoryJson, $categoryReadStatus) = read_json_file($categoryFile);

$existingPages = [];
if (is_array($categoryJson)) $existingPages = extract_pages_array($categoryJson);
$existingIds = pages_to_id_set($existingPages);

$cdnDir = '/var/www/webroot/cdn';
$thumbW = 170;
$thumbH = 128;

$seenIdsInRun = [];
$publishPages = [];

$created = 0;
$skippedExistingId = 0;
$skippedExistingThumb = 0;
$errors = 0;

$results = [];

foreach ($data as $item) {
    if (!is_array($item)) continue;

    $title = isset($item['Title']) ? trim((string)$item['Title']) : '';
    if ($title === '') continue;

    $id = make_id_from_title($title);
    if ($id === '') continue;

    if (isset($seenIdsInRun[$id])) continue;
    $seenIdsInRun[$id] = true;

    if (isset($existingIds[$id])) {
        $skippedExistingId++;
        continue;
    }

    $outPath = rtrim($cdnDir, '/') . '/' . $id . '.png';
    if (is_file($outPath) && filesize($outPath) > 0) {
        $skippedExistingThumb++;
        continue;
    }

    $iframe = pick_iframe($item);
    if ($iframe === '') continue;

    $description = generate_gd_description($category, $title, $id);
    if (trim($description) === '') continue;

    $assetUrl = pick_asset_512x384($item);
    if ($assetUrl === '') {
        $errors++;
        $results[] = ["id" => $id, "status" => "error", "error" => "missing_asset_image"];
        continue;
    }

    list($bytes, $st) = http_get_bytes($assetUrl, 20, 8000000);
    if ($bytes === null) {
        $errors++;
        $results[] = ["id" => $id, "status" => "error", "error" => $st, "asset" => $assetUrl];
        continue;
    }

    list($srcIm, $st2) = image_from_bytes($bytes);
    if ($srcIm === null) {
        $errors++;
        $results[] = ["id" => $id, "status" => "error", "error" => $st2, "asset" => $assetUrl];
        continue;
    }

    list($okSave, $st3) = resize_cover_to_png($srcIm, $thumbW, $thumbH, $outPath);
    imagedestroy($srcIm);

    if (!$okSave) {
        $errors++;
        $results[] = ["id" => $id, "status" => "error", "error" => $st3, "thumb" => $outPath];
        continue;
    }

    $created++;

    $publishPages[] = [
        "id" => $id,
        "title" => $title,
        "iframe" => $iframe,
        "description" => $description
    ];

    $results[] = [
        "id" => $id,
        "status" => "created_thumbnail",
        "thumb" => $outPath,
        "asset" => $assetUrl
    ];
}

list($appendOk, $appendStatus, $appendedCount, $totalAfter) = append_pages_with_lock_top($categoryFile, $publishPages);

if (!$appendOk) http_response_code(502);

echo json_encode([
    "ok" => $appendOk ? true : false,
    "category" => $category,
    "page" => $page,
    "type" => $type,
    "source_url" => $sourceUrl,
    "category_file" => $categoryFile,
    "category_read_status" => $categoryReadStatus,
    "existing_ids_count" => count($existingIds),
    "thumbnail_size" => [$thumbW, $thumbH],
    "cdn_dir" => $cdnDir,
    "created_thumbnails" => $created,
    "skipped_existing_id" => $skippedExistingId,
    "skipped_existing_thumbnail" => $skippedExistingThumb,
    "errors" => $errors,
    "candidates_for_publishing_count" => count($publishPages),
    "append_status" => $appendStatus,
    "appended_count" => $appendedCount,
    "total_pages_after_append" => $totalAfter,
    "pages" => $publishPages,
    "thumbnail_results" => $results
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);