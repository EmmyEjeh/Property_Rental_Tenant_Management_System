#!/usr/bin/env bash
set -euo pipefail
# Requires mysqldump and the DB_* variables to be exported in the shell.
: "${DB_NAME:?Set DB_NAME}"
: "${DB_USER:?Set DB_USER}"
: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
backup_dir="$(cd "$(dirname "$0")/.." && pwd)/storage/backups"
mkdir -p "$backup_dir"
filename="$backup_dir/${DB_NAME}_$(date +%Y%m%d_%H%M%S).sql"
MYSQL_PWD="${DB_PASS:-}" mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --single-transaction --routines --triggers "$DB_NAME" > "$filename"
echo "Backup written to $filename"
