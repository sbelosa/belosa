#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 1 ]]; then
  cat <<'EOF'
Usage: scripts/prod_ops_logs.sh <profile> [lines]

Profiles:
  ls      List common log directories/files
  error   Tail common Apache/PHP error logs
  access  Tail common Apache access logs
  php     Tail common PHP-FPM/php error logs

Required env vars:
  FCC_LIVE_SSH_HOST
  FCC_LIVE_SSH_USER

Optional env vars:
  FCC_LIVE_SSH_PORT
  FCC_LIVE_SSH_KEY_PATH

Examples:
  scripts/prod_ops_logs.sh ls
  scripts/prod_ops_logs.sh error 200
  scripts/prod_ops_logs.sh access 200
EOF
  exit 1
fi

: "${FCC_LIVE_SSH_HOST:?Set FCC_LIVE_SSH_HOST to the live SSH host.}"
: "${FCC_LIVE_SSH_USER:?Set FCC_LIVE_SSH_USER to the live SSH username.}"

profile="$1"
lines="${2:-200}"

if ! [[ "$lines" =~ ^[0-9]+$ ]]; then
  echo "lines must be a positive integer" >&2
  exit 1
fi

ssh_args=()
if [[ -n "${FCC_LIVE_SSH_PORT:-}" ]]; then
  ssh_args+=(-p "$FCC_LIVE_SSH_PORT")
fi

if [[ -n "${FCC_LIVE_SSH_KEY_PATH:-}" ]]; then
  ssh_args+=(-i "$FCC_LIVE_SSH_KEY_PATH")
fi

case "$profile" in
  ls)
    remote_cmd="ls -lah ~/logs /var/log /var/log/apache2 /usr/local/apache/logs 2>/dev/null || true"
    ;;
  error)
    remote_cmd="for f in ~/logs/error_log /var/log/apache2/error.log /usr/local/apache/logs/error_log /var/log/httpd/error_log; do if [ -f \"\$f\" ]; then echo \"===== \$f =====\"; tail -n ${lines} \"\$f\"; fi; done"
    ;;
  access)
    remote_cmd="for f in ~/logs/access_log /var/log/apache2/access.log /usr/local/apache/logs/access_log /var/log/httpd/access_log; do if [ -f \"\$f\" ]; then echo \"===== \$f =====\"; tail -n ${lines} \"\$f\"; fi; done"
    ;;
  php)
    remote_cmd="for f in ~/logs/php_error_log /var/log/php-fpm/error.log /var/log/php8.2-fpm.log /var/log/php8.1-fpm.log /var/log/php/error.log; do if [ -f \"\$f\" ]; then echo \"===== \$f =====\"; tail -n ${lines} \"\$f\"; fi; done"
    ;;
  *)
    echo "Unknown profile: ${profile}" >&2
    exit 1
    ;;
esac

ssh "${ssh_args[@]}" "${FCC_LIVE_SSH_USER}@${FCC_LIVE_SSH_HOST}" "$remote_cmd"
