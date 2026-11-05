#!/bin/bash
set -euo pipefail

DB_HOST="${MYSQL_HOST:-mysql}"
DB_NAME="${MYSQL_DATABASE:?MYSQL_DATABASE is required}"
DB_USER="${MYSQL_USER:?MYSQL_USER is required}"
DB_PASSWORD="${MYSQL_PASSWORD:?MYSQL_PASSWORD is required}"
RETENTION="${BACKUP_RETENTION_DAYS:-7}"
BACKUP_DIR="/backups"

mkdir -p "$BACKUP_DIR"

while true; do
    timestamp="$(date +%Y%m%d-%H%M%S)"
    db_tmp="$BACKUP_DIR/.mysql-$timestamp.sql.gz"
    storage_tmp="$BACKUP_DIR/.storage-$timestamp.tar.gz"

    if mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" | gzip > "$db_tmp"; then
        mv "$db_tmp" "$BACKUP_DIR/mysql-$timestamp.sql.gz"
    else
        echo "mysqldump failed at $timestamp" >&2
        rm -f "$db_tmp"
    fi

    if [ -d /storage ]; then
        if tar -czf "$storage_tmp" -C /storage .; then
            mv "$storage_tmp" "$BACKUP_DIR/storage-$timestamp.tar.gz"
        else
            echo "storage archive failed at $timestamp" >&2
            rm -f "$storage_tmp"
        fi
    fi

    find "$BACKUP_DIR" -type f -mtime +"$RETENTION" -delete

    sleep 86400
done

