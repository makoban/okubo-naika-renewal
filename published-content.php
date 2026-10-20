<?php
// Read-only publication bridge. Only this fixed, owner-controlled source is read.
const OKUBO_PUBLISHED_SOURCE = 'https://raw.githubusercontent.com/makoban/okubo-naika-renewal/main/published-content.json';
const OKUBO_CMS_CACHE_SECONDS = 30;
const OKUBO_CMS_MAX_BYTES = 600000;

function okubo_text_valid($value, $limit) {
    if (!is_string($value) || strpos($value, "\0") !== false || !preg_match('//u', $value)) return false;
    return preg_match_all('/./us', $value, $unused) <= $limit;
}

function okubo_decode_document($raw) {
    if (!is_string($raw) || strlen($raw) > OKUBO_CMS_MAX_BYTES) return null;
    $doc = json_decode($raw, true);
    if (!is_array($doc) || ($doc['schemaVersion'] ?? null) !== 1
        || !is_string($doc['revision'] ?? null) || !is_string($doc['publishedAt'] ?? null)
        || !preg_match('/^[a-f0-9]{64}$/D', $doc['revision'] ?? '')
        || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/D', $doc['publishedAt'] ?? '')) return null;
    $content = $doc['content'] ?? null;
    if (!is_array($content) || !isset($content['topics'], $content['fees'])
        || !is_array($content['topics']) || !is_array($content['fees'])
        || count($content['topics']) > 20 || count($content['fees']) > 30) return null;
    foreach (['topics', 'fees'] as $kind) {
        if ($content[$kind] !== [] && array_keys($content[$kind]) !== range(0, count($content[$kind]) - 1)) return null;
    }
    $links = ['', './index.html', './guide.html#hours', './access.html', './doctor.html', './guide.html', './emergency.html', './endoscopy.html', './pylori.html'];
    foreach (['topics', 'fees'] as $kind) {
        $ids = [];
        foreach ($content[$kind] as $item) {
            if (!is_array($item) || !is_string($item['id'] ?? null) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $item['id'])
                || isset($ids[$item['id']]) || !in_array($item['status'] ?? '', ['show', 'hide'], true)) return null;
            $ids[$item['id']] = true;
            $limits = $kind === 'topics'
                ? ['date' => 40, 'label' => 60, 'title' => 160, 'body' => 4000, 'linkText' => 100]
                : ['title' => 200, 'body' => 12000];
            foreach ($limits as $key => $limit) if (!okubo_text_valid($item[$key] ?? null, $limit)) return null;
            if ($kind === 'topics' && (!in_array($item['link'] ?? null, $links, true)
                || !in_array($item['tone'] ?? '', ['normal', 'important', 'soft'], true)
                || !in_array($item['font'] ?? '', ['gothic', 'mincho'], true))) return null;
        }
    }
    $canonical = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS);
    if ($canonical === false || !hash_equals($doc['revision'], hash('sha256', $canonical))) return null;
    return ['schemaVersion' => 1, 'revision' => $doc['revision'], 'publishedAt' => $doc['publishedAt'], 'content' => $content];
}

function okubo_fetch_document($now) {
    $url = OKUBO_PUBLISHED_SOURCE . '?v=' . (int) floor($now / OKUBO_CMS_CACHE_SECONDS);
    if (function_exists('curl_init')) {
        $body = '';
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_CONNECTTIMEOUT => 1, CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'OkuboClinicPublishedContent/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Cache-Control: no-cache'],
            CURLOPT_WRITEFUNCTION => function ($handle, $part) use (&$body) {
                if (strlen($body) + strlen($part) > OKUBO_CMS_MAX_BYTES) return 0;
                $body .= $part;
                return strlen($part);
            }]);
        $ok = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $ok !== false && $status === 200 ? $body : null;
    }
    $context = stream_context_create(['http' => ['timeout' => 3, 'follow_location' => 0,
        'header' => "Accept: application/json\r\nCache-Control: no-cache\r\nUser-Agent: OkuboClinicPublishedContent/1.0\r\n"],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $body = @file_get_contents($url, false, $context, 0, OKUBO_CMS_MAX_BYTES + 1);
    return is_string($body) && strlen($body) <= OKUBO_CMS_MAX_BYTES ? $body : null;
}

function okubo_read_cache($file) {
    $raw = @file_get_contents($file);
    $cache = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($cache) || !is_int($cache['checkedAt'] ?? null) || !is_int($cache['validAt'] ?? null)) return null;
    $doc = okubo_decode_document(json_encode($cache['document'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if (!$doc) return null;
    $cache['document'] = $doc;
    return $cache;
}

function okubo_load_published($directory, $fallbackFile, $now, $fetcher) {
    $fallback = okubo_decode_document(@file_get_contents($fallbackFile));
    if (!$fallback) throw new RuntimeException('No valid published fallback');
    $file = $directory . '/published.json';
    $cache = okubo_read_cache($file);
    $state = $cache ?: ['document' => $fallback, 'checkedAt' => 0, 'validAt' => 0];
    if ($cache && $now - $cache['checkedAt'] < OKUBO_CMS_CACHE_SECONDS) return $state;
    if (!is_dir($directory) && !@mkdir($directory, 0700, true)) return $state;
    $lock = @fopen($directory . '/refresh.lock', 'c');
    if (!$lock) return $state;
    @chmod($directory . '/refresh.lock', 0600);
    if (!flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return $state; }
    try {
        $current = okubo_read_cache($file);
        if ($current && $now - $current['checkedAt'] < OKUBO_CMS_CACHE_SECONDS) return $current;
        if ($current) $state = $current;
        $remote = okubo_decode_document(call_user_func($fetcher, $now));
        if ($remote) $state = ['document' => $remote, 'checkedAt' => $now, 'validAt' => $now];
        else $state['checkedAt'] = $now;
        $temporary = @tempnam($directory, 'published-');
        if ($temporary !== false) {
            @chmod($temporary, 0600);
            $bytes = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (@file_put_contents($temporary, $bytes) === false || !@rename($temporary, $file)) @unlink($temporary);
        }
        return $state;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

if (!defined('OKUBO_CMS_LIBRARY_ONLY')) {
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
    header('Access-Control-Allow-Origin: https://makoban.github.io');
    $format = ($_GET['format'] ?? '') === 'json' ? 'json' : 'js';
    header('Content-Type: application/' . ($format === 'json' ? 'json' : 'javascript') . '; charset=UTF-8');
    try {
        $state = okubo_load_published(dirname(__DIR__) . '/.okubo-cms-cache', __DIR__ . '/published-content.json', time(), 'okubo_fetch_document');
        $doc = $state['document'];
        $meta = ['revision' => $doc['revision'], 'publishedAt' => $doc['publishedAt'],
            'checkedAt' => $state['checkedAt'], 'stale' => $state['validAt'] !== $state['checkedAt'] || $state['validAt'] === 0];
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        if ($format === 'json') echo json_encode($doc + ['sync' => $meta], $flags);
        else echo 'window.OKUBO_REVIEW_DEFAULTS = ' . json_encode($doc['content'], $flags)
            . '; window.OKUBO_TOPICS = window.OKUBO_REVIEW_DEFAULTS.topics; window.OKUBO_REVIEW_SOURCE = "published"; window.OKUBO_PUBLISHED_META = '
            . json_encode($meta, $flags) . ';';
    } catch (Throwable $error) {
        http_response_code(503);
        echo $format === 'json' ? '{"error":"Published content unavailable"}' : '/* Published content unavailable */';
    }
}
