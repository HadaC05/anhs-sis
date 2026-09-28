# Render deployment on the free plan

The Docker web service runs Nginx, PHP-FPM, and one Laravel database queue worker
under Supervisor. No separate Render Background Worker service is needed.
Supervisor restarts the queue process if it exits, and startup runs migrations
before accepting jobs. Imports remain asynchronous and keep their live progress.

## Update your existing Render website

1. Commit and push the changes to the branch connected to your existing Docker
   web service (usually `main`). Keep the service on the Free plan.
2. Open **anhs-sis > Environment**, retain your existing `APP_KEY` and database
   settings, and add or confirm these values:

   ```dotenv
   APP_ENV=production
   QUEUE_CONNECTION=database
   RUN_QUEUE_WORKER=true
   AUTO_START_LOCAL_IMPORT_WORKER=false
   CLASS_LIST_IMPORT_TIMEOUT=240
   DB_QUEUE_RETRY_AFTER=300
   CACHE_STORE=database
   LOG_CHANNEL=stderr
   ```

3. Save and deploy. Use **Manual Deploy > Deploy latest commit** if auto-deploy
   has not started. Leave the Docker Command override empty so the image's
   startup script runs. Do not replace it with `queue:work`.
4. In **Logs**, check for `queue-worker` entering the `RUNNING` state, as well as
   `nginx` and `php-fpm`. Upload a small class list and confirm the completed
   student count. Import jobs also write RUNNING/DONE messages to these logs.

The default Docker image enables the worker even without syncing the Blueprint.
For Blueprint-managed services, sync `render.yaml` to also apply its environment
values and 300-second graceful shutdown period. For manually created services,
configure the same shutdown period through Render's service settings/API if
available, and avoid redeploying during an import.

If you already created a separate paid worker, do not run both unintentionally.
Set `RUN_QUEUE_WORKER=false` only when that separate worker is running instead.
No separate worker service is created by the current Blueprint.

## New deployments

Create a Blueprint from this repository. `render.yaml` specifies a free web
service and a free PostgreSQL database. If you already have a database, keep its
connection and data; do not create a replacement just to enable imports.
Set `APP_URL` and `ASSET_URL` to the public website URL. Do not commit `.env` or
replace an existing `APP_KEY`. The startup script applies migrations automatically.

## Free-plan limits

The queue worker shares the web service's CPU and memory and runs only while the
service is awake. Render sleeps free web services after 15 minutes without inbound
traffic; opening the website wakes the service and starts the worker again.
Pending jobs and temporary import files are stored in PostgreSQL, not local disk.
A job interrupted by a forced restart can eventually be marked failed; already
saved students remain, and re-uploading reuses students and enrollments.

Imports have a 240-second limit, below the 300-second retry interval. A timeout
marks the import failed instead of leaving it processing indefinitely. Very large
class lists may need smaller uploads. One worker processes queued jobs in order.

Free Render PostgreSQL expires 30 days after creation. Free services also lose
local files on restart/redeploy, so student photos and other local documents need
external persistent storage for lasting use. The queued class-list payload uses
the database and does not depend on a shared disk.

References: [Render free-plan limits](https://render.com/docs/free),
[Laravel queue timeouts](https://laravel.com/docs/12.x/queues#timeouts), and
[Render shutdown behavior](https://render.com/docs/deploys#graceful-shutdown).
