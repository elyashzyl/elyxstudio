# Deploy ELYX Studio with Dokploy

## 1. Connect the repository

Create a Dokploy **Compose** project and connect the GitHub repository `elyashzyl/elyxstudio` on the `master` branch. Set the Compose file path to `docker-compose.dokploy.yml` and deploy from the repository root. This Compose file builds the application from the repository's `Dockerfile` and uses persistent SQLite storage.

Do not use `docker-compose.yml` for a public production deployment: it is intended for local development and enables debug mode.

## 2. Add environment variables

Configure these variables in the Compose project's environment settings before deploying:

| Variable | Value |
| --- | --- |
| `APP_URL` | Public app URL, including scheme, e.g. `https://learn.example.com` |
| `APP_DOMAIN` | Same public host only, e.g. `learn.example.com` (no scheme or path) |
| `APP_KEY` | Laravel key in `base64:...` format; generate once with `php artisan key:generate --show` |
| `REVERB_APP_KEY` | A random non-empty identifier, e.g. `elyxstudio-prod-<random>` |
| `REVERB_APP_SECRET` | A strong random secret, different from the app key |

Optional variables default to `ELYX Studio` for the app name and `elyxstudio` for Reverb's app ID. Override `REVERB_APP_ID` if desired.

Keep `APP_KEY` unchanged on future deployments or encrypted application data will no longer be readable. Keep the same Reverb key/secret pair across app and client configuration.

## 3. Configure the application domain

After creating the Compose service, configure the Dokploy domain/proxy for service `app`, container port `80`, with HTTPS enabled. Do not expose PostgreSQL. Reverb runs inside the app container on port `8080`; Nginx proxies `/app` WebSocket requests to it, so do not create a separate public Reverb port or domain.

## 4. Deploy and verify

Deploy the Compose project. The app runs Laravel migrations on startup. Check the app logs for `Database migrations completed successfully.` and confirm the health check passes at `/up` on the configured domain.

The Compose project uses persistent volumes for the SQLite database and Laravel storage. Back up those volumes regularly. Removing the volumes deletes the database and uploaded files.

## Notes

- `APP_ENV=production`, `APP_DEBUG=false`, and `RUN_MIGRATIONS=true` are set in the deployment Compose file.
- Set `APP_URL` to the final HTTPS URL before first launch so generated links and callbacks use the right host.
- `APP_DOMAIN` is used for runtime Reverb connection settings; it must match the public app domain.
- SQLite is configured to preserve current local database-engine behavior and simplify single-container deployment. Do not run multiple app instances against this SQLite volume.
- Vite assets are built into the Docker image; no separate Node.js service is needed.
- Build the application service from the Dockerfile with the repository root as the build context.
