#!/usr/bin/env python3
"""Provision a new clinic login without placing plaintext credentials in the repo."""
import json
import os
from pathlib import Path
import secrets
import subprocess
import tempfile
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root / 'output/customer-cms-20261020'
output.mkdir(parents=True, exist_ok=True)
username = 'clinic'
password = secrets.token_urlsafe(18)
result = subprocess.run(['/usr/sbin/htpasswd', '-niBC', '12', username],
                        input=password + '\n', text=True, capture_output=True, check=True)
password_hash = result.stdout.strip().split(':', 1)[1]
assert password_hash.startswith(('$2y$', '$2b$'))
with tempfile.TemporaryDirectory(prefix='okubo-credential-') as temporary:
    source = Path(temporary) / 'credentials'
    source.write_text('OKUBO_CMS_USER=' + username + '\nOKUBO_CMS_PASSWORD=' + password + '\n')
    source.chmod(0o600)
    imported = subprocess.run(['with-secrets', 'import', str(source), '-p', 'projects/okubo-cms'], capture_output=True, text=True)
    if imported.returncode:
        raise RuntimeError('Credential registration failed; installation was not generated')
seed = "<?php\nreturn ['username' => 'clinic', 'passwordHash' => " + repr(password_hash) + "];\n"
archive = output / 'okubo-cms-install.zip'
with ZipFile(archive, 'w', ZIP_DEFLATED) as zipped:
    zipped.writestr('admin/credential-seed.php', seed)
archive.chmod(0o600)
transport = Path(tempfile.gettempdir()) / 'okubo-cms-auth.json'
with open(transport, 'w', opener=lambda p, f: os.open(p, f, 0o600)) as handle:
    json.dump({'username': username, 'password': password}, handle)
transport.chmod(0o600)
export = Path.home() / '.config' / 'bantex' / 'secrets' / 'exports' / 'okubo-cms-login.txt'
export.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
with open(export, 'w', opener=lambda p, f: os.open(p, f, 0o600)) as handle:
    handle.write('おおくぼ内科クリニック ホームページ更新用\n\nURL: https://www.okubo-naika-cl.com/admin/\nログインID: ' + username + '\nパスワード: ' + password + '\n\nログイン後、編集 → プレビュー → 本番HPに公開する、で更新できます。\n医院内の更新担当者だけで管理してください。\n')
export.chmod(0o600)
print('Clinic login registered in the secret store; hashed installer and private login handoff created.')
