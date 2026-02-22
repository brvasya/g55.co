<?php
declare(strict_types=1);

if (!function_exists('generate_gd_description')) {

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
        $base = dirname(__DIR__) . '/categories';
        $app = dirname(__DIR__) . '/app';

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

        $seed = (int)(crc32('v2|' . $category . '|' . $id) & 0x7fffffff);
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
        if (count($patterns) > 0) {
            $patternIndex = next_seed($seed) % count($patterns);
        }

        $pattern = $patterns[$patternIndex];

        $adj = pick_one_stable($pools['adjectives'], $seed);
        $mode = pick_one_stable($pools['modes'], $seed);
        $skill = pick_one_stable($pools['skills'], $seed);

        $opener = pick_one_stable($pools['openers'], $seed);
        $usageTpl = pick_one_stable($pools['usage_templates'], $seed);
        $valueProp = pick_one_stable($pools['value_props'], $seed);
        $cta = pick_one_stable($pools['cta'], $seed);

        $intro = ensure_sentence($opener);

        $usageRaw = str_replace(['{mode}', '{skill}'], [$mode, $skill], $usageTpl);
        $usage = ensure_sentence($usageRaw);

        $ease = ensure_sentence($valueProp);
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
}