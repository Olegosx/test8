#!/usr/bin/env bash
# Скрипт для проверки защиты от race condition при параллельном взятии заявки.
#
# ВАЖНО: php -S (встроенный сервер) однопоточный — запросы обрабатываются
# последовательно. Для истинной параллельности используйте Docker/Apache/nginx.
# На однопоточном сервере скрипт всё равно демонстрирует корректность логики:
# второй (последовательный) запрос получает 409.
#
# Использование:
#   bash race_test.sh [BASE_URL] [REQUEST_ID]
#   bash race_test.sh http://localhost:8080 2

set -euo pipefail

BASE_URL="${1:-http://localhost:8080}"
REQUEST_ID="${2:-2}"

COOKIE1=$(mktemp)
COOKIE2=$(mktemp)

cleanup() { rm -f "$COOKIE1" "$COOKIE2"; }
trap cleanup EXIT

echo "=== Тест гонки: параллельное взятие заявки #${REQUEST_ID} ==="
echo "URL: ${BASE_URL}"
echo ""

echo "► Авторизация session 1 (master1)..."
curl -s -c "$COOKIE1" -b "$COOKIE1" \
    -X POST "${BASE_URL}/login" \
    -d "username=master1&password=master1" -o /dev/null

echo "► Авторизация session 2 (master1)..."
curl -s -c "$COOKIE2" -b "$COOKIE2" \
    -X POST "${BASE_URL}/login" \
    -d "username=master1&password=master1" -o /dev/null

echo ""
echo "► Отправляем два параллельных POST на /master/requests/${REQUEST_ID}/take"
echo "  (без -L: смотрим прямой HTTP-статус, не следуем редиректу)"
echo ""

# Оба запроса параллельно; -L не используем — нас интересует прямой статус.
# Ожидаем: один 302 (взято), другой 409 (конфликт).
curl -s -o /dev/null -w "  Запрос 1: HTTP %{http_code}\n" \
    -c "$COOKIE1" -b "$COOKIE1" \
    -X POST "${BASE_URL}/master/requests/${REQUEST_ID}/take" &
curl -s -o /dev/null -w "  Запрос 2: HTTP %{http_code}\n" \
    -c "$COOKIE2" -b "$COOKIE2" \
    -X POST "${BASE_URL}/master/requests/${REQUEST_ID}/take"
wait

echo ""
echo "Ожидаемый результат:"
echo "  302 — заявка успешно взята в работу"
echo "  409 — race condition: заявка уже взята (Conflict)"
