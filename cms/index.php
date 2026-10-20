<?php
require __DIR__ . '/lib.php';
okubo_cms_session();
okubo_cms_require_login();
$nonce = bin2hex(random_bytes(16));
require __DIR__ . '/templates/editor.php';
