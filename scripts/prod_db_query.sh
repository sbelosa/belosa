#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
workspace_root="$(cd "${script_dir}/.." && pwd)"

# shellcheck disable=SC1091
source "${script_dir}/prod_db_lib.sh"

usage() {
  cat <<'EOF'
Usage:
  scripts/prod_db_query.sh --sql "SELECT 1"
  scripts/prod_db_query.sh --file scripts/something.sql
  scripts/prod_db_query.sh --db app --sql "UPDATE ..."
  scripts/prod_db_query.sh --label "investigation-2026-04-22" --sql "SELECT 1"
  echo "SELECT 1" | scripts/prod_db_query.sh

Runs arbitrary SQL directly against the configured live database.
Supports direct DB access or HTTP ops-write transport, depending on env setup.
EOF
}

sql_text=""
sql_file=""
target_db=""
label=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --help|-h)
      usage
      exit 0
      ;;
    --sql)
      shift
      sql_text="${1:-}"
      ;;
    --file)
      shift
      sql_file="${1:-}"
      ;;
    --db)
      shift
      target_db="${1:-}"
      ;;
    --label)
      shift
      label="${1:-}"
      ;;
    *)
      echo "Nepoznata opcija: $1" >&2
      usage
      exit 1
      ;;
  esac
  shift
done

if [[ -n "${sql_text}" ]]; then
  if [[ "$(get_live_db_transport)" == "http" ]]; then
    normalized_sql="$(normalize_http_sql_payload "${sql_text}")"
    run_live_db_http_sql "${normalized_sql}" "${label}"
    exit 0
  fi

  setup_live_db_connection
  if [[ -z "${target_db}" ]]; then
    target_db="${LIVE_DB_NAME}"
  fi
  printf '%s\n' "${sql_text}" | run_live_db_from_stdin "${target_db}"
  exit 0
fi

if [[ -n "${sql_file}" ]]; then
  if [[ "${sql_file}" = /* ]]; then
    sql_path="${sql_file}"
  else
    sql_path="${workspace_root}/${sql_file#./}"
  fi
  sql_path="$(cd "$(dirname "${sql_path}")" && pwd)/$(basename "${sql_path}")"
  if [[ ! -f "${sql_path}" ]]; then
    echo "SQL file not found: ${sql_path}" >&2
    exit 1
  fi

  if [[ "$(get_live_db_transport)" == "http" ]]; then
    normalized_sql="$(normalize_http_sql_payload "$(cat "${sql_path}")")"
    run_live_db_http_sql "${normalized_sql}" "${label}"
    exit 0
  fi

  setup_live_db_connection
  if [[ -z "${target_db}" ]]; then
    target_db="${LIVE_DB_NAME}"
  fi
  cat "${sql_path}" | run_live_db_from_stdin "${target_db}"
  exit 0
fi

if [[ ! -t 0 ]]; then
  if [[ "$(get_live_db_transport)" == "http" ]]; then
    normalized_sql="$(normalize_http_sql_payload "$(cat -)")"
    run_live_db_http_sql "${normalized_sql}" "${label}"
    exit 0
  fi

  setup_live_db_connection
  if [[ -z "${target_db}" ]]; then
    target_db="${LIVE_DB_NAME}"
  fi
  cat - | run_live_db_from_stdin "${target_db}"
  exit 0
fi

usage
exit 1
