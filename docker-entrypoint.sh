#!/bin/sh
set -e

# Инициализируем БД при первом запуске (volume пустой — файла ещё нет).
if [ ! -f /app/database/database.sqlite ]; then
    echo ">>> Первый запуск: инициализация базы данных..."
    php /app/database/migrations/migrate.php
    php /app/database/seeds/seed.php
    echo ">>> База данных готова."
fi

exec "$@"
