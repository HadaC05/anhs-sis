# Audit trail

Implemented from `SIS_Audit_Trail_Developer_Guide.docx`, with the requested access for **both admin and principal**. Each portal has an Audit Trail navigation link; teachers, students, guidance, registrar, and guests cannot view it. There are no edit/delete routes. Model-level update/delete operations are rejected as well; this is not a substitute for restricting direct database access.

## Coverage

`config/audit.php` explicitly lists the supported workflows: account management; academic years, sections, subjects, curriculum, grading terms, attendance settings and reasons; enrollment reviews and changes; document submission/verification/return; student profiles; teacher grades, attendance and promotions; registrar grade approval/return; principal grade release; SF1/class list imports, SF2 imports, and school form access/generation. Only workflows that exist in the application are instrumented.

Each entry stores the timestamp, actor ID/name/role snapshot, action, module, record references, description, and outcome. Staff and student IDs use different prefixes. Public registration is attributed to an unauthenticated applicant, with created record references, rather than impersonating a signed-in student. Search and date/user/role/module/action/status filters preserve pagination. Display and date filters use Asia/Manila; storage follows the application timezone (UTC).

Selected model changes include before/after operational fields such as role IDs, section IDs, status IDs and numeric grades. Bulk query updates receive a workflow entry even though they bypass model events; their references identify the requested scope, not a complete per-row diff. Grade approval/release descriptions include affected counts. Model references from rolled-back transactions are discarded. This is a workflow audit trail, not a full database change-data-capture system.

SF1/class list imports have separate queued and completed/failed entries. The importing user's identity and role are saved when queuing, so background execution retains attribution after an account changes. Partial enrollment/import outcomes are marked Partial. A failed workflow may have saved earlier work, especially imports; failure does not imply that every preceding change was rolled back.

No raw request bodies, passwords, tokens, exception messages, file contents, or generated account credentials are stored in the audit trail. Ordinary navigation, polling, enrollment status lookups and authentication forms are excluded. Opening an enrollment application for guidance review and accessing school forms are intentional read events. The existing application error log has its own separate behavior.

## Deployment

Run `php artisan migrate` before serving the new code, clear any cached configuration/routes as appropriate, and restart queue workers (`php artisan queue:restart`) so they load the import completion logging. The new migration creates `audit_logs` and adds an actor snapshot column to class list imports. No historical entries are fabricated.

For future workflows, add a route definition to `config/audit.php`. Controllers can supply a precise safe summary through the request's `audit_description` attribute. Background jobs must explicitly call `AuditTrail::record()` with a captured actor. Direct SQL, console commands, external database edits and future unlisted routes are not automatically audited.

Audit storage errors are not silently ignored. Because normal HTTP logging runs after the controller, an audit-write failure can return an error after the business change has committed; investigate before retrying that operation. Database backups and access controls remain necessary for log preservation.
