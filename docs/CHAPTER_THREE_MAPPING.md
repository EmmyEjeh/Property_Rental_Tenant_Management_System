# Chapter Three to project-folder mapping

| Chapter Three item / requirement area | Main project locations |
|---|---|
| 3.5.1 User registration and authentication / role-based access | `app/core/auth.php`, `app/controllers/AuthController.php`, `app/views/auth/`, `database/schema.sql` (`users`) |
| 3.5.1 Tenant and tenancy management | `app/controllers/TenantController.php`, `app/views/tenants/`, `database/schema.sql` (`tenants`, `tenancies`) |
| 3.5.1 Property/unit and occupancy status | `app/controllers/UnitController.php`, `app/views/units/`, `database/schema.sql` (`properties`, `units`) |
| 3.5.1 Rent payment tracking, partial payments, receipts, arrears | `app/controllers/PaymentController.php`, `app/views/payments/`, `database/schema.sql` (`payments`) |
| 3.5.1 Maintenance request management and evidence photos | `app/controllers/MaintenanceController.php`, `app/views/maintenance/`, `storage/uploads/maintenance/`, `database/schema.sql` (`maintenance_requests`) |
| 3.5.1 AI-assisted caution-fee/deposit disputes | `app/controllers/DisputeController.php`, `app/views/disputes/`, `ai_service/`, `storage/uploads/move_in/`, `storage/uploads/move_out/`, `database/schema.sql` (`dispute_cases`) |
| Occupancy, rent, arrears, and maintenance reports | `app/controllers/ReportController.php`, `app/views/reports/` |
| 3.7.2 Shared module interactions and audit history | `app/core/helpers.php` (`audit()`), `database/schema.sql` (`audit_logs`) |
| 3.8 Database design and ERD | `database/schema.sql` |
| 3.10 HTML/CSS/JavaScript/PHP/MySQL tools | `public/`, `app/`, `database/` |
| Security, prepared statements, sessions, CSRF | `app/config/database.php`, `app/core/auth.php`, `app/core/helpers.php` |
| Database backup | `scripts/backup_database.sh` |

## Status and scope
This folder is a structured starter scaffold. Authentication and a basic dashboard are wired up, and the schema and module entry points are included. The feature-specific workflows in the tenant, unit, payment, maintenance, report, and dispute modules remain to be implemented and tested; the folder should not be presented as a completed application until those workflows are finished.
