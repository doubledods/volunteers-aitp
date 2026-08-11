# Production Server Setup (Debian 12)

This guide covers setting up the Volunteer Database on a fresh Debian 12 server. By the end, you'll have the application running behind nginx on port 80 with MariaDB for the database, all managed through Docker.

## 1. Install Dependencies

As root, install git and Docker:

```sh
apt update && apt install -y git curl

# Install Docker using the official convenience script
curl -fsSL https://get.docker.com | sh
```

## 2. Create a Dedicated User

Create a non-root user to own and run the application. This user's UID/GID will be used inside the Docker container for PHP-FPM file permissions.

```sh
adduser voldb --disabled-password --gecos ""
usermod -aG docker voldb
```

`--disabled-password` means the account has no password to log in with; you reach it by `su - voldb` from root, or over SSH with a key. `--gecos ""` skips the interactive full-name / phone prompts, which are meaningless for a service account.

**This account is intentionally not given sudo.** It runs the PHP process, so if the application is ever compromised, an attacker landing as `voldb` should not have an easy path to root. Anything needing root — installing packages, issuing certificates, editing crontabs — is done from a separate root login instead.

Note that `voldb` is added to the `docker` group, which is itself effectively root-equivalent on most systems (a member can mount the host filesystem into a container). It's a real trade-off, accepted here so the application owner can manage its own containers. If you want to close that gap, look into rootless Docker or a socket proxy.

Log out and back in (or reboot) for the docker group membership to take effect, then switch to the new user:

```sh
su - voldb
```

Verify Docker access:

```sh
docker ps
```

## 3. Clone and Configure

```sh
git clone https://github.com/playasoft/volunteers.git
cd volunteers

# Create environment config
cp .env.example .env

# Set UID/GID to match this user
sed -i "s/^UID=.*/UID=$(id -u)/" .env && sed -i "s/^GID=.*/GID=$(id -g)/" .env

# Generate a secure database password and set it
DB_PASS=$(openssl rand -hex 24)
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
echo "Generated DB password: $DB_PASS"
echo "Save this somewhere safe — it won't be shown again."
```

Now edit `.env` to set your site-specific values:

```sh
nano .env
```

Update at minimum:
- `SITE_NAME` — your organization's name
- `SITE_URL` — the public URL (e.g. `http://volunteer.denverburners.org`)
- `NGINX_PORT` — leave as 80 for production, or adjust if behind a reverse proxy

## 4. Build and Start

```sh
docker compose build
docker compose up -d
```

Verify all three containers are running:

```sh
docker compose ps
```

## 5. Install and Set Up the Application

```sh
# Install PHP dependencies
docker compose exec app composer install

# Generate application key
docker compose exec app php artisan key:generate

# Run database migrations
docker compose exec app php artisan migrate

# Seed initial roles
docker compose exec app php artisan db:seed

# Build frontend assets
docker compose exec app cp resources/js/config.example.js resources/js/config.js
docker compose exec app npm install
docker compose exec app npm run build
```

## 6. Verify

The site should now be accessible on port 80 (or whatever `NGINX_PORT` you configured):

```sh
curl -I http://localhost
```

You should see a `200 OK` response. Visit the site in a browser to confirm everything is working, then register your first admin account.

## 7. Set Up HTTPS with Let's Encrypt

Once your domain's DNS is pointing at the server, you can enable HTTPS. The project uses a Docker Compose override (`docker-compose.production.yml`) to layer HTTPS on top of the base development config, keeping the two environments cleanly separated.

### Install Certbot

As root:

```sh
apt install -y certbot
```

### Configure Domains

As voldb, add your domain(s) to `.env`. Multiple domains are supported with a single certificate (SAN):

```sh
cd ~/volunteers
nano .env
```

Set the `SSL_DOMAINS` variable (comma-separated, first domain is primary):

```
SSL_DOMAINS=denverburners.playa.software
```

Or for multiple domains:

```
SSL_DOMAINS=denverburners.playa.software,volunteer.denverburners.org
```

Also update `SITE_URL` to use HTTPS:

```
SITE_URL=https://denverburners.playa.software
```

### Issue Certificate and Enable HTTPS

Certificate issuance needs root, because certbot writes to `/etc/letsencrypt`. The `voldb` account is deliberately unprivileged and has **no sudo access** — since it runs the PHP process, keeping it unable to escalate limits the damage if the application is ever compromised. So run this part from a root shell rather than with `sudo`.

**As root:**

```sh
cd /home/voldb/volunteers
./scripts/ssl-setup.sh
```

The script reads `SSL_DOMAINS` from `.env`, issues the certificate, generates the nginx HTTPS config, and starts the containers with SSL enabled.

That runs two steps — `issue` (gets the cert from Let's Encrypt) and `configure` (generates the nginx config and starts the production containers). You can also run them individually:

```sh
# As root:
./scripts/ssl-setup.sh issue            # Issue or expand the certificate
./scripts/ssl-setup.sh renew            # Renew and reload nginx

# As root or voldb:
./scripts/ssl-setup.sh configure        # Generate nginx config and start with HTTPS
./scripts/ssl-setup.sh status           # Show certificate details and expiry
./scripts/ssl-setup.sh help             # Show all commands
```

When root runs `configure`, the generated `docker/nginx/production.conf` is chowned back to `voldb` so the unprivileged account can still regenerate it later.

### How Verification Works

Certbot uses **webroot** mode rather than standalone mode. Instead of binding port 80 itself (which would require stopping nginx), certbot writes challenge files into `docker/certbot/` on the host. That directory is bind-mounted into the nginx container at `/var/www/certbot`, and both nginx configs serve it at `/.well-known/acme-challenge/`.

The practical result is that **certificates are issued and renewed with no downtime** — containers are never stopped.

The ACME location block is deliberately present in the development config (`docker/nginx/default.conf`) as well as the production one. On a first run there is no certificate yet, so the HTTPS config can't load — nginx would fail to start referencing a cert that doesn't exist. Serving challenges from the plain-HTTP config solves that bootstrap problem.

### Adding or Changing Domains

The certificate is a SAN certificate: one cert covering every domain in `SSL_DOMAINS`. Add the new domain to the list in `.env`:

```
SSL_DOMAINS=denverburners.playa.software,volunteer.denverburners.org
```

Then reissue and reconfigure, **as root**:

```sh
cd /home/voldb/volunteers
./scripts/ssl-setup.sh
```

The script passes `--expand`, so certbot adds the new names to the existing certificate without prompting. The cert directory stays named after the primary (first) domain, so the nginx config doesn't need to change.

Make sure DNS for every domain in the list already points at this server — certbot verifies each one and the whole request fails if any of them can't be reached.

### Automatic Certificate Renewal

As root, set up a cron job:

```sh
crontab -e
```

Add:

```
0 3 * * * /home/voldb/volunteers/scripts/ssl-setup.sh renew >> /var/log/certbot-renewal.log 2>&1
```

This checks daily at 3 AM. Certbot only acts when a certificate is within 30 days of expiry, and on success reloads nginx in place via a deploy hook. Nothing is stopped and no requests are dropped.

Test the renewal path without waiting for expiry, **as root**:

```sh
certbot renew --dry-run
```

## Maintenance

**View logs:**
```sh
cd ~/volunteers
docker compose -f docker-compose.yml -f docker-compose.production.yml logs -f
```

**Restart after server reboot:**
The containers are set to `restart: unless-stopped`, so they will come back automatically after a reboot. If they don't:

```sh
cd ~/volunteers
docker compose -f docker-compose.yml -f docker-compose.production.yml up -d
```

**Pull updates:**
```sh
cd ~/volunteers
git pull
docker compose build
docker compose -f docker-compose.yml -f docker-compose.production.yml up -d
docker compose exec app composer install
docker compose exec app php artisan migrate
docker compose exec app npm run build
```

## Next Steps

- [Import legacy data from previous events](https://github.com/playasoft/volunteers/issues) (TODO)