#!/usr/bin/env python3
"""Custom code: FC-2026-10-14: guarded atomic deployment of the one CLI worker entry point."""
from pathlib import Path
import ftplib
import hashlib
import io
import json
import os
import re
import secrets
import ssl
import subprocess
import sys

PUBLIC = '/public_html/scripts/partner-notifications.php'
WORKER = '/fcc-release-20260927/partner-worker.php'
WORKER_SHA = '9038206196439d8ff1eb3621974924b1f3266d41a53eeb5a8e19386ba962c16f'
BASELINE_SHA = '827dcfaeaa0b12d9bc4bbc35a14ac39aa0b91c30f249fb1786f4ee18756eba4d'
PROFILE_SHA = '663c948a3010685e61420e447a3d9a70f12071129da1b432f6a7e4b89afcc9ed'
class WorkerDeployGuardError(RuntimeError):
    pass

HELPERS = ('app/helpers/fcc_team.php', 'app/helpers/fcc_partner_notifications.php', 'app/helpers/fcc_webinar_events.php')

def sha(raw):
    return hashlib.sha256(raw).hexdigest()

def read(client, path):
    stream = io.BytesIO()
    def bounded_write(chunk):
        if stream.tell() + len(chunk) > 2097152:
            raise WorkerDeployGuardError('remote_source_size_guard')
        stream.write(chunk)
    client.retrbinary('RETR ' + path, bounded_write)
    return stream.getvalue()

def git_blob(ref, path):
    return subprocess.check_output(['git', 'show', ref + ':' + path], stderr=subprocess.DEVNULL)

def main():
    expected = git_blob('HEAD', 'scripts/partner-notifications.php')
    if Path('scripts/partner-notifications.php').read_bytes() != expected:
        raise WorkerDeployGuardError('uncommitted_worker_source')
    previous = os.environ.get('BEFORE_SHA', '')
    if not re.fullmatch('[0-9a-f]{40}', previous):
        raise WorkerDeployGuardError('previous_commit_required')
    allowed = {BASELINE_SHA, PROFILE_SHA, sha(expected)}
    try:
        allowed.add(sha(git_blob(previous, 'scripts/partner-notifications.php')))
    except subprocess.CalledProcessError:
        pass  # The legacy entry point was installed before being tracked in Git.
    server = os.environ.get('FTP_TLS_SERVER') or 'litespeed.hiperweb.hr'
    if server != 'litespeed.hiperweb.hr':
        raise WorkerDeployGuardError('unexpected_production_server')
    username, password = os.environ.get('FTP_USERNAME'), os.environ.get('FTP_PASSWORD')
    if not username or not password:
        raise WorkerDeployGuardError('ftp_credentials_unavailable')
    port = int(os.environ.get('FTP_PORT') or 21)
    if port != 21:
        raise WorkerDeployGuardError('unexpected_production_port')
    client = ftplib.FTP_TLS(context=ssl.create_default_context(), timeout=30)
    ready = None
    try:
        client.connect(server, port); client.login(username, password); client.prot_p()
        if sha(read(client, WORKER)) != WORKER_SHA:
            raise WorkerDeployGuardError('private_worker_not_regular')
        for helper in HELPERS:
            if read(client, '/public_html/' + helper) != git_blob('HEAD', helper):
                raise WorkerDeployGuardError('helper_deployment_incomplete')
        current = read(client, PUBLIC)
        if sha(current) not in allowed:
            raise WorkerDeployGuardError('unreviewed_live_worker_source')
        lines = []; client.retrlines('LIST ' + PUBLIC, lines.append)
        if len(lines) != 1 or not re.match(r'^-[rwx-]{9}\s', lines[0]):
            raise WorkerDeployGuardError('worker_permissions_unavailable')
        mode = sum(1 << (8-i) for i, value in enumerate(lines[0][1:10]) if value != '-')
        if mode not in {0o600, 0o640, 0o644}:
            raise WorkerDeployGuardError('unexpected_worker_permissions')
        if current != expected:
            ready = '/public_html/scripts/partner-notifications-ready-' + secrets.token_hex(8) + '.php'
            client.storbinary('STOR ' + ready, io.BytesIO(expected))
            client.sendcmd('SITE CHMOD ' + format(mode, 'o') + ' ' + ready)
            if read(client, ready) != expected or read(client, PUBLIC) != current:
                raise WorkerDeployGuardError('worker_changed_before_activation')
            client.rename(ready, PUBLIC); ready = None
        if read(client, PUBLIC) != expected or sha(read(client, WORKER)) != WORKER_SHA:
            raise WorkerDeployGuardError('worker_post_deploy_guard')
        print(json.dumps({'status': 'WORKER_VERIFIED', 'script_sha256': sha(expected), 'prior_sha256': sha(current), 'permissions': mode, 'private_worker_unchanged': True, 'extra_worker_invocations': 0}))
    finally:
        if ready is not None:
            try: client.delete(ready)
            except ftplib.all_errors: pass
        try: client.quit()
        except ftplib.all_errors:
            try: client.close()
            except ftplib.all_errors: pass

if __name__ == '__main__':
    try: main()
    except Exception as error:
        print(json.dumps({'status': 'ERROR', 'error_class': type(error).__name__, 'guard': str(error) if isinstance(error, WorkerDeployGuardError) else 'transport_or_environment_failure'}), file=sys.stderr)
        sys.exit(1)
