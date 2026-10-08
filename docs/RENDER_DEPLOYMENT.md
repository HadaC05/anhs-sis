# Render deployment on the free plan

## One-time full database reset

The Docker startup script supports a full reset without a Render shell. This
**deletes every database table and record**, then runs all migrations and the
default seeders. Back up the database first using its external URL and
`pg_dump`; the web service's `DB_URL` is an internal URL and does not work from
your computer.

1. Commit and push the intended application code. Set `RUN_DATABASE_SEEDER=false`.
2. In the web service's **Environment** page, set `RUN_DATABASE_FRESH=true` and
   `DATABASE_RESET_TOKEN` to a new unique value of at least 16 characters (for
   example, a UUID). Choose **Save, rebuild, and deploy** so Render uses the
   latest commit even if its earlier deployment failed during migration.
3. In the deploy logs, confirm `Database reset completed and recorded.` and
   that the deployment becomes live. The same token is stored as a hash in the
   database, so a restart with the same settings skips the reset.
4. Set `RUN_DATABASE_FRESH=false` and choose **Save and deploy**. Leave
   `RUN_DATABASE_SEEDER=false` so later restarts do not overwrite seeded records.

Changing `DATABASE_RESET_TOKEN` while `RUN_DATABASE_FRESH=true` requests a new
full reset. Never set both `RUN_DATABASE_FRESH` and `RUN_DATABASE_SEEDER` to true.
The default seeders create sample staff accounts with password `password`;
change or disable them before opening the site to others.

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

## Registrar grade submission notifications

The Docker Supervisor configuration also starts `php artisan schedule:work`
after migrations. Check the deployment logs for `scheduler` entering the
`RUNNING` state. No separate queue worker is needed for these in-app digests.

`registrar:grade-digest` checks each minute and sends at most one notification
per active registrar every two hours, weekdays from 08:00 up to 18:00 in
`Asia/Manila`. Each class subject and grading term counts once per digest,
regardless of the number of student grades. Empty digests and submissions
already reviewed or unlocked are skipped. Clicking the alert opens submitted
grades across all school years and terms.

Pending submissions and the last delivery time are stored in the database.
Overnight/weekend submissions wait for the next work window. If the service is
stopped or asleep, delivery resumes when the scheduler is running within that
window; it does not discard submissions because a scheduled check was missed.
Existing grades are not backfilled into notifications when this feature deploys.

For a non-Docker host, run Laravel's scheduler continuously or invoke
`php artisan schedule:run` once per minute from cron/Task Scheduler after
applying migrations. `php artisan registrar:grade-digest` can also run a check
manually; it respects the same delivery interval and working hours.

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
