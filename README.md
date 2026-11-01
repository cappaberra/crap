# C.R.A.P. v2 — local Docker edition

Your original PHP connectivity dashboard, recovered from the live trunk HEAD of the supplied CVS/RCS archives and updated for PHP 8.4. Apache serves the dashboard, MariaDB stores history, and a separate worker probes connectivity even when the browser is closed.

## Start

Install and start Docker Desktop (Mac/Windows), or Docker Engine with the Compose v2 plugin (Linux). Clone this repository and start the stack:

```sh
git clone https://github.com/cappaberra/crap.git
cd crap
docker compose up -d --build
```

Open **http://localhost:8080**. The initial image download/build can take several minutes. Database setup happens automatically on first launch. No CVS client, local PHP, local database, or cron job is required.

```sh
docker compose ps
docker compose logs --tail=50 monitor
docker compose logs --tail=50 web db
```

## Settings

The defaults work without a `.env` file. For custom settings, copy `.env.example` to `.env` (`cp .env.example .env` in macOS/Linux, `Copy-Item .env.example .env` in PowerShell), edit it, then run `docker compose up -d` again.

| Setting | Default | Purpose |
|---|---|---|
| APP_PORT | 8080 | Dashboard port, bound to this computer's loopback address |
| TZ | America/Boise | Dashboard and worker timestamps |
| MONITOR_HOSTS | 1.1.1.1,8.8.8.8,9.9.9.9 | Comma-separated ICMP targets; replace with your own targets if desired |
| CHECK_INTERVAL | 10 | Seconds to wait after each probe cycle |
| OUTAGE_THRESHOLD | 0.335 | Down when responding hosts / total hosts is less than or equal to this fraction; must be >=0 and <1 |
| EXPECTED_UPTIME | 0.9995 | Dashboard uptime target; use a value greater than 0 and less than 1 |
| ADMIN_PASSWORD | blank | Optional history administration password; blank disables deletion |
| DB_PASSWORD | local-crap2-password | Local application database password |
| DB_ROOT_PASSWORD | local-root-password | Local database administrator password |

With three default targets, zero or one responding target means an outage; two or three means up. Each target has a two-second timeout and is checked sequentially, so a cycle can take up to roughly six seconds plus CHECK_INTERVAL. Browser data refreshes every second. ICMP blocking, a VPN, or Docker's own networking can affect results; this measures connectivity from the container. Test your targets with `docker compose exec monitor ping -c 3 1.1.1.1`.

To delete completed history entries, set ADMIN_PASSWORD, recreate the services, and use **History administration** on the dashboard. Authentication is session-based with a form token for deletion. An active outage cannot be deleted. The old `?nlp=` secret and caller-supplied `us=1` flag no longer authorize deletion.

The dashboard is intended for local use and is bound to `127.0.0.1`. The database has no published port. Keep that binding unless you deliberately add suitable authentication/TLS for wider access. The sample database passwords are local defaults; choose your own before first launch if desired. Changing database password variables after the volume has initialized does not change stored MariaDB credentials.

## Stop, resume, rebuild

```sh
docker compose stop
docker compose start
# After editing PHP or Dockerfile:
docker compose up -d --build
# Remove containers and network, retaining database history:
docker compose down
```

Database history lives in the named `db_data` volume. `docker compose down -v` permanently removes that history and starts fresh next time; use it only for an intentional reset. Keep the same project directory/name to reuse the same Compose volume.

Monitoring runs only while Docker and this stack are running. A sleeping/shut-down computer is not monitored. An outage open when the worker stops will include the unobserved interval when recovery is later detected. If reliable 24/7 monitoring matters, run this on an always-on machine.

## Backup and restore

These commands use the password already present inside the database container. Run from the project directory. On Windows, use Command Prompt or a shell that preserves redirected bytes.

```sh
docker compose exec -T db sh -c 'exec mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction crap2' > crap2-backup.sql
```

Restore replaces tables/history in the target database. Stop the web and monitor services first, then restart them after import:

```sh
docker compose stop web monitor
docker compose exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" crap2' < crap2-backup.sql
docker compose start web monitor
```

## Validation on your computer

After startup, check the service health, load the dashboard, and run:

```sh
docker compose exec -T web php < tests/smoke.php
```

On Windows, run that command in Command Prompt, or in PowerShell use `Get-Content -Raw tests/smoke.php | docker compose exec -T web php`.

The smoke test creates connection-local temporary tables, so it does not modify your saved history. It tests healthy probes, outage detection, deduplication, recovery durations, fractional host ratios, ID gaps, and the actual dashboard response. Check `VALIDATION.md` for checks performed during conversion and remaining runtime limitations.

## What's included and changed

- `app/`: runnable modernized dashboard, native JSON/fetch endpoint, configurable database connection, administration, and worker.
- `db/001-init.sql`: current schema with InnoDB transactions, fractional host ratios, and first-run timestamp.
- `docker/`, `Dockerfile`, `compose.yaml`: web, worker, and persistent database setup.
- `tools/extract_cvs.py`: byte-preserving trunk HEAD extractor. Dead HEAD revisions are excluded. It does not export branches or replay older deltas; the original archives remain in the separately supplied conversion ZIP.
- `tests/smoke.php`: non-destructive local integration test.

The archive contains schema and seed rows, **not historical outage data**. This edition starts a new recording period; importing a separate legacy database would require a schema/data migration. The original archive and recovered legacy files contain historical hardcoded credentials, so they are excluded from this repository. They remain in the separately supplied conversion ZIP.

The original ISP logo, styling, copyright attribution, and main dashboard remain. The old JSON library and AJAX library are replaced by native JSON and browser fetch. Removed mysql_* calls, short PHP tags, hardcoded filesystem/database paths, fresh-install null/division errors, integer-truncated response ratios, incorrect outage ID assumptions, and the client-controlled deletion authorization have been addressed. The overlay now scales against the configured acceptable downtime allowance, and the uptime text explicitly describes the recording period.

The old `rrr.php` / `rrr.sh` recovery automation is preserved only in the separately supplied conversion ZIP. It references missing `functions.inc`, obsolete Gentoo networking/PPPoE/firewall services, and a fixed host path. It is not installed or executed: a container should not restart your computer's network stack. Outage detection and recovery logging remain automatic.

The database image uses its bundled `healthcheck.sh --connect --innodb_initialized` startup checks: https://mariadb.com/docs/server/server-management/automated-mariadb-deployment-and-administration/docker-and-mariadb/using-healthcheck-sh
