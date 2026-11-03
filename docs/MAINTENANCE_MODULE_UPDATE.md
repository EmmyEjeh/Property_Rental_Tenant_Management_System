# Maintenance Module Update

This update replaces the maintenance placeholder with manager-side maintenance request registration and status tracking. It uses the existing `maintenance_requests` and `tenancies` tables; no database re-import or schema change is required.

## Included
- Register a request against an active tenancy belonging to the signed-in manager.
- Display request history with tenant, property, unit, description and submission date.
- Update status to Pending, In Progress or Resolved.
- CSRF protection, prepared statements, manager ownership checks and audit logging.

## Notes
The current database schema supports a text description, submission date, status and optional photo path. This update does not add photo upload, categories, priority, contractor assignment or repair-cost fields; those require a separately reviewed schema and upload-security implementation.
