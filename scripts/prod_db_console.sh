#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# shellcheck disable=SC1091
source "${script_dir}/prod_db_lib.sh"

if [[ "$(get_live_db_transport)" == "http" ]]; then
  echo "HTTP write lane ne podrzava interaktivni SQL console." >&2
  echo "Koristi scripts/prod_db_query.sh --sql \"...\" ili /Volumes/Extreme SSD/web/bin/db-portable.sh fcc-live --sql \"...\"" >&2
  exit 1
fi

setup_live_db_connection
run_live_db_interactive "${1:-$LIVE_DB_NAME}"
