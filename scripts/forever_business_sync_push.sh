#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
secret_file="${FCC_FOREVER_SYNC_ENV_FILE:-$HOME/.local/share/web-stack/live-access/fcc/flp360_sync.env}"

if [[ -f "${secret_file}" ]]; then
  # shellcheck disable=SC1090
  source "${secret_file}"
fi

: "${FCC_FOREVER_SYNC_KEY:?Set FCC_FOREVER_SYNC_KEY in the private sync environment file.}"
: "${FCC_FOREVER_SYNC_URL:?Set FCC_FOREVER_SYNC_URL in the private sync environment file.}"

report_file="${1:-}"
report_period="${2:-$(date +%Y-%m)}"

if [[ -z "${report_file}" || ! -f "${report_file}" ]]; then
  echo "Usage: ${script_dir}/forever_business_sync_push.sh /path/report.csv [YYYY-MM]" >&2
  exit 2
fi

case "${report_file##*.}" in
  csv|CSV|xlsx|XLSX) ;;
  *) echo "Only CSV and XLSX files are accepted." >&2; exit 2 ;;
esac

if [[ ! "${report_period}" =~ ^[0-9]{4}-[0-9]{2}$ ]]; then
  echo "Report period must use YYYY-MM." >&2
  exit 2
fi

curl --silent --show-error --fail-with-body \
  --request POST \
  --header "X-FCC-Forever-Sync-Key: ${FCC_FOREVER_SYNC_KEY}" \
  --form "report_period=${report_period}" \
  --form "report_file=@${report_file}" \
  "${FCC_FOREVER_SYNC_URL}"
printf '\n'
