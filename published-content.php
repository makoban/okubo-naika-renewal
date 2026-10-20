<?php
// Public reader. Authenticated CMS publication is stored outside the web root.
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

function okubo_published_current() {
    $test = PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli';
    $directory = $test && getenv('OKUBO_CMS_DATA_DIR') ? getenv('OKUBO_CMS_DATA_DIR') : dirname(__DIR__) . '/.okubo-cms-state';
    $file = $directory . '/published.json';
    $doc = okubo_decode_document(@file_get_contents(is_file($file) ? $file : __DIR__ . '/published-content.json'));
    if (!$doc) throw new RuntimeException('Published content unavailable');
    return $doc;
}

if (!defined('OKUBO_CMS_LIBRARY_ONLY')) {
    header('Cache-Control: no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, nofollow');
    header('Access-Control-Allow-Origin: https://makoban.github.io');
    $format = ($_GET['format'] ?? '') === 'json' ? 'json' : 'js';
    header('Content-Type: application/' . ($format === 'json' ? 'json' : 'javascript') . '; charset=UTF-8');
    try {
        $doc = okubo_published_current();
        $meta = ['revision' => $doc['revision'], 'publishedAt' => $doc['publishedAt'], 'checkedAt' => time(), 'stale' => false];
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
