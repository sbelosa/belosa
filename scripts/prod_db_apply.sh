#!/usr/bin/env bash

set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
workspace_root="$(cd "${script_dir}/.." && pwd)"

# shellcheck disable=SC1091
source "${script_dir}/prod_db_lib.sh"

usage() {
  cat <<'EOF'
Usage:
  scripts/prod_db_apply.sh <sql-file>
  scripts/prod_db_apply.sh --apply <sql-file>

Behavior:
  - Without --apply, prints a safe preview only.
  - With --apply, runs the SQL file against the configured live write database.
  - Only .sql files inside ./scripts are allowed.

Required env vars in scripts/live_db_write.env:
  FCC_LIVE_WRITE_DB_HOST
  FCC_LIVE_WRITE_DB_NAME
  FCC_LIVE_WRITE_DB_USER
  FCC_LIVE_WRITE_DB_PASSWORD

Optional:
  FCC_LIVE_WRITE_DB_PORT=3306

Examples:
  scripts/prod_db_apply.sh scripts/check_lejla_kovacevic_clicks_2026_04_10.sql
  scripts/prod_db_apply.sh --apply scripts/fix_lejla_kovacevic_main_app_and_ai_reset_2026_04_10.sql
EOF
}

apply_mode=0
sql_file=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --help|-h)
      usage
      exit 0
      ;;
    --apply)
      apply_mode=1
      shift
      ;;
    *)
      if [[ -n "${sql_file}" ]]; then
        echo "Only one SQL file can be provided." >&2
        exit 1
      fi
      sql_file="$1"
      shift
      ;;
  esac
done

if [[ -z "${sql_file}" ]]; then
  usage
  exit 1
fi

if [[ "${sql_file}" = /* ]]; then
  sql_path="${sql_file}"
else
  sql_path="${workspace_root}/${sql_file#./}"
fi

sql_path="$(cd "$(dirname "${sql_path}")" && pwd)/$(basename "${sql_path}")"
allowed_prefix="${workspace_root}/scripts/"

if [[ "${sql_path}" != "${allowed_prefix}"* ]]; then
  echo "Refusing to run SQL outside ${allowed_prefix}" >&2
  exit 1
fi

if [[ ! -f "${sql_path}" ]]; then
  echo "SQL file not found: ${sql_path}" >&2
  exit 1
fi

if [[ "${sql_path##*.}" != "sql" ]]; then
  echo "Only .sql files are allowed." >&2
  exit 1
fi

if command -v shasum >/dev/null 2>&1; then
  file_hash="$(shasum -a 256 "${sql_path}" | awk '{print $1}')"
elif command -v sha256sum >/dev/null 2>&1; then
  file_hash="$(sha256sum "${sql_path}" | awk '{print $1}')"
else
  file_hash="unavailable"
fi

echo "SQL file: ${sql_path}"
echo "SHA256:   ${file_hash}"
echo
sed -n '1,24p' "${sql_path}"
echo

if [[ "${apply_mode}" -ne 1 ]]; then
  echo "Preview only. Re-run with --apply to execute."
  exit 0
fi

setup_live_db_connection

if [[ "$(get_live_db_transport)" == "http" ]]; then
  normalized_sql="$(normalize_http_sql_payload "$(cat "${sql_path}")")"
  echo "Applying ${sql_path} preko HTTP ops-write endpointa ..."
  run_live_db_http_sql "${normalized_sql}" "prod_db_apply:$(basename "${sql_path}")"
  echo
  echo "SQL apply completed."
  exit 0
fi

setup_live_db_connection

echo "Applying ${sql_path} to ${LIVE_DB_HOST}:${LIVE_DB_PORT}/${LIVE_DB_NAME} ..."

cat "${sql_path}" | run_live_db_from_stdin "${LIVE_DB_NAME}"

echo
echo "SQL apply completed."
