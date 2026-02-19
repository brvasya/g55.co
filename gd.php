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

function read_category_json(string $path): array {
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

$sourceBase = 'https://catalog.api.gamedistribution.com/api/v2.0/rss/All/';
$sourceUrl  = $sourceBase . '?categories=' . rawurlencode($category) . '&page=' . $page;

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
list($categoryJson, $categoryReadStatus) = read_category_json($categoryFile);

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

    $description = isset($item['Description']) ? trim((string)$item['Description']) : '';
    if ($description === '') $description = $title;

    $assetUrl = pick_asset_512x384($item);
    if ($assetUrl === '') {
        $errors++;
        $results[] = ["id" => $id, "status" => "error", "error" => "missing_asset_512x384"];
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

echo json_encode([
    "ok" => true,
    "category" => $category,
    "page" => $page,
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
    "pages" => $publishPages,
    "thumbnail_results" => $results
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);