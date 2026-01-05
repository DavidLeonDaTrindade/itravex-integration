#!/bin/sh
set -e

DBS="${DB_NAMES:-$DB_NAME}"
TS=$(date +'%Y-%m-%d_%H-%M')

for ENTRY in $DBS; do
  # Soporta DB:ALIAS
  if echo "$ENTRY" | grep -q ':'; then
    DB_REAL=$(echo "$ENTRY" | cut -d: -f1)
    DB_LABEL=$(echo "$ENTRY" | cut -d: -f2)
  else
    DB_REAL="$ENTRY"
    DB_LABEL="$ENTRY"
  fi

  FILE="/backups/${DB_LABEL}_${TS}.sql.gz"
  echo "[backup] $(date) -> Dump $DB_REAL as $FILE"

  mysqldump \
    -h "${DB_HOST}" -P "${DB_PORT:-3306}" \
    -u "${DB_USER}" -p"${DB_PASSWORD}" \
    --single-transaction --quick --skip-lock-tables --no-tablespaces \
    --routines --events --triggers "$DB_REAL" \
    | gzip > "$FILE"

  find /backups -type f -name "${DB_LABEL}_*.sql.gz" -mtime +${RETENTION_DAYS:-7} -delete
done
