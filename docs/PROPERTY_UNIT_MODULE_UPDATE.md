# Property and Unit Management Update

This update implements manager-side property and unit CRUD screens.

## Installation on an existing XAMPP installation
1. Back up the current project folder.
2. Extract this ZIP to a temporary folder.
3. Copy the contents of `Property_Rental_Tenant_Management_System` into the existing project folder in `C:\\xampp\\htdocs\\Property_Rental_Tenant_Management_System`.
4. Allow Windows to overwrite matching files when prompted. This update intentionally does not include `.env`; your existing environment file and database are left untouched.
5. Do not re-import `database/schema.sql`; the existing `properties` and `units` tables are used.
6. Sign in as the manager and open `?page=properties`.

## Features
- Register, list, edit and delete properties.
- Property records are scoped to the signed-in manager.
- Register, list, edit and delete units belonging to the manager's properties.
- Unit number uniqueness is enforced per property by the existing database constraint.
- Property/unit deletion is blocked by foreign-key relationships when dependent records exist.
- Forms use CSRF tokens and PDO prepared statements.

## Notes
The unit status can be set to vacant or occupied manually. Tenant/tenancy workflows are not yet implemented, so automatic occupancy updates will be added with that module.

## Tenant and tenancy module update
The tenant module now supports manager-only tenant registration, assignment to a vacant unit, active tenancy creation, editing an active tenant/tenancy, and ending a tenancy. Tenant and tenancy records are written in a database transaction. Creating a tenancy marks the selected unit occupied; ending it marks the unit vacant. Historical tenant and tenancy records are retained. No database re-import or schema change is required.
