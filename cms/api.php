<?php
require __DIR__ . '/lib.php';
okubo_cms_session();
okubo_cms_require_login(true);
if (!okubo_cms_valid_post($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) okubo_cms_response(403, ['message' => '認証を確認できません。ページを再読み込みしてください。']);
if (($_SERVER['CONTENT_LENGTH'] ?? 0) > OKUBO_CMS_MAX_BYTES) okubo_cms_response(413, ['message' => '入力内容が多すぎます。']);
try {
    $raw = file_get_contents('php://input', false, null, 0, OKUBO_CMS_MAX_BYTES + 1);
    if (strlen($raw) > OKUBO_CMS_MAX_BYTES) okubo_cms_response(413, ['message' => '入力内容が多すぎます。']);
    $payload = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
    $doc = okubo_cms_publish($payload, $_SESSION['user']);
    okubo_cms_response(200, ['revision' => $doc['revision'], 'message' => '公開しました。']);
} catch (InvalidArgumentException | JsonException $error) { okubo_cms_response(422, ['message' => $error->getMessage()]);
} catch (LogicException $error) { okubo_cms_response(409, ['message' => $error->getMessage()]);
} catch (Throwable $error) { okubo_cms_response(503, ['message' => '公開に失敗しました。下書きは残っています。管理者へご連絡ください。']); }
