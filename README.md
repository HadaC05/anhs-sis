# anhs-sis

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
