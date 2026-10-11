#!/usr/bin/env bash
# Cadangan harian: dump database + foto unggahan. Jadwalkan lewat cron (lihat PANDUAN-VM.md).
set -euo pipefail
APP_DIR="${APP_DIR:-/var/www/web-smkibu-laravel}"
TUJUAN="${TUJUAN:-/var/backups/smkibu}"
SIMPAN_HARI="${SIMPAN_HARI:-14}"

env_get() { grep -E "^$1=" "$APP_DIR/.env" | head -1 | cut -d= -f2-; }
TGL="$(date +%F_%H%M)"
mkdir -p "$TUJUAN"
umask 077

MYSQL_PWD="$(env_get DB_PASSWORD)" mysqldump --single-transaction --no-tablespaces \
    -h "$(env_get DB_HOST)" -u "$(env_get DB_USERNAME)" "$(env_get DB_DATABASE)" | gzip > "$TUJUAN/db_$TGL.sql.gz"
tar -czf "$TUJUAN/foto_$TGL.tar.gz" -C "$APP_DIR/storage/app/public" .
tar -czf "$TUJUAN/images_$TGL.tar.gz" -C "$APP_DIR/public" images 2>/dev/null || true

find "$TUJUAN" -type f -mtime +"$SIMPAN_HARI" -delete
echo "Cadangan tersimpan di $TUJUAN ($TGL)"
