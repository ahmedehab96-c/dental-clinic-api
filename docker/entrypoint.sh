#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Prefer platform-provided public URL. Render exposes RENDER_EXTERNAL_URL
# (full https:// URL) automatically.
if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
  export APP_URL="${RENDER_EXTERNAL_URL}"
fi

# This Dockerfile/entrypoint only runs in the free-tier Render demo
# deployment (local dev uses `php artisan serve` directly, no Docker) — so
# it's safe to hardcode demo-appropriate defaults here rather than depend on
# dashboard-configured env vars (this Render CLI has no env-var subcommand).
: "${DB_CONNECTION:=sqlite}"
: "${SEED_ON_START:=true}"
: "${APP_ENV:=production}"
: "${APP_DEBUG:=false}"
: "${SESSION_DRIVER:=file}"
: "${CACHE_STORE:=file}"
: "${QUEUE_CONNECTION:=sync}"
: "${MAIL_MAILER:=log}"
: "${FRONTEND_URL:=https://ahmedmyportofilo.netlify.app}"
: "${SANCTUM_STATEFUL_DOMAINS:=ahmedmyportofilo.netlify.app}"

# Ensure a usable APP_KEY is available to the PHP process. Platform CLIs can
# mangle base64 padding (`=`) when passing env vars, so always normalize here.
case "${APP_KEY:-}" in
  base64:????????????????*)
    ;;
  *)
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    ;;
esac

# Persist the platform's env vars into .env — `php artisan serve` spawns its
# own PHP built-in-server subprocess (Symfony Process) that does NOT
# reliably inherit arbitrary orchestrator-injected env vars the way a
# directly-exec'd process does; it does reliably read .env.
set_env() {
  key="$1"
  value="$2"
  [ -z "$value" ] && return 0
  TMP_ENV="$(mktemp)"
  grep -v "^${key}=" .env > "$TMP_ENV" || true
  echo "${key}=${value}" >> "$TMP_ENV"
  mv "$TMP_ENV" .env
}

set_env APP_KEY "${APP_KEY}"
set_env APP_URL "${APP_URL:-}"
set_env APP_ENV "${APP_ENV:-}"
set_env APP_DEBUG "${APP_DEBUG:-}"
set_env FRONTEND_URL "${FRONTEND_URL:-}"
set_env SANCTUM_STATEFUL_DOMAINS "${SANCTUM_STATEFUL_DOMAINS:-}"
set_env LOG_CHANNEL "${LOG_CHANNEL:-}"
set_env DB_CONNECTION "${DB_CONNECTION:-}"
set_env SESSION_DRIVER "${SESSION_DRIVER:-}"
set_env CACHE_STORE "${CACHE_STORE:-}"
set_env QUEUE_CONNECTION "${QUEUE_CONNECTION:-}"
set_env MAIL_MAILER "${MAIL_MAILER:-}"

export APP_KEY

php artisan config:clear --no-interaction >/dev/null 2>&1 || true

# HTTPS demos behind Render need secure cookies when APP_URL is https.
case "${APP_URL:-}" in
  https://*)
    export SESSION_SECURE_COOKIE="${SESSION_SECURE_COOKIE:-true}"
    set_env SESSION_SECURE_COOKIE "${SESSION_SECURE_COOKIE:-true}"
    ;;
esac

php artisan migrate --force --no-interaction

# Seed only on first boot (empty users) or when SEED_ON_START=true
SHOULD_SEED="${SEED_ON_START:-false}"
if [ "$SHOULD_SEED" = "true" ]; then
  php artisan db:seed --force --no-interaction
elif [ -f database/database.sqlite ]; then
  COUNT="$(php -r "
    try {
      \$pdo = new PDO('sqlite:database/database.sqlite');
      \$n = (int) \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
      echo \$n;
    } catch (Throwable \$e) {
      echo '0';
    }
  ")"
  if [ "$COUNT" = "0" ]; then
    php artisan db:seed --force --no-interaction
  fi
else
  php artisan db:seed --force --no-interaction
fi

php artisan storage:link --force >/dev/null 2>&1 || true

PORT="${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="${PORT}"
