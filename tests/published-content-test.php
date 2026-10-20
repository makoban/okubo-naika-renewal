<?php
define('OKUBO_CMS_LIBRARY_ONLY', true);
require dirname(__DIR__) . '/published-content.php';

function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function document_with_body($doc, $body) {
    $doc['content']['topics'][0]['body'] = $body;
    $doc['revision'] = hash('sha256', json_encode($doc['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS));
    return $doc;
}
function remove_test_directory($directory) {
    foreach (glob($directory . '/*') as $file) {
        if (is_dir($file)) remove_test_directory($file);
        else unlink($file);
    }
    rmdir($directory);
}

$fallbackFile = dirname(__DIR__) . '/published-content.json';
$raw = file_get_contents($fallbackFile);
$initial = okubo_decode_document($raw);
check($initial !== null, 'The JavaScript-generated publication must validate in PHP');
$bad = $initial;
$bad['revision'] = [];
check(okubo_decode_document(json_encode($bad)) === null, 'Malformed metadata must not throw');
$bad = $initial;
$bad['content']['topics'][0]['body'] .= 'tampered';
check(okubo_decode_document(json_encode($bad)) === null, 'Content hash mismatch must be rejected');
$bad = $initial;
$bad['content']['topics'][0]['link'] = 'javascript:alert(1)';
check(okubo_decode_document(json_encode($bad)) === null, 'Unapproved URLs must be rejected');
$bad = $initial;
$bad['content']['topics'] = ['not-a-list' => $bad['content']['topics'][0]];
check(okubo_decode_document(json_encode($bad)) === null, 'Object-shaped item collections must be rejected');
$bad = $initial;
$bad['content']['topics'][0]['id'] = [];
check(okubo_decode_document(json_encode($bad)) === null, 'Malformed item IDs must not throw');
check(okubo_decode_document(str_repeat('x', OKUBO_CMS_MAX_BYTES + 1)) === null, 'Oversized responses must be rejected');
$unicode = document_with_body($initial, "日本語 / 😀 \u{2028} \u{2029}");
check(okubo_decode_document(json_encode($unicode)) !== null, 'Unicode and line terminators must remain intact');

$directory = sys_get_temp_dir() . '/okubo-cms-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
try {
    $new = document_with_body($initial, 'Approved test data');
    $fetches = 0;
    $fetcher = function () use (&$fetches, $new) { $fetches++; return json_encode($new); };
    $first = okubo_load_published($directory, $fallbackFile, 1000, $fetcher);
    check($first['document']['revision'] === $new['revision'], 'An approved update must be applied');
    check($first['validAt'] === 1000, 'Successful refresh time must be recorded');
    $cached = okubo_load_published($directory, $fallbackFile, 1010, $fetcher);
    check($fetches === 1 && $cached['document']['revision'] === $new['revision'], 'Fresh cache must avoid a remote call');

    $failed = okubo_load_published($directory, $fallbackFile, 1040, function () { return '{invalid'; });
    check($failed['document']['revision'] === $new['revision'], 'Remote corruption must preserve the last approved content');
    check($failed['validAt'] === 1000 && $failed['checkedAt'] === 1040, 'Failure and success times must remain distinct');
    okubo_load_published($directory, $fallbackFile, 1041, function () { throw new RuntimeException('Failure retry was not throttled'); });

    $lock = fopen($directory . '/refresh.lock', 'c');
    flock($lock, LOCK_EX);
    $busy = okubo_load_published($directory, $fallbackFile, 1100, function () { throw new RuntimeException('Concurrent refresh must not run'); });
    check($busy['document']['revision'] === $new['revision'], 'A concurrent reader must keep serving approved content');
    flock($lock, LOCK_UN);
    fclose($lock);

    file_put_contents($directory . '/published.json', '{broken cache');
    $recovered = okubo_load_published($directory, $fallbackFile, 1200, $fetcher);
    check($recovered['document']['revision'] === $new['revision'], 'Corrupt cache must recover');

    $empty = $directory . '/first-failure';
    $fallback = okubo_load_published($empty, $fallbackFile, 1300, function () { return null; });
    check($fallback['document']['revision'] === $initial['revision'], 'Initial network failure must serve the bundled approved publication');
    check($fallback['validAt'] === 0, 'Bundled fallback must not claim a successful remote sync');
    echo "PHP publication validation, cache, failure recovery, and concurrency: OK\n";
} finally {
    remove_test_directory($directory);
}
