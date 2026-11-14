#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# One-shot Magento bootstrap, run by the `setup` service on `docker compose up`.
#
#  * No app/etc/env.php  -> runs setup:install using the values from .env
#  * env.php exists      -> syncs base URL, admin path and admin user from .env
#
# Set MAGENTO_SYNC_ON_UP=0 in .env to skip the sync on an existing install.
# ---------------------------------------------------------------------------
set -euo pipefail
cd /var/www/html

SITE_DOMAIN="${SITE_DOMAIN:-epoxy.local}"
BASE_URL="https://${SITE_DOMAIN}/"
ADMIN_FRONTNAME="${ADMIN_FRONTNAME:-admin}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@${SITE_DOMAIN}}"
ADMIN_FIRSTNAME="${ADMIN_FIRSTNAME:-Admin}"
ADMIN_LASTNAME="${ADMIN_LASTNAME:-User}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"

magento() { php bin/magento "$@"; }
log() { echo "[setup] $*"; }

if [ ! -f vendor/autoload.php ]; then
    log "vendor/ missing - running composer install"
    composer install --no-interaction --no-progress
fi

if [ ! -f app/etc/env.php ]; then
    if [ -z "$ADMIN_PASSWORD" ]; then
        log "ERROR: ADMIN_PASSWORD must be set in .env for a fresh install"
        exit 1
    fi
    log "app/etc/env.php missing - installing Magento for ${BASE_URL}"
    key_arg=()
    [ -n "${MAGENTO_CRYPT_KEY:-}" ] && key_arg=(--key="$MAGENTO_CRYPT_KEY")
    magento setup:install -n \
        --base-url="$BASE_URL" \
        --base-url-secure="$BASE_URL" \
        --use-secure=1 --use-secure-admin=1 --use-rewrites=1 \
        --backend-frontname="$ADMIN_FRONTNAME" \
        --admin-user="$ADMIN_USER" \
        --admin-password="$ADMIN_PASSWORD" \
        --admin-email="$ADMIN_EMAIL" \
        --admin-firstname="$ADMIN_FIRSTNAME" \
        --admin-lastname="$ADMIN_LASTNAME" \
        --db-host=db \
        --db-name="${DB_NAME:-magento}" \
        --db-user="${DB_USER:-magento}" \
        --db-password="${DB_PASSWORD:-magento}" \
        --search-engine=opensearch --opensearch-host=opensearch --opensearch-port=9200 \
        --cache-backend=redis --cache-backend-redis-server=redis --cache-backend-redis-db=0 \
        --page-cache=redis --page-cache-redis-server=redis --page-cache-redis-db=1 \
        --session-save=redis --session-save-redis-host=redis --session-save-redis-db=2 \
        --amqp-host=rabbitmq --amqp-port=5672 --amqp-virtualhost=/ \
        --amqp-user="${RABBITMQ_USER:-magento}" \
        --amqp-password="${RABBITMQ_PASSWORD:-magento}" \
        --document-root-is-pub=true \
        "${key_arg[@]}"
    log "install complete"
    exit 0
fi

if [ "${MAGENTO_SYNC_ON_UP:-1}" != "1" ]; then
    log "MAGENTO_SYNC_ON_UP=0 - skipping config sync"
    exit 0
fi

log "syncing base URL -> ${BASE_URL}"
magento config:set web/unsecure/base_url "$BASE_URL"
magento config:set web/secure/base_url "$BASE_URL"
magento config:set web/secure/use_in_frontend 1
magento config:set web/secure/use_in_adminhtml 1
magento downloadable:domains:add "$SITE_DOMAIN" >/dev/null

log "syncing admin path -> /${ADMIN_FRONTNAME}"
magento setup:config:set -n --backend-frontname="$ADMIN_FRONTNAME"

if [ -n "$ADMIN_PASSWORD" ]; then
    log "syncing admin user '${ADMIN_USER}'"
    # admin:user:create refuses an existing username with a different email,
    # so update the email first
    php -r '
        $c = (include "app/etc/env.php")["db"]["connection"]["default"];
        $pdo = new PDO("mysql:host={$c["host"]};dbname={$c["dbname"]}", $c["username"], $c["password"]);
        $pdo->prepare("UPDATE admin_user SET email = ? WHERE username = ?")
            ->execute([getenv("ADMIN_EMAIL") ?: $argv[1], getenv("ADMIN_USER") ?: $argv[2]]);
    ' "$ADMIN_EMAIL" "$ADMIN_USER"
    # Creates the user, or updates name/password if the username already exists
    magento admin:user:create \
        --admin-user="$ADMIN_USER" \
        --admin-password="$ADMIN_PASSWORD" \
        --admin-email="$ADMIN_EMAIL" \
        --admin-firstname="$ADMIN_FIRSTNAME" \
        --admin-lastname="$ADMIN_LASTNAME"
else
    log "ADMIN_PASSWORD empty - leaving admin user untouched"
fi

magento cache:flush >/dev/null
log "done"
