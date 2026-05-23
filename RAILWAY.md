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

## Deploy checklist

1. Keep `railway.json` in the repository root.
2. Add your production environment variables.
3. Connect a database.
4. Deploy.
5. Run migrations after the first deploy:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## Notes

- The existing [docker-compose.yaml](docker-compose.yaml) remains the local development stack.
- The same [Dockerfile](Dockerfile) now serves both Railway and local compose.
- If you ever change the Dockerfile path, update [railway.json](railway.json) too.