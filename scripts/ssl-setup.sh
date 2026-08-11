#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# SSL Setup — Issue, renew, and configure SSL certificates
#
# Uses certbot's webroot mode: ACME challenge files are written into a
# directory that the running nginx container already serves, so certificates
# can be issued and renewed with NO downtime. Containers are never stopped.
#
# Reads SSL_DOMAINS from .env to determine which domains to cover.
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_DIR/.env"
WEBROOT="$PROJECT_DIR/docker/certbot"
NGINX_CONTAINER="voldb-nginx"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

# ---- Read SSL_DOMAINS from .env ----

if [[ ! -f "$ENV_FILE" ]]; then
    echo -e "${RED}Error: .env file not found at $ENV_FILE${NC}"
    echo "Run 'cp .env.example .env' and set SSL_DOMAINS first."
    exit 1
fi

SSL_DOMAINS=$(grep -E "^SSL_DOMAINS=" "$ENV_FILE" | cut -d'=' -f2- | tr -d '"' | tr -d "'")

if [[ -z "$SSL_DOMAINS" ]]; then
    echo -e "${RED}Error: SSL_DOMAINS is not set in .env${NC}"
    echo ""
    echo "Add a line like this to your .env file:"
    echo "  SSL_DOMAINS=example.com,www.example.com"
    exit 1
fi

# Parse domains: first is primary and determines the cert directory name
IFS=',' read -ra DOMAINS <<< "$SSL_DOMAINS"
PRIMARY_DOMAIN="${DOMAINS[0]}"

CERTBOT_DOMAINS=""
for domain in "${DOMAINS[@]}"; do
    CERTBOT_DOMAINS="$CERTBOT_DOMAINS -d $domain"
done

echo -e "${CYAN}SSL Configuration${NC}"
echo -e "  Primary domain: ${GREEN}$PRIMARY_DOMAIN${NC}"
if [[ ${#DOMAINS[@]} -gt 1 ]]; then
    echo -e "  Additional domains: ${GREEN}${DOMAINS[*]:1}${NC}"
fi
echo ""

# ---- Helpers ----

require_root() {
    if [[ $EUID -ne 0 ]]; then
        echo -e "${RED}Error: this command needs root (certbot writes to /etc/letsencrypt).${NC}"
        echo ""
        echo "The project owner account is intentionally unprivileged, so run this"
        echo "from a root shell instead:"
        echo "  $SCRIPT_DIR/ssl-setup.sh ${1:-}"
        exit 1
    fi
}

compose() {
    # Run compose from the project dir. Which user invokes this doesn't affect
    # the container's internal user — that's fixed by the UID/GID build args.
    cd "$PROJECT_DIR"
    if [[ -f docker/nginx/production.conf ]]; then
        docker compose -f docker-compose.yml -f docker-compose.production.yml "$@"
    else
        docker compose "$@"
    fi
}

nginx_running() {
    docker ps --filter "name=^${NGINX_CONTAINER}$" --filter "status=running" --quiet | grep -q .
}

reload_nginx() {
    if nginx_running; then
        echo "Reloading nginx..."
        docker exec "$NGINX_CONTAINER" nginx -s reload
    fi
}

show_help() {
    cat <<EOF
Usage: ./scripts/ssl-setup.sh [command]

Commands:
  issue       Issue or expand a certificate via webroot (no downtime)
  renew       Renew certificates and reload nginx (no downtime)
  configure   Generate the nginx HTTPS config and start production containers
  status      Show certificate details and expiry

With no command, runs: issue + configure.

Notes:
  issue and renew must be run from a root shell — certbot writes to
  /etc/letsencrypt. The project owner account is deliberately unprivileged
  and has no sudo access, so log in as root (or `su -`) to run them.

  configure works as either root or the project owner.

  Renewal is safe to run from root's crontab — if nothing is due for
  renewal, certbot exits without touching anything.
EOF
}

# ---- Commands ----

issue_cert() {
    require_root "issue"

    mkdir -p "$WEBROOT"

    # nginx must be serving HTTP so it can answer the ACME challenge.
    # On a first run there's no cert yet, so the HTTPS config can't load —
    # fall back to the plain-HTTP base config, which also serves challenges.
    if ! nginx_running; then
        echo -e "${YELLOW}nginx isn't running — starting it to serve the challenge...${NC}"
        cd "$PROJECT_DIR"
        if [[ -d "/etc/letsencrypt/live/$PRIMARY_DOMAIN" && -f docker/nginx/production.conf ]]; then
            docker compose -f docker-compose.yml -f docker-compose.production.yml up -d
        else
            docker compose up -d
        fi

        # Give nginx a moment to bind before certbot probes it
        sleep 3
    fi

    if ! nginx_running; then
        echo -e "${RED}Error: nginx failed to start. Check: docker compose logs nginx${NC}"
        exit 1
    fi

    echo -e "${YELLOW}Requesting certificate via webroot...${NC}"
    certbot certonly \
        --webroot -w "$WEBROOT" \
        --expand \
        --keep-until-expiring \
        $CERTBOT_DOMAINS

    echo -e "${GREEN}Certificate issued.${NC}"
}

renew_cert() {
    require_root "renew"

    # certbot remembers the webroot authenticator per-domain, so plain
    # `renew` reuses it. Reload nginx only when a cert actually changed.
    certbot renew --deploy-hook "docker exec $NGINX_CONTAINER nginx -s reload"

    echo -e "${GREEN}Renewal check complete.${NC}"
}

configure() {
    if [[ ! -d "/etc/letsencrypt/live/$PRIMARY_DOMAIN" ]]; then
        echo -e "${RED}Error: no certificate found for $PRIMARY_DOMAIN${NC}"
        echo "Run this from a root shell: $SCRIPT_DIR/ssl-setup.sh issue"
        exit 1
    fi

    echo -e "${YELLOW}Generating nginx HTTPS config...${NC}"
    export SSL_DOMAIN="$PRIMARY_DOMAIN"
    envsubst '${SSL_DOMAIN}' \
        < "$PROJECT_DIR/docker/nginx/production.conf.template" \
        > "$PROJECT_DIR/docker/nginx/production.conf"

    # If root generated the file, hand ownership back to the project owner so
    # the unprivileged account can regenerate it later without needing root.
    if [[ $EUID -eq 0 ]]; then
        OWNER="$(stat -c '%U:%G' "$PROJECT_DIR")"
        chown "$OWNER" "$PROJECT_DIR/docker/nginx/production.conf"
    fi

    echo -e "  Written to: ${GREEN}docker/nginx/production.conf${NC}"
    echo ""

    echo -e "${YELLOW}Starting containers with HTTPS...${NC}"
    compose up -d

    echo ""
    echo -e "${GREEN}HTTPS is live!${NC}"
    for domain in "${DOMAINS[@]}"; do
        echo -e "  https://$domain"
    done
}

show_status() {
    echo -e "${YELLOW}Certificate status:${NC}"
    if [[ -d "/etc/letsencrypt/live/$PRIMARY_DOMAIN" ]]; then
        echo -e "  Cert directory: ${GREEN}/etc/letsencrypt/live/$PRIMARY_DOMAIN${NC}"
        openssl x509 -in "/etc/letsencrypt/live/$PRIMARY_DOMAIN/fullchain.pem" -noout \
            -subject -enddate -ext subjectAltName 2>/dev/null \
            || echo -e "  ${RED}Could not read certificate (need root?)${NC}"
    else
        echo -e "  ${RED}No certificate found for $PRIMARY_DOMAIN${NC}"
        echo "  Run this from a root shell: $SCRIPT_DIR/ssl-setup.sh issue"
    fi
}

# ---- Main ----

case "${1:-all}" in
    issue)      issue_cert ;;
    renew)      renew_cert ;;
    configure)  configure ;;
    status)     show_status ;;
    all)        issue_cert; echo ""; configure ;;
    help|--help|-h) show_help ;;
    *)
        echo -e "${RED}Unknown command: $1${NC}"
        show_help
        exit 1
        ;;
esac