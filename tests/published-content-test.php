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
// Keep malformed-item tests independent of future legitimate empty publications.
if (count($initial['content']['topics']) === 0) {
    $initial['content']['topics'][] = [
        'id' => 'php-test-topic', 'date' => '2026-10-20', 'label' => 'お知らせ',
        'title' => 'Test fixture', 'body' => '', 'link' => '', 'linkText' => '',
        'tone' => 'normal', 'font' => 'gothic', 'status' => 'hide'
    ];
    $initial = document_with_body($initial, '');
}
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

require dirname(__DIR__) . '/cms/lib.php';
$directory = sys_get_temp_dir() . '/okubo-cms-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
putenv('OKUBO_CMS_DATA_DIR=' . $directory);
try {
    $new = document_with_body($initial, 'Approved customer publication');
    $payload = ['baseRevision' => okubo_cms_current()['revision'], 'content' => $new['content']];
    $published = okubo_cms_publish($payload, 'test-editor');
    check($published['revision'] === $new['revision'], 'Customer publication must persist');
    check(okubo_cms_current()['revision'] === $new['revision'], 'The public reader must use customer content');
    check(count(glob($directory . '/backups/*.json')) === 1, 'Previous publication must be backed up');
    try { okubo_cms_publish($payload, 'test-editor'); throw new RuntimeException('Stale update was accepted'); }
    catch (LogicException $expected) {}
    $payload['baseRevision'] = $published['revision'];
    $same = okubo_cms_publish($payload, 'test-editor');
    check($same['publishedAt'] === $published['publishedAt'], 'An identical save must be idempotent');
    $payload['content']['topics'][0]['link'] = 'javascript:alert(1)';
    try { okubo_cms_publish($payload, 'test-editor'); throw new RuntimeException('Unsafe content was accepted'); }
    catch (InvalidArgumentException $expected) {}
    file_put_contents($directory . '/published.json', '{broken');
    try { okubo_cms_current(); throw new LogicException('Corruption was ignored'); }
    catch (RuntimeException $expected) {}
    echo "PHP validation, atomic customer publication, backup, conflict protection, and corruption rejection: OK\n";
} finally { remove_test_directory($directory); }
