<?php
require __DIR__ . '/lib.php';
okubo_cms_session();
if (isset($_SESSION['user'])) { header('Location: ./'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!okubo_cms_valid_post($_POST['csrf'] ?? null)) $error = 'ページを再読み込みしてお試しください。';
    else try {
        if (okubo_cms_login($_POST['username'] ?? null, $_POST['password'] ?? null, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            session_regenerate_id(true);
            $_SESSION = ['user' => $_POST['username'], 'lastSeen' => time(), 'started' => time(), 'csrf' => bin2hex(random_bytes(32))];
            header('Location: ./', true, 303); exit;
        }
        $error = 'ログインIDまたはパスワードが違います。';
    } catch (Throwable $ex) { $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'ログインできません。管理者へご連絡ください。'; }
}
?>
<!doctype html><html lang="ja"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>サイト更新 ログイン | おおくぼ内科クリニック</title><link rel="stylesheet" href="./login.css"></head>
<body><main class="login-card"><img src="../assets/original-logo.png" alt="おおくぼ内科クリニック" width="382" height="70"><h1>ホームページの更新</h1><p>医院専用のログインIDとパスワードを入力してください。</p>
<?php if ($error): ?><p class="error" role="alert"><?= okubo_cms_escape($error) ?></p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= okubo_cms_escape(okubo_cms_csrf()) ?>"><label for="username">ログインID</label><input id="username" name="username" autocomplete="username" maxlength="100" required><label for="password">パスワード</label><input id="password" type="password" name="password" autocomplete="current-password" maxlength="200" required><button type="submit">ログイン</button></form><a href="../">ホームページを見る</a></main></body></html>
