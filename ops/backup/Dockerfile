FROM mysql:8.0

COPY ops/backup/backup.sh /usr/local/bin/backup.sh
RUN chmod +x /usr/local/bin/backup.sh

ENV BACKUP_RETENTION_DAYS=7

CMD ["/usr/local/bin/backup.sh"]
