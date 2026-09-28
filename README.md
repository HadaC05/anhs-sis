# anhs-sis

## Queued class imports

Run `php artisan migrate` after updating. Imports show saved student progress on
the advisory class list. On local Windows/XAMPP installations, uploads and queued
import progress checks automatically launch a hidden worker that exits when the
queue is empty. This also recovers uploads waiting after a local server restart.
Set `AUTO_START_LOCAL_IMPORT_WORKER=false` to disable this when managing your own
worker. The Render Docker container runs its own supervised queue worker alongside
the website, including on the free web-service plan. See docs/RENDER_DEPLOYMENT.md.

`composer dev` starts a worker automatically. To run a worker manually, use
`php artisan queue:work database --tries=1 --timeout=1200 --sleep=2` in a separate
terminal and keep it running. Restart existing workers after deploying changes.
The database queue retry interval must exceed the 1200-second worker timeout
(the default is 1260 seconds).

If an import fails partway through, completed students stay saved. Uploading the
same file again fills missing details and reuses existing students/enrollments.
Class-list imports skip address and IP-group details; existing values stay intact.

## Production document upload limits

The Docker image loads `docker/php/uploads.ini`, allowing 15 MB per file and
64 MB per request. Nginx uses the same 64 MB request limit so students can submit
multiple documents together. The application still validates each file at 15 MB.

Rebuild and redeploy the Docker image after changing these settings; a Laravel
cache clear alone does not apply PHP or Nginx configuration changes.

For hosting that does not use this Docker image, configure the web PHP runtime
with `upload_max_filesize = 15M` and `post_max_size = 64M`, and set the web server
or proxy request limit to at least 64 MB. Reload the relevant services after
changing their configuration.
