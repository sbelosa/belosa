"""Custom code: FC-2026-10-07: Preserve live changes during a targeted registration release."""
import ftplib
import hashlib
import io
import json
import os
from pathlib import Path
import re
import subprocess
import tempfile
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
TAG = "FC-2026-10-07"
HELPERS = ["app/helpers/fcc_registration_notifications.php", "app/helpers/fcc_registration_automation.php"]
SHARED = ["app/controllers/Register.php", "app/controllers/ForeverBusinessSync.php"]
LANGUAGES = ["app/languages/Hrvatski#hr.php", "app/languages/english#en.php",
             "app/languages/cache/Hrvatski#hr.php", "app/languages/cache/english#en.php"]


def block(source):
    pattern = rf"(?m)^[ \t]*/\* Custom code: {TAG}:.*?^[ \t]*/\* /Custom code: {TAG} \*/\n"
    matches = list(re.finditer(pattern, source, re.S))
    if len(matches) != 1:
        raise ValueError("Expected one reviewed controller patch")
    return matches[0].group()


def patch_controller(path, live, source):
    addition = block(source)
    if f"/* Custom code: {TAG}:" in live:
        existing = block(live)
        return live.replace(existing, addition, 1)
    if path.endswith("/Register.php"):
        anchor = re.search(r"(?m)^([ \t]*)\$registered_user\s*=\s*\(new User\(\)\)->create\(", live)
        if not anchor:
            raise ValueError("Live registration call differs from the reviewed contract")
        call = live[anchor.start():].split(");", 1)[0]
        if "$_POST['meta']" not in call or not re.search(r"\$_POST\['name'\],\s*0,\s*'direct'", call):
            raise ValueError("Live registration is not an explicit pending direct request")
        return live[:anchor.start()] + addition + live[anchor.start():]
    anchors = list(re.finditer(r"(?m)^[ \t]*\$this->ensure_access\(\);[ \t]*\n", live))
    if len(anchors) != 1:
        raise ValueError("Live machine endpoint authentication differs")
    anchor = anchors[0]
    return live[:anchor.end()] + addition + live[anchor.end():]


def patch_language(live, source):
    keys = ["global.emails.admin.fcc_access_rejected.subject", "global.emails.admin.fcc_access_rejected.body",
            "global.emails.admin.fcc_access_rejected_not_team.subject", "global.emails.admin.fcc_access_rejected_not_team.body"]
    for key in keys:
        pattern = r"'" + re.escape(key) + r"'\s*=>\s*'(?:\\.|[^'\\])*',"
        canonical = list(re.finditer(pattern, source, re.S))
        production = list(re.finditer(pattern, live, re.S))
        if len(canonical) != 1 or len(production) != 1:
            raise ValueError("Live email template contract differs")
        replacement = canonical[0].group()
        if key == keys[0] and f"Custom code: {TAG}: Registration verification email wording" not in live:
            replacement = f"/* Custom code: {TAG}: Registration verification email wording */\n\t" + replacement
        if key == keys[-1] and f"/* /Custom code: {TAG} */" not in live:
            replacement += f"\n\t/* /Custom code: {TAG} */"
        match = production[0]
        live = live[:match.start()] + replacement + live[match.end():]
    return live


def download(ftp, path, optional=False):
    output = io.BytesIO()
    try:
        ftp.retrbinary("RETR " + path, output.write)
        return output.getvalue()
    except ftplib.error_perm as error:
        if optional and str(error).startswith("550"):
            return None
        raise


def mkdirs(ftp, path):
    current = ""
    for part in path.strip("/").split("/"):
        current += "/" + part
        try:
            ftp.mkd(current)
        except ftplib.error_perm:
            ftp.cwd(current)
    ftp.cwd("/")


def store_atomic(ftp, path, data, suffix):
    temporary = path + ".fcc-upload-" + suffix + ".php"
    ftp.storbinary("STOR " + temporary, io.BytesIO(data))
    if download(ftp, temporary) != data:
        raise RuntimeError("Uploaded file verification failed")
    ftp.rename(temporary, path)


def fcc_request(metric, **fields):
    import urllib.parse
    body = urllib.parse.urlencode(dict(metric=metric, **fields)).encode()
    request = urllib.request.Request(os.environ["FCC_FOREVER_SYNC_URL"], data=body,
        headers={"X-FCC-Forever-Sync-Key": os.environ["FCC_FOREVER_SYNC_KEY"], "Content-Type": "application/x-www-form-urlencoded"})
    with urllib.request.urlopen(request, timeout=90) as response:
        result = json.load(response)
    if result.get("status") != "success" or result.get("metric") != metric:
        raise RuntimeError("Production endpoint verification failed")
    return result


def main():
    release = os.environ["GITHUB_SHA"]
    if not re.fullmatch(r"[a-f0-9]{40}", release):
        raise ValueError("Invalid release identifier")
    ftp = ftplib.FTP()
    ftp.connect(os.environ["FTP_SERVER"], int(os.environ.get("FTP_PORT") or "21"), timeout=60)
    ftp.login(os.environ["FTP_USERNAME"], os.environ["FTP_PASSWORD"])
    before, after = {}, {}
    uploaded = []
    try:
        for path in HELPERS + LANGUAGES + SHARED:
            data = download(ftp, "/public_html/" + path, optional=path in HELPERS)
            before[path] = data
            source = (ROOT / path).read_text()
            if path in HELPERS:
                after[path] = source.encode()
            else:
                live = data.decode()
                updated = patch_language(live, source) if path in LANGUAGES else patch_controller(path, live, source)
                after[path] = updated.encode()
            print(json.dumps({"file": path, "existing_live_changes_preserved": path not in HELPERS,
                              "changed": data != after[path]}))
        with tempfile.TemporaryDirectory() as directory:
            for index, data in enumerate(after.values()):
                file = Path(directory) / f"patch{index}.php"
                file.write_bytes(data)
                subprocess.run(["php", "-l", str(file)], check=True, capture_output=True)
        backup = "/.fcc-registration-backups/" + release
        mkdirs(ftp, backup)
        for path, data in before.items():
            if data is not None:
                ftp.storbinary("STOR " + backup + "/" + path.replace("/", "__"), io.BytesIO(data))
        try:
            for path, data in after.items():
                if before[path] == data:
                    continue
                store_atomic(ftp, "/public_html/" + path, data, release[:12])
                uploaded.append(path)
            status = fcc_request("registration_status")
            if not status.get("runtime_ready"):
                raise RuntimeError("Production registration prerequisites are unavailable")
            pending = fcc_request("registration_pending", limit="100")
            fcc_request("registration_decisions", decisions="[]", dry_run="1")
            print(json.dumps({"production_verified": True, "pending_registration_count": len(pending.get("accounts", [])),
                              "admin_notifications": status.get("admin_notifications"), "release": release}))
        except Exception:
            for path in reversed(uploaded):
                if before[path] is None:
                    ftp.delete("/public_html/" + path)
                else:
                    store_atomic(ftp, "/public_html/" + path, before[path], release[:12] + "rollback")
            raise
    finally:
        ftp.quit()


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        # Never expose FTP credentials, endpoints, names, email addresses or subscriptions.
        print("Targeted registration release failed: " + type(error).__name__)
        raise SystemExit(1)
# /Custom code: FC-2026-10-07
