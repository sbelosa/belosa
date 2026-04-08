#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ -f "${script_dir}/live_ops.env" ]]; then
  # shellcheck disable=SC1091
  source "${script_dir}/live_ops.env"
fi

if [[ $# -lt 1 ]]; then
  cat <<'EOF'
Usage: scripts/prod_ops_fetch.sh <scope> [key=value ...]

Required env vars:
  FCC_OPS_BASE_URL
  FCC_OPS_READONLY_KEY

Examples:
  scripts/prod_ops_fetch.sh health pretty=1
  scripts/prod_ops_fetch.sh overview pretty=1
  scripts/prod_ops_fetch.sh collaborators query=ana limit=5 pretty=1
  scripts/prod_ops_fetch.sh collaborator user_id=555 billing_events_limit=12 pretty=1
  scripts/prod_ops_fetch.sh billing state=past_due_critical limit=10 pretty=1
EOF
  exit 1
fi

: "${FCC_OPS_BASE_URL:?Set FCC_OPS_BASE_URL to the live production base URL.}"
: "${FCC_OPS_READONLY_KEY:?Set FCC_OPS_READONLY_KEY to the shared readonly ops key.}"

scope="$1"
shift

endpoint="${FCC_OPS_BASE_URL%/}/ops-readonly"

curl_args=(
  --silent
  --show-error
  --fail
  --location
  --get
  "$endpoint"
  --data-urlencode "scope=${scope}"
  --data-urlencode "key=${FCC_OPS_READONLY_KEY}"
)

for pair in "$@"; do
  if [[ "$pair" != *=* ]]; then
    echo "Invalid argument: ${pair}. Use key=value." >&2
    exit 1
  fi

  curl_args+=(--data-urlencode "$pair")
done

curl "${curl_args[@]}"
printf '\n'
