# Railway Deployment

This repository now includes a Railway-ready Docker setup for the Symfony web app.

## Files used by Railway

- [Dockerfile](Dockerfile)
- [railway.json](railway.json)
- [entrypoint.sh](entrypoint.sh)
- [docker/nginx/main.conf](docker/nginx/main.conf)
- [docker/nginx/default.conf](docker/nginx/default.conf)

## What this setup does

- Builds a single container with PHP-FPM and nginx.
- Serves the app on port `8080`.
- Uses nginx to forward Symfony requests to PHP-FPM on `127.0.0.1:9000`.
- Keeps logs on stdout and stderr so Railway can capture them.

## Railway service settings

`railway.json` forces Railway to use the repository `Dockerfile` as the service build.

Recommended environment variables:

- `APP_ENV=prod`
- `APP_DEBUG=0`
- `APP_URL=https://your-railway-domain`
- `DATABASE_URL` from your Railway MySQL service or external database
- `MAILER_DSN`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `JWT_PASSPHRASE`

If you use Railway MySQL, point `DATABASE_URL` to the Railway-provided connection string.

IMPORTANT: Railway injects service variables by service name. Do NOT hardcode `127.0.0.1` or `localhost`.

Use the Railway MySQL variable directly in the service Environment section. For example, if your MySQL service is named `MySQL` (case-sensitive), set:

```
DATABASE_URL=${{MySQL.DATABASE_URL}}
```

The `Dockerfile` no longer sets a default `DATABASE_URL`. You MUST set `DATABASE_URL` in Railway service Environment (for example `mysql://user:pass@host:3306/dbname`) or migrations will attempt to run against SQLite defaults and fail.

## Deploy checklist

1. Keep `railway.json` in the repository root.
2. Add your production environment variables.
3. Connect a database.
4. Deploy.
5. Run migrations after the first deploy:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Avoid Railway pre-deploy CLI errors

Railway allows a "Pre-deploy command" that runs before creating containers. If that command references the `railway` CLI it may fail because the CLI is not available in the build environment. To avoid this, set the project's Pre-deploy command to the repository script:

```bash
./predeploy.sh
```

The included `predeploy.sh` is a no-op wrapper that prevents the common error "The executable 'railway' could not be found." and lets the build proceed to create the container where runtime migrations (handled by `entrypoint.sh`) will run.

## Notes

- The existing [docker-compose.yaml](docker-compose.yaml) remains the local development stack.
- The same [Dockerfile](Dockerfile) now serves both Railway and local compose.
- If you ever change the Dockerfile path, update [railway.json](railway.json) too.
- For local compose, `WEB_PORT` controls the host port and defaults to `8081` so it does not collide with another service already listening on `8080`.