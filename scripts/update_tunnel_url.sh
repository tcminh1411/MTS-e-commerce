#!/usr/bin/env bash
set -euo pipefail

NEW_URL="${1:?usage: $0 <https://....trycloudflare.com>}"
NEW_HOST="${NEW_URL#https://}"; NEW_HOST="${NEW_HOST%%/*}"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

[ -f .env ] || { echo "no .env in $ROOT"; exit 1; }

# 1) backup
STAMP="$(date +%Y%m%d_%H%M%S)"
cp -p .env ".env.bak.${STAMP}"
cp -p .env .env.bak

# 2) find all old trycloudflare hosts currently in .env
OLD_HOSTS="$(grep -oE '[a-z0-9-]+\.trycloudflare\.com' .env | sort -u || true)"
if [ -z "$OLD_HOSTS" ]; then
  echo "warning: no existing *.trycloudflare.com host found in .env"
fi

# 3) rewrite each line, preserving the key name
for h in $OLD_HOSTS; do
  sed -i "s|${h}|${NEW_HOST}|g" .env
done

# 4) fallback: if key exists but had no trycloudflare host, still force APP_URL etc.
sed -i -E "s|^(APP_URL=).*|\1${NEW_URL}|"    .env
sed -i -E "s|^(FRONTEND_URL=).*|\1${NEW_URL}|" .env

echo "backup -> .env.bak.${STAMP}"
echo "old host(s): ${OLD_HOSTS:-<none>}"
echo "new url    : ${NEW_URL}"
grep -n "trycloudflare" .env || true

# 5) Laravel caches the env — clear so changes take effect
if [ -f artisan ]; then
  php artisan config:clear >/dev/null 2>&1 || true
  # php artisan config:cache >/dev/null 2>&1 || true   # uncomment if you cache in prod
fi

# 6) if services run via docker, recreate the ones that read env
# docker compose up -d --force-recreate app queue scheduler 2>/dev/null || true
