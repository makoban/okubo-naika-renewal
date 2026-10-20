<?php
if (!defined('OKUBO_CMS_LIBRARY_ONLY')) define('OKUBO_CMS_LIBRARY_ONLY', true);
require_once dirname(__DIR__) . '/published-content.php';

function okubo_cms_directory() {
    $test = PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli';
    return $test && getenv('OKUBO_CMS_DATA_DIR') ? getenv('OKUBO_CMS_DATA_DIR') : dirname(__DIR__, 2) . '/.okubo-cms-state';
}
function okubo_cms_escape($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function okubo_cms_atomic_write($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('保存先を作成できません。');
    $tmp = tempnam($dir, 'write-');
    if ($tmp === false) throw new RuntimeException('保存先を利用できません。');
    chmod($tmp, 0600);
    try {
        if (file_put_contents($tmp, $data) !== strlen($data) || !rename($tmp, $file)) throw new RuntimeException('保存に失敗しました。');
    } finally { if (is_file($tmp)) unlink($tmp); }
}
function okubo_cms_json($value) { return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR); }
function okubo_cms_current() { return okubo_published_current(); }
function okubo_cms_config() {
    $file = okubo_cms_directory() . '/config.json';
    if (!is_file($file)) {
        $seedFile = __DIR__ . '/credential-seed.php';
        if (!is_file($seedFile)) throw new RuntimeException('初期設定を確認してください。');
        $seed = require $seedFile;
        if (!is_array($seed) || !is_string($seed['username'] ?? null) || !is_string($seed['passwordHash'] ?? null)
            || !password_get_info($seed['passwordHash'])['algo']) throw new RuntimeException('初期設定を確認してください。');
        // Exclusive creation prevents a second first request from replacing credentials.
        $dir = okubo_cms_directory();
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('初期設定に失敗しました。');
        $handle = @fopen($file, 'x');
        if ($handle) { chmod($file, 0600); fwrite($handle, okubo_cms_json($seed)); fclose($handle); }
        @unlink($seedFile);
    }
    $config = json_decode(file_get_contents($file), true, 16, JSON_THROW_ON_ERROR);
    if (!is_string($config['username'] ?? null) || !is_string($config['passwordHash'] ?? null)) throw new RuntimeException('認証設定を確認してください。');
    return $config;
}
function okubo_cms_session() {
    header('Cache-Control: no-store, max-age=0');
    header('X-Robots-Tag: noindex, nofollow');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    // Basic form POSTs can acquire an opaque Origin under no-referrer.
    // Preserve same-origin context while withholding referrers from other sites.
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'");
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('OKUBO_CMS');
    session_set_cookie_params(['lifetime' => 0, 'path' => PHP_SAPI === 'cli-server' ? '/cms/' : '/admin/',
        'secure' => PHP_SAPI !== 'cli-server', 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
    if (isset($_SESSION['user']) && (time() - ($_SESSION['lastSeen'] ?? 0) > 1800 || time() - ($_SESSION['started'] ?? 0) > 28800)) {
        $_SESSION = []; session_regenerate_id(true);
    }
    if (isset($_SESSION['user'])) $_SESSION['lastSeen'] = time();
}
function okubo_cms_csrf() {
    if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function okubo_cms_valid_post($token) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $expected = PHP_SAPI === 'cli-server' ? 'http://' . $_SERVER['HTTP_HOST'] : 'https://www.okubo-naika-cl.com';
    return $_SERVER['REQUEST_METHOD'] === 'POST' && is_string($token) && hash_equals(okubo_cms_csrf(), $token)
        && ($origin === '' || $origin === $expected);
}
function okubo_cms_require_login($json = false) {
    if (isset($_SESSION['user'])) return;
    if ($json) okubo_cms_response(401, ['message' => 'ログインの有効時間が切れました。下書きは残っています。ログインし直してください。']);
    header('Location: ' . (PHP_SAPI === 'cli-server' ? '/cms/login.php' : '/admin/login.php'), true, 302); exit;
}
function okubo_cms_response($status, $value) {
    http_response_code($status); header('Content-Type: application/json; charset=UTF-8');
    echo okubo_cms_json($value); exit;
}
function okubo_cms_login($username, $password, $ip) {
    if (!is_string($username) || !is_string($password) || strlen($username) > 100 || strlen($password) > 200) return false;
    $config = okubo_cms_config();
    $dir = okubo_cms_directory() . '/attempts';
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('ログインを確認できません。');
    $file = $dir . '/' . hash('sha256', $ip) . '.json';
    $lock = fopen($file, 'c+'); chmod($file, 0600); flock($lock, LOCK_EX);
    try {
        $attempts = json_decode(stream_get_contents($lock), true) ?: ['start' => time(), 'count' => 0];
        if (time() - $attempts['start'] > 900) $attempts = ['start' => time(), 'count' => 0];
        if ($attempts['count'] >= 10) throw new RuntimeException('入力を続けて間違えたため、15分ほど待ってからお試しください。');
        $valid = password_verify($password, $config['passwordHash']) && hash_equals($config['username'], $username);
        if ($valid) $attempts = ['start' => time(), 'count' => 0]; else $attempts['count']++;
        rewind($lock); ftruncate($lock, 0); fwrite($lock, okubo_cms_json($attempts));
        return $valid;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
function okubo_cms_publish($payload, $username) {
    if (!is_array($payload) || !is_string($payload['baseRevision'] ?? null)) throw new InvalidArgumentException('公開データの形式が正しくありません。');
    $content = $payload['content'] ?? null;
    $normalized = ['topics' => [], 'fees' => []];
    if (!is_array($content)) throw new InvalidArgumentException('公開データを確認してください。');
    foreach ($normalized as $kind => $unused) {
        if (!isset($content[$kind]) || !is_array($content[$kind]) || count($content[$kind]) > ($kind === 'topics' ? 20 : 30)) throw new InvalidArgumentException('項目数を確認してください。');
        if ($content[$kind] !== [] && array_keys($content[$kind]) !== range(0, count($content[$kind]) - 1)) throw new InvalidArgumentException('項目の形式が正しくありません。');
        $keys = $kind === 'topics' ? ['id','status','date','label','title','body','link','linkText','tone','font'] : ['id','status','title','body'];
        foreach ($content[$kind] as $item) {
            if (!is_array($item)) throw new InvalidArgumentException('項目の形式が正しくありません。');
            $row = [];
            foreach ($keys as $key) { if (!is_string($item[$key] ?? null)) throw new InvalidArgumentException('入力内容を確認してください。'); $row[$key] = $item[$key]; }
            $normalized[$kind][] = $row;
        }
    }
    $doc = ['schemaVersion' => 1, 'revision' => hash('sha256', okubo_cms_json($normalized)),
        'publishedAt' => gmdate('Y-m-d\TH:i:s.000\Z'), 'content' => $normalized];
    if (!okubo_decode_document(okubo_cms_json($doc))) throw new InvalidArgumentException('文字数・表示設定・リンク先を確認してください。');
    $dir = okubo_cms_directory();
    if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('公開先を利用できません。');
    $lock = fopen($dir . '/publish.lock', 'c'); chmod($dir . '/publish.lock', 0600); flock($lock, LOCK_EX);
    try {
        $current = okubo_cms_current();
        if (!hash_equals($current['revision'], $payload['baseRevision'])) throw new LogicException('別の公開更新がありました。下書きを控えてから、公開中の内容を読み直してください。');
        if ($current['revision'] === $doc['revision']) return $current;
        okubo_cms_atomic_write($dir . '/backups/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json', okubo_cms_json($current));
        okubo_cms_atomic_write($dir . '/published.json', okubo_cms_json($doc));
        $audit = okubo_cms_json(['at' => $doc['publishedAt'], 'user' => $username, 'revision' => $doc['revision']]) . "\n";
        file_put_contents($dir . '/audit.jsonl', $audit, FILE_APPEND | LOCK_EX); chmod($dir . '/audit.jsonl', 0600);
        return $doc;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
