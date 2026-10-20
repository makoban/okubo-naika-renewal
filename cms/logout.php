<?php
require __DIR__ . '/lib.php'; okubo_cms_session();
if (!okubo_cms_valid_post($_POST['csrf'] ?? null)) { http_response_code(403); exit; }
$_SESSION = []; session_destroy();
setcookie('OKUBO_CMS', '', ['expires' => time() - 3600, 'path' => PHP_SAPI === 'cli-server' ? '/cms/' : '/admin/', 'secure' => PHP_SAPI !== 'cli-server', 'httponly' => true, 'samesite' => 'Strict']);
header('Location: ./login.php', true, 303);
