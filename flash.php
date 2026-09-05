<?php

header('Content-Type: application/json; charset=utf-8');

$limit = (int)$_GET['limit'];

function verify_swf(string $url): array {
    if (!function_exists('curl_init')) return ['', "curl_missing"];

    $url = str_replace(' ', '%20', trim($url));
    $url = preg_replace('#^http://#i', 'https://', $url);

    if (!filter_var($url, FILTER_VALIDATE_URL)) return ['', "invalid_url"];

    $path = parse_url($url, PHP_URL_PATH);
    if (!is_string($path) || !preg_match('/\.swf$/i', $path)) return ['', "not_direct_swf"];

    $headers = [];
    $signature = '';
    $stoppedAfterSignature = false;
    $ch = curl_init($url);
    if ($ch === false) return ['', "curl_init_failed"];

    curl_setopt_array($ch, [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RANGE => '0-2',
        CURLOPT_USERAGENT => 'g55-flash-bot/1.0',
        CURLOPT_HTTPHEADER => [
            'Accept: application/x-shockwave-flash,*/*;q=0.8',
            'Origin: https://g55.co',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HEADERFUNCTION => function($ch, $line) use (&$headers) {
            $len = strlen($line);

            if (preg_match('/^HTTP\//i', $line)) {
                $headers = [];
            } elseif (($pos = strpos($line, ':')) !== false) {
                $name = strtolower(trim(substr($line, 0, $pos)));
                $headers[$name] = trim(substr($line, $pos + 1));
            }

            return $len;
        },
        CURLOPT_WRITEFUNCTION => function($ch, $chunk) use (&$signature, &$stoppedAfterSignature) {
            $needed = 3 - strlen($signature);
            if ($needed > 0) $signature .= substr($chunk, 0, $needed);

            if (strlen($signature) === 3 && strlen($chunk) > $needed) {
                $stoppedAfterSignature = true;
                return 0;
            }

            return strlen($chunk);
        },
    ]);

    $ok = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($ok === false && !$stoppedAfterSignature) return ['', "curl_error:" . $curlError];
    if (!in_array($code, [200, 206], true)) return ['', "http_status:" . $code];
    if (!in_array($signature, ['FWS', 'CWS', 'ZWS'], true)) return ['', "invalid_swf_signature"];

    $cors = $headers['access-control-allow-origin'] ?? '';
    if ($cors !== '*' && strcasecmp($cors, 'https://g55.co') !== 0) return ['', "cors_denied"];

    if (!is_string($finalUrl) || strtolower((string)parse_url($finalUrl, PHP_URL_SCHEME)) !== 'https') {
        return ['', "https_required"];
    }

    return [$finalUrl, "ok"];
}

function is_permanent_swf_failure(string $status): bool {
    if (in_array($status, [
        'invalid_url',
        'not_direct_swf',
        'invalid_swf_signature',
        'cors_denied',
        'https_required',
    ], true)) {
        return true;
    }

    if (preg_match('/^http_status:(\d{3})$/', $status, $matches)) {
        $code = (int)$matches[1];
        return $code >= 400 && $code < 500 && !in_array($code, [408, 425, 429], true);
    }

    return false;
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

function game_shard(string $id): string {
    return strtolower(substr($id, 0, 2));
}

function game_shard_file(string $gamesDir, string $id): string {
    return rtrim($gamesDir, '/') . '/' . game_shard($id) . '.json';
}

function http_get_bytes(string $url, int $timeoutSeconds = 60, int $maxBytes = 256000000): array {
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
        CURLOPT_USERAGENT => 'g55-flash-bot/1.0',
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

function normalize_creator_value($value): string {
    if (is_array($value)) {
        return trim((string)($value['name'] ?? ''));
    }
    return trim((string)$value);
}

function image_from_bytes(string $bytes): array {
    if (!function_exists('imagecreatefromstring')) return [null, "gd_missing"];
    $im = @imagecreatefromstring($bytes);
    if ($im === false) return [null, "image_decode_failed"];
    return [$im, "ok"];
}

function resize_to_png($srcIm, int $dstW, int $dstH, string $outPath): array {
    $srcW = imagesx($srcIm);
    $srcH = imagesy($srcIm);

    if ($srcW <= 0 || $srcH <= 0) return [false, "bad_source_dimensions"];

    $dstIm = imagecreatetruecolor($dstW, $dstH);
    if ($dstIm === false) return [false, "dst_create_failed"];

    imagealphablending($dstIm, false);
    imagesavealpha($dstIm, true);
    $transparent = imagecolorallocatealpha($dstIm, 0, 0, 0, 127);
    imagefilledrectangle($dstIm, 0, 0, $dstW, $dstH, $transparent);

    $ok = imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
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

function atomic_write_json(string $path, array $data): array {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $json = str_replace("    ", "", $json);
    $json = preg_replace_callback('/"categories": \[\n((?:"(?:\\\\.|[^"\\\\])*",?\n)*)\]/', fn($m) => '"categories": [' . str_replace("\n", ' ', trim($m[1])) . ']', $json);

    if (!is_string($json)) return [false, "json_encode_failed"];

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

function merge_failed_checks(string $cacheFile, array $newFailures): array {
    if ($newFailures === []) return [true, "unchanged"];

    $lockFp = @fopen($cacheFile . '.lock', 'c+');
    if ($lockFp === false) return [false, "lock_open_failed"];

    if (!@flock($lockFp, LOCK_EX)) {
        @fclose($lockFp);
        return [false, "lock_failed"];
    }

    list($failedChecks, $status) = read_json_file($cacheFile);
    if ($status === "file_not_found") {
        $failedChecks = [];
    } elseif (!is_array($failedChecks)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return [false, $status];
    }

    foreach ($newFailures as $id => $reason) {
        $failedChecks[$id] = $reason;
    }

    list($okWrite, $writeStatus) = atomic_write_json($cacheFile, $failedChecks);

    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);

    if (!$okWrite) return [false, $writeStatus];
    return [true, "ok"];
}

function append_pages_to_shard(string $shardFile, array $newPages): array {
    $dir = dirname($shardFile);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) return [false, "mkdir_failed", 0, 0];
    }

    $lockPath = $shardFile . '.lock';
    $lockFp = @fopen($lockPath, 'c+');
    if ($lockFp === false) return [false, "lock_open_failed", 0, 0];

    if (!@flock($lockFp, LOCK_EX)) {
        @fclose($lockFp);
        return [false, "lock_failed", 0, 0];
    }

    list($shardJson, $st) = read_json_file($shardFile);
    if ($st === "file_not_found") {
        $shardJson = ["pages" => []];
    } elseif (!is_array($shardJson)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return [false, $st, 0, 0];
    }

    $pages = extract_pages_array($shardJson);
    $existingIds = pages_to_id_set($pages);

    $filtered = [];
    foreach ($newPages as $p) {
        if (!is_array($p)) continue;
        $id = isset($p['id']) ? trim((string)$p['id']) : '';
        if ($id === '' || isset($existingIds[$id])) continue;
        $filtered[] = $p;
        $existingIds[$id] = true;
    }

    $appended = count($filtered);
    if ($appended > 0) $pages = array_merge($pages, $filtered);

    $shardJson = ["pages" => $pages];
    list($okWrite, $stWrite) = atomic_write_json($shardFile, $shardJson);

    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);

    if (!$okWrite) return [false, $stWrite, $appended, count($pages)];
    return [true, "ok", $appended, count($pages)];
}

function update_creator_in_shard(string $shardFile, string $id, string $creator): array {
    $lockPath = $shardFile . '.lock';
    $lockFp = @fopen($lockPath, 'c+');
    if ($lockFp === false) return [false, "lock_open_failed"];

    if (!@flock($lockFp, LOCK_EX)) {
        @fclose($lockFp);
        return [false, "lock_failed"];
    }

    list($shardJson, $st) = read_json_file($shardFile);
    if (!is_array($shardJson)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return [false, $st];
    }

    $pages = extract_pages_array($shardJson);
    $found = false;
    $changed = false;

    foreach ($pages as &$page) {
        if (!is_array($page) || ($page['id'] ?? '') !== $id) continue;
        $found = true;
        if (normalize_creator_value($page['creator'] ?? '') === '') {
            $page['creator'] = $creator;
            $changed = true;
        }
        break;
    }
    unset($page);

    if (!$found) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return [false, "game_not_found"];
    }

    if (!$changed) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        return [true, "creator_already_present"];
    }

    $shardJson = ["pages" => $pages];
    list($okWrite, $stWrite) = atomic_write_json($shardFile, $shardJson);

    @flock($lockFp, LOCK_UN);
    @fclose($lockFp);

    if (!$okWrite) return [false, $stWrite];
    return [true, "creator_updated"];
}

$sourceUrl = 'https://db-api.unstable.life/search'
    . '?platform=Flash'
    . '&library=arcade'
    . '&filter=true'
    . '&fields=id,title,publisher,launchCommand'
    . '&limit=' . $limit;

$ctx = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 20,
        'header' => "User-Agent: g55-flash-bot/1.0\r\nAccept: application/json, */*;q=0.8\r\n",
    ]
]);

$body = @file_get_contents($sourceUrl, false, $ctx);

if ($body === false || $body === '') {
    http_response_code(502);
    echo json_encode([
        "ok" => false,
        "error" => "fetch_failed",
        "source_url" => $sourceUrl
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
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

if (!array_is_list($data)) {
    http_response_code(502);
    echo json_encode([
        "ok" => false,
        "error" => "invalid_source_shape",
        "source_url" => $sourceUrl
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$items = $data;

$gamesDir = __DIR__ . '/games';
$cdnDir = '/var/www/webroot/cdn';
$failedCacheFile = __DIR__ . '/flash_failed.json';
$thumbW = 170;
$thumbH = 128;

list($failedChecks, $failedCacheReadStatus) = read_json_file($failedCacheFile);
if ($failedCacheReadStatus === "file_not_found") {
    $failedChecks = [];
} elseif (!is_array($failedChecks)) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $failedCacheReadStatus,
        "failure_cache_file" => $failedCacheFile
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$seenIdsInRun = [];
$existingIdsByShard = [];
$existingCreatorsByShard = [];
$newFailedChecks = [];
$publishPages = [];
$created = 0;
$skippedExistingId = 0;
$skippedExistingThumb = 0;
$skippedCachedFailure = 0;
$creatorRepairAttempts = 0;
$creatorsRepaired = 0;
$skippedInvalidSwf = 0;
$skippedMissingScreenshot = 0;
$errors = 0;
$results = [];

foreach ($items as $item) {
    if (!is_array($item)) continue;

    $title = trim($item['title']);
    if ($title === '') continue;

    $flashpointId = strtolower(trim($item['id']));
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $flashpointId)) continue;

    $id = str_replace('-', '', $flashpointId);
    $creator = trim($item['publisher']);

    if (isset($seenIdsInRun[$id])) continue;
    $seenIdsInRun[$id] = true;

    $shard = game_shard($id);
    $shardFile = game_shard_file($gamesDir, $id);

    if (!isset($existingIdsByShard[$shard])) {
        list($shardJson, $shardReadStatus) = read_json_file($shardFile);

        if ($shardReadStatus === "file_not_found") {
            $existingIdsByShard[$shard] = [];
            $existingCreatorsByShard[$shard] = [];
        } elseif (is_array($shardJson)) {
            $shardPages = extract_pages_array($shardJson);
            $existingIdsByShard[$shard] = pages_to_id_set($shardPages);
            $existingCreatorsByShard[$shard] = [];

            foreach ($shardPages as $existingPage) {
                if (!is_array($existingPage)) continue;
                $existingId = trim((string)($existingPage['id'] ?? ''));
                if ($existingId === '') continue;
                $existingCreatorsByShard[$shard][$existingId] = normalize_creator_value($existingPage['creator'] ?? '');
            }
        } else {
            $errors++;
            $results[] = [
                "id" => $id,
                "status" => "error",
                "error" => $shardReadStatus,
                "shard" => $shard
            ];
            continue;
        }
    }

    if (isset($existingIdsByShard[$shard][$id])) {
        $existingCreator = $existingCreatorsByShard[$shard][$id] ?? '';

        if ($existingCreator !== '' || $creator === '') {
            $skippedExistingId++;
            continue;
        }

        $creatorRepairAttempts++;
        list($repairOk, $repairStatus) = update_creator_in_shard($shardFile, $id, $creator);

        if ($repairOk) {
            if ($repairStatus === "creator_updated") $creatorsRepaired++;
            $existingCreatorsByShard[$shard][$id] = $creator;
        } else {
            $errors++;
        }

        $results[] = [
            "id" => $id,
            "status" => $repairOk ? $repairStatus : "creator_repair_write_failed",
            "creator" => $creator,
            "repair_status" => $repairStatus,
            "shard" => $shard
        ];
        continue;
    }

    if (isset($failedChecks[$flashpointId])) {
        $skippedCachedFailure++;
        $results[] = [
            "id" => $id,
            "status" => "cached_skip",
            "error" => $failedChecks[$flashpointId]
        ];
        continue;
    }

    $swfUrl = trim($item['launchCommand']);
    list($iframe, $swfStatus) = verify_swf($swfUrl);

    if ($iframe === '') {
        $skippedInvalidSwf++;
        if (is_permanent_swf_failure($swfStatus)) {
            $failedChecks[$flashpointId] = $swfStatus;
            $newFailedChecks[$flashpointId] = $swfStatus;
        }
        $results[] = [
            "id" => $id,
            "status" => "skipped",
            "error" => $swfStatus,
            "swf" => $swfUrl
        ];
        continue;
    }

    $categories = ['Flash'];
    $outPath = rtrim($cdnDir, '/') . '/' . $id . '.png';
    $thumbnailExists = is_file($outPath) && filesize($outPath) > 0;
    $assetUrl = 'https://db-api.unstable.life/screenshot?id=' . rawurlencode($flashpointId);

    if ($thumbnailExists) {
        $skippedExistingThumb++;
    } else {
        list($bytes, $st) = http_get_bytes($assetUrl);
        if ($bytes === null) {
            $errors++;
            $results[] = [
                "id" => $id,
                "status" => "error",
                "error" => $st,
                "asset" => $assetUrl
            ];
            continue;
        }

        if (hash('sha256', $bytes) === 'd13bdf73830bf70468a562c4d5be78ce05d598b811d0c8dc19550ffbd38b8a6b') {
            $skippedMissingScreenshot++;
            $failedChecks[$flashpointId] = 'missing_screenshot';
            $newFailedChecks[$flashpointId] = 'missing_screenshot';
            $results[] = [
                "id" => $id,
                "status" => "skipped",
                "error" => "missing_screenshot",
                "asset" => $assetUrl
            ];
            continue;
        }

        list($srcIm, $st2) = image_from_bytes($bytes);
        if ($srcIm === null) {
            $errors++;
            $results[] = [
                "id" => $id,
                "status" => "error",
                "error" => $st2,
                "asset" => $assetUrl
            ];
            continue;
        }

        list($okSave, $st3) = resize_to_png($srcIm, $thumbW, $thumbH, $outPath);
        imagedestroy($srcIm);

        if (!$okSave) {
            $errors++;
            $results[] = [
                "id" => $id,
                "status" => "error",
                "error" => $st3,
                "thumb" => $outPath
            ];
            continue;
        }

        $created++;
    }

    $publishPages[] = [
        "id" => $id,
        "title" => $title,
        "iframe" => $iframe,
        "categories" => $categories,
        "creator" => $creator
    ];

    $existingIdsByShard[$shard][$id] = true;

    $results[] = [
        "id" => $id,
        "status" => $thumbnailExists ? "existing_thumbnail" : "created_thumbnail",
        "thumb" => $outPath,
        "asset" => $assetUrl,
        "shard" => $shard,
        "creator" => $creator,
        "swf_status" => $swfStatus
    ];
}

list($failedCacheOk, $failedCacheWriteStatus) = merge_failed_checks($failedCacheFile, $newFailedChecks);
if (!$failedCacheOk) $errors++;

$pagesByShard = [];
foreach ($publishPages as $p) {
    $shard = game_shard($p['id']);
    $pagesByShard[$shard][] = $p;
}

$appendOk = true;
$appendedCount = 0;
$shardResults = [];

foreach ($pagesByShard as $shard => $pages) {
    $shardFile = rtrim($gamesDir, '/') . '/' . $shard . '.json';
    list($ok, $status, $appended, $total) = append_pages_to_shard($shardFile, $pages);

    if (!$ok) $appendOk = false;
    $appendedCount += $appended;

    $shardResults[$shard] = [
        "status" => $status,
        "appended" => $appended,
        "total" => $total
    ];
}

if (!$appendOk || !$failedCacheOk) http_response_code(502);

$appendStatus = $appendOk ? "ok" : "partial_failure";
$ok = $appendOk && $failedCacheOk;

echo json_encode([
    "ok" => $ok,
    "limit" => $limit,
    "source_url" => $sourceUrl,
    "games_dir" => $gamesDir,
    "shard_scheme" => "first_2_id_chars",
    "shards_touched" => count($pagesByShard),
    "thumbnail_size" => [$thumbW, $thumbH],
    "cdn_dir" => $cdnDir,
    "created_thumbnails" => $created,
    "skipped_existing_id" => $skippedExistingId,
    "existing_thumbnails_reused" => $skippedExistingThumb,
    "skipped_cached_failure" => $skippedCachedFailure,
    "creator_repair_attempts" => $creatorRepairAttempts,
    "creators_repaired" => $creatorsRepaired,
    "skipped_invalid_swf" => $skippedInvalidSwf,
    "skipped_missing_screenshot" => $skippedMissingScreenshot,
    "failure_cache_file" => $failedCacheFile,
    "failure_cache_status" => $failedCacheWriteStatus,
    "failure_cache_entries" => count($failedChecks),
    "new_failures_cached" => count($newFailedChecks),
    "errors" => $errors,
    "candidates_for_publishing_count" => count($publishPages),
    "append_status" => $appendStatus,
    "appended_count" => $appendedCount,
    "shards" => $shardResults,
    "pages" => $publishPages,
    "thumbnail_results" => $results
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
