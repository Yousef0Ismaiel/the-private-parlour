# The Parlour

A private Laravel 13 and PHP landing page for a two-person brand experiment. The neutral members’ entrance keeps the reveal for after sign-in. Each person gets their own invitation, password and authenticator entry.

## Start on your computer

With Docker Desktop running, open a terminal in the extracted project folder and run the following commands.

```sh
docker compose up --build -d
docker compose exec --user www-data app php artisan member:invite yousef
docker compose exec --user www-data app php artisan member:invite friend
```

Open **http://127.0.0.1:8000** on the computer running Docker. Choose **Use an invitation** and enter your own code. Give your friend only their own invitation. A code expires in 24 hours. Reissuing an unused invitation for the same username replaces the old one.

Scan the QR code with Google Authenticator, Aegis, or another TOTP app. On the same phone, open **Enter the key manually** and add a time-based entry instead. Choose your password and enter the current six-digit code to see the page. On subsequent visits, sign in with your username and password, then enter a fresh app code. If you sign straight back in after setup, wait for the number on your phone to change.

Docker stores the accounts and application key in the named volume `parlour-data`. Use `docker compose stop` to stop the app and `docker compose start` to return later.

## Run with PHP directly

The release ZIP includes the production Composer dependencies. You can run it on a computer with PHP 8.3 or later, SQLite PDO, XMLWriter, Argon2id and the standard Laravel extensions.

```sh
php setup.php
php artisan member:invite yousef
php artisan member:invite friend
php artisan serve --host=127.0.0.1 --port=8000
```

If you are starting from source without the bundled dependencies, run `composer install --no-dev` before setup. The setup command preserves existing accounts and settings. Passwords and authenticator keys are chosen or generated during enrollment. The package contains no pre-enrolled accounts.

## Test together on Render

The included `render.yaml` provisions a free Docker web service and a free PostgreSQL database in Frankfurt. Accounts and authenticator pairings survive web-service restarts. Render’s free database expires after 30 days. [Free instance details](https://render.com/docs/free)

1. Put the contents of the extracted `the-parlour` directory in a **private** Git repository with `render.yaml` at its root. Give Render access to that repository. The supplied `.gitignore` excludes local accounts, keys and installed dependencies.
2. In the local project directory, run the two commands below. Each prints a private invitation code for the member and a separate SHA-256 digest for the host. You can prepare these before setting up a local database.

```sh
php artisan member:invite yousef --prepare
php artisan member:invite friend --prepare
```

3. Create a Render Blueprint from the repository. Enter the two **digests** as `PARLOUR_YOUSEF_INVITE_HASH` and `PARLOUR_FRIEND_INVITE_HASH`. Check that the web service and database both use the Free plan before applying. Render generates the stable application encryption key and connects the database over its private network.
4. When the service is live, open its HTTPS address. Each person selects **Use an invitation** and enters their own **invitation code**. The code is valid for 24 hours after the running service installs its digest. If an unused code expires, prepare a new one for that person, replace its digest in the service environment and redeploy. Repeated restarts preserve active accounts and keep the original deadline for unchanged invitations.

## Other HTTPS hosts

The loopback address above works on the host computer. For a shared link, deploy the app behind HTTPS on a PHP host, or run the Docker image behind an HTTPS reverse proxy. Set `APP_URL` to the HTTPS origin and `SESSION_SECURE_COOKIE=true`. Send your friend the HTTPS address and their individual invitation privately.

For the container, mount a persistent disk at `/var/lib/parlour` and route traffic to container port 80. It migrates SQLite at startup. The Docker Compose file is for local use. On another PHP host, use `public/` as the web root, set `APP_ENV=production`, `APP_DEBUG=false`, and keep the same `APP_KEY` across restarts. Keep the database and key together in private backups. The web process needs write access to SQLite, `storage/`, and `bootstrap/cache/`.

## Access and verification

Only the administrator’s Artisan command issues invitations. The server checks the password before creating a five-minute code challenge. The landing page, its stylesheet and cover image all require the completed session. Each accepted time step is consumed once per account. Pairing codes are generated on the server and a 15-minute setup session is bound to the current invitation. The session store and the saved authenticator seeds are encrypted. Authentication responses and private media carry `Cache-Control: no-store`.

To run the feature tests on a PHP development machine, install the development dependencies and run PHPUnit.

```sh
composer install
php vendor/bin/phpunit
```

## Art and type

The entrance uses a detail of a late seventeenth-century chamberlain’s key from The Metropolitan Museum of Art. The museum makes the photograph available under its Open Access program. [Collection record](https://www.metmuseum.org/art/collection/search/196603)

The cover uses *Vase of Flowers* by Jan Davidsz de Heem from the National Gallery of Art’s public-domain collection. [Collection record](https://www.nga.gov/artworks/46097-vase-flowers)

DM Sans and Instrument Serif are served locally. Their SIL Open Font Licenses are in `public/assets/fonts/`.
