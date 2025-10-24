#!/bin/sh
set -e

DBS="${DB_NAMES:-$DB_NAME}"
TS=$(date +'%Y-%m-%d_%H-%M')

for DB in $DBS; do
  FILE="/backups/${DB}_${TS}.sql.gz"
  echo "[backup] $(date) -> Dump $DB to $FILE"

  mysqldump \
    -h "${DB_HOST}" -P "${DB_PORT:-3306}" \
    -u "${DB_USER}" -p"${DB_PASSWORD}" \
    --single-transaction --quick --skip-lock-tables --no-tablespaces \
    --routines --events --triggers "$DB" \
    | gzip > "$FILE"

  find /backups -type f -name "${DB}_*.sql.gz" -mtime +${RETENTION_DAYS:-7} -delete
done