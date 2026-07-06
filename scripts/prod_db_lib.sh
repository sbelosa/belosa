#!/usr/bin/env bash

if [[ -n "${FCC_PROD_DB_LIB_LOADED:-}" ]]; then
  return 0
fi
FCC_PROD_DB_LIB_LOADED=1

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ -f "${script_dir}/live_ops.env" ]]; then
  # shellcheck disable=SC1091
  source "${script_dir}/live_ops.env"
fi

if [[ -f "${script_dir}/live_db_write.env" ]]; then
  # shellcheck disable=SC1091
  source "${script_dir}/live_db_write.env"
fi

get_live_db_transport() {
  if [[ -n "${FCC_LIVE_WRITE_TRANSPORT:-}" ]]; then
    printf '%s\n' "${FCC_LIVE_WRITE_TRANSPORT}"
    return 0
  fi

  if [[ -n "${FCC_OPS_BASE_URL:-}" && -n "${FCC_OPS_WRITE_KEY:-}" ]]; then
    printf 'http\n'
    return 0
  fi

  printf 'direct\n'
}

get_live_http_default_label() {
  local current_user
  local current_host

  current_user="$(id -un 2>/dev/null || echo codex)"
  current_host="$(hostname -s 2>/dev/null || hostname 2>/dev/null || echo machine)"

  printf 'codex:%s@%s\n' "${current_user}" "${current_host}"
}

strip_http_sql_comments() {
  awk '
    BEGIN { in_block = 0 }
    {
      line = $0

      while(1) {
        if(in_block) {
          if(match(line, /\*\//)) {
            line = substr(line, RSTART + RLENGTH)
            in_block = 0
          } else {
            next
          }
        }

        if(match(line, /\/\*/)) {
          prefix = substr(line, 1, RSTART - 1)
          suffix = substr(line, RSTART + RLENGTH)
          if(match(suffix, /\*\//)) {
            suffix = substr(suffix, RSTART + RLENGTH)
            line = prefix suffix
            continue
          }

          line = prefix
          in_block = 1
        }

        break
      }

      trimmed = line
      sub(/^[[:space:]]+/, "", trimmed)

      if(trimmed == "" || trimmed ~ /^--([[:space:]]|$)/ || trimmed ~ /^#/) {
        next
      }

      print line
    }
  '
}

normalize_http_sql_payload() {
  local sql_text="${1//$'\r'/}"
  sql_text="$(printf '%s' "${sql_text}" | strip_http_sql_comments)"
  sql_text="$(printf '%s' "${sql_text}" | tr '\n\t' '  ' | sed -E 's/[[:space:]]+/ /g; s/^ //; s/ $//')"
  sql_text="${sql_text%;}"
  sql_text="$(printf '%s' "${sql_text}" | sed -E 's/[[:space:]]+$//')"

  if [[ -z "${sql_text}" ]]; then
    echo "HTTP write lane nije dobio valjan SQL payload." >&2
    return 1
  fi

  if [[ "${sql_text}" == *";"* ]]; then
    echo "HTTP write lane podrzava samo jednu SQL naredbu po pozivu." >&2
    return 1
  fi

  printf '%s\n' "${sql_text}"
}

detect_http_sql_action() {
  local sql_text="$1"
  local first_word

  first_word="$(printf '%s' "${sql_text}" | sed -E 's/^[[:space:]]*([[:alpha:]]+).*$/\1/' | tr '[:lower:]' '[:upper:]')"

  case "${first_word}" in
    SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)
      printf 'query\n'
      ;;
    INSERT|UPDATE|DELETE|REPLACE)
      printf 'execute\n'
      ;;
    *)
      echo "HTTP write lane ne podrzava ovaj tip SQL naredbe: ${first_word:-unknown}" >&2
      return 1
      ;;
  esac
}

run_live_db_http_sql() {
  local sql_text="$1"
  local label="${2:-}"
  local endpoint
  local action

  : "${FCC_OPS_BASE_URL:?Set FCC_OPS_BASE_URL in live_ops.env or live_db_write.env.}"
  : "${FCC_OPS_WRITE_KEY:?Set FCC_OPS_WRITE_KEY in live_ops.env or live_db_write.env.}"

  endpoint="${FCC_OPS_BASE_URL%/}/ops-write"
  action="$(detect_http_sql_action "${sql_text}")"

  if [[ -z "${label}" ]]; then
    label="$(get_live_http_default_label)"
  fi

  curl \
    --silent \
    --show-error \
    --fail \
    --location \
    --request POST \
    "${endpoint}" \
    --data-urlencode "action=${action}" \
    --data-urlencode "key=${FCC_OPS_WRITE_KEY}" \
    --data-urlencode "sql=${sql_text}" \
    --data-urlencode "label=${label}" \
    --data-urlencode "pretty=1"

  printf '\n'
}

live_db_cleanup() {
  if [[ -n "${FCC_LIVE_DB_TUNNEL_SOCKET:-}" && -S "${FCC_LIVE_DB_TUNNEL_SOCKET}" ]]; then
    ssh -S "${FCC_LIVE_DB_TUNNEL_SOCKET}" -O exit \
      -p "${FCC_LIVE_SSH_PORT:-22}" \
      "${FCC_LIVE_SSH_USER}@${FCC_LIVE_SSH_HOST}" >/dev/null 2>&1 || true
  fi
}

pick_free_local_port() {
  local port
  for port in $(seq 13306 13340); do
    if ! lsof -nP -iTCP:"${port}" -sTCP:LISTEN >/dev/null 2>&1; then
      echo "${port}"
      return 0
    fi
  done

  echo "Nisam uspio pronaci slobodan lokalni port za SSH tunnel." >&2
  return 1
}

setup_live_db_connection() {
  : "${FCC_LIVE_WRITE_DB_HOST:?Set FCC_LIVE_WRITE_DB_HOST in live_db_write.env.}"
  : "${FCC_LIVE_WRITE_DB_NAME:?Set FCC_LIVE_WRITE_DB_NAME in live_db_write.env.}"
  : "${FCC_LIVE_WRITE_DB_USER:?Set FCC_LIVE_WRITE_DB_USER in live_db_write.env.}"
  : "${FCC_LIVE_WRITE_DB_PASSWORD:?Set FCC_LIVE_WRITE_DB_PASSWORD in live_db_write.env.}"

  export LIVE_DB_NAME="${FCC_LIVE_WRITE_DB_NAME}"
  export LIVE_DB_USER="${FCC_LIVE_WRITE_DB_USER}"
  export LIVE_DB_PASSWORD="${FCC_LIVE_WRITE_DB_PASSWORD}"
  export LIVE_DB_HOST="${FCC_LIVE_WRITE_DB_HOST}"
  export LIVE_DB_PORT="${FCC_LIVE_WRITE_DB_PORT:-3306}"
  export LIVE_DB_CLIENT_HOST="${LIVE_DB_HOST}"
  export LIVE_DB_CLIENT_PORT="${LIVE_DB_PORT}"

  if [[ "${FCC_LIVE_WRITE_DB_USE_SSH_TUNNEL:-0}" == "1" ]]; then
    : "${FCC_LIVE_SSH_HOST:?Set FCC_LIVE_SSH_HOST in live_db_write.env when tunnel mode is enabled.}"
    : "${FCC_LIVE_SSH_USER:?Set FCC_LIVE_SSH_USER in live_db_write.env when tunnel mode is enabled.}"

    local tunnel_port
    tunnel_port="$(pick_free_local_port)"
    export FCC_LIVE_DB_TUNNEL_SOCKET
    FCC_LIVE_DB_TUNNEL_SOCKET="$(mktemp -u "/tmp/fcc-live-db-tunnel.XXXXXX.sock")"

    local remote_db_host="${FCC_LIVE_WRITE_DB_REMOTE_HOST:-127.0.0.1}"
    local remote_db_port="${FCC_LIVE_WRITE_DB_REMOTE_PORT:-3306}"
    local ssh_args=(-fN -M -S "${FCC_LIVE_DB_TUNNEL_SOCKET}" -L "0.0.0.0:${tunnel_port}:${remote_db_host}:${remote_db_port}")

    if [[ -n "${FCC_LIVE_SSH_PORT:-}" ]]; then
      ssh_args+=(-p "${FCC_LIVE_SSH_PORT}")
    fi

    if [[ -n "${FCC_LIVE_SSH_KEY_PATH:-}" ]]; then
      ssh_args+=(-i "${FCC_LIVE_SSH_KEY_PATH}")
    fi

    ssh_args+=(-o IdentitiesOnly=yes -o ExitOnForwardFailure=yes)
    ssh "${ssh_args[@]}" "${FCC_LIVE_SSH_USER}@${FCC_LIVE_SSH_HOST}"
    trap live_db_cleanup EXIT

    export LIVE_DB_HOST="127.0.0.1"
    export LIVE_DB_PORT="${tunnel_port}"
    export LIVE_DB_CLIENT_HOST="host.docker.internal"
    export LIVE_DB_CLIENT_PORT="${tunnel_port}"
  fi
}

run_live_db_from_stdin() {
  local target_db="${1:-$LIVE_DB_NAME}"
  if command -v mariadb >/dev/null 2>&1; then
    MYSQL_PWD="${LIVE_DB_PASSWORD}" \
      mariadb \
      --host="${LIVE_DB_HOST}" \
      --port="${LIVE_DB_PORT}" \
      --user="${LIVE_DB_USER}" \
      --default-character-set=utf8mb4 \
      "${target_db}"
    return 0
  fi

  if command -v mysql >/dev/null 2>&1; then
    MYSQL_PWD="${LIVE_DB_PASSWORD}" \
      mysql \
      --host="${LIVE_DB_HOST}" \
      --port="${LIVE_DB_PORT}" \
      --user="${LIVE_DB_USER}" \
      --default-character-set=utf8mb4 \
      "${target_db}"
    return 0
  fi

  if ! command -v docker >/dev/null 2>&1; then
    echo "Neither local DB client nor docker is available." >&2
    return 1
  fi

  docker run --rm -i \
    -e MYSQL_PWD="${LIVE_DB_PASSWORD}" \
    mariadb:11.4 \
    mariadb \
    --host="${LIVE_DB_CLIENT_HOST}" \
    --port="${LIVE_DB_CLIENT_PORT}" \
    --user="${LIVE_DB_USER}" \
    --default-character-set=utf8mb4 \
    "${target_db}"
}

run_live_db_interactive() {
  local target_db="${1:-$LIVE_DB_NAME}"
  if command -v mariadb >/dev/null 2>&1; then
    MYSQL_PWD="${LIVE_DB_PASSWORD}" \
      mariadb \
      --host="${LIVE_DB_HOST}" \
      --port="${LIVE_DB_PORT}" \
      --user="${LIVE_DB_USER}" \
      --default-character-set=utf8mb4 \
      "${target_db}"
    return 0
  fi

  if command -v mysql >/dev/null 2>&1; then
    MYSQL_PWD="${LIVE_DB_PASSWORD}" \
      mysql \
      --host="${LIVE_DB_HOST}" \
      --port="${LIVE_DB_PORT}" \
      --user="${LIVE_DB_USER}" \
      --default-character-set=utf8mb4 \
      "${target_db}"
    return 0
  fi

  if ! command -v docker >/dev/null 2>&1; then
    echo "Neither local DB client nor docker is available." >&2
    return 1
  fi

  docker run --rm -it \
    -e MYSQL_PWD="${LIVE_DB_PASSWORD}" \
    mariadb:11.4 \
    mariadb \
    --host="${LIVE_DB_CLIENT_HOST}" \
    --port="${LIVE_DB_CLIENT_PORT}" \
    --user="${LIVE_DB_USER}" \
    --default-character-set=utf8mb4 \
    "${target_db}"
}
