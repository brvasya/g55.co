<?php

$sourceDir = '/var/www/webroot/html5';
$gamesDir  = __DIR__ . '/games';
$cdnDir    = '/var/www/webroot/cdn';

$existingIds = [];

function game_exists(string $gamesDir, string $id, array &$existingIds): bool {
    $shard = substr($id, 0, 2);

    if (!isset($existingIds[$shard])) {
        $shardFile = $gamesDir . '/' . $shard . '.json';
        $data = json_decode(file_get_contents($shardFile), true);

        $existingIds[$shard] = [];

        foreach ($data['pages'] as $page) {
            $existingIds[$shard][$page['id']] = true;
        }
    }

    return isset($existingIds[$shard][$id]);
}

function append_to_shard(string $gamesDir, array $game): void {
    $shardFile = $gamesDir . '/' . substr($game['id'], 0, 2) . '.json';
    $data = json_decode(file_get_contents($shardFile), true);

    array_unshift($data['pages'], $game);

    $json = json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_PRETTY_PRINT
    );

    $json = str_replace("    ", "", $json);
    $json = preg_replace_callback(
        '/"categories": \[\n((?:"(?:\\\\.|[^"\\\\])*",?\n)*)\]/',
        fn($m) => '"categories": [' . str_replace("\n", ' ', trim($m[1])) . ']',
        $json
    );

    file_put_contents($shardFile, $json);
}

foreach (glob($sourceDir . '/*', GLOB_ONLYDIR) as $dir) {
    $slug = basename($dir);
    $id   = md5($slug);

    if (game_exists($gamesDir, $id, $existingIds)) {
        continue;
    }

    $title = ucwords(str_replace('-', ' ', $slug));

    copy(
        $dir . '/thumb.png',
        $cdnDir . '/' . $id . '.png'
    );

    $game = [
        'id'         => $id,
        'title'      => $title,
        'iframe'     => 'https://html5.g55.co/' . $slug . '/',
        'categories' => ['Exclusive'],
        'creator'    => 'G55.CO'
    ];

    append_to_shard($gamesDir, $game);

    $existingIds[substr($id, 0, 2)][$id] = true;
}
