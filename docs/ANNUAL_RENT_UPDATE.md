# Annual Rent Update

This update changes the system from monthly rent to yearly (annual) rent.

## Existing database — important
1. Back up the `property_rental` database in phpMyAdmin (Export → Quick).
2. Copy the updated project files into the existing project, but keep your local `.env` file.
3. Open phpMyAdmin → select the `property_rental` database → SQL.
4. Open `database/migrations/2026_annual_rent_once.sql`, copy its contents into the SQL box, and execute it.
5. Run the migration **once only**. It renames `monthlyRent` to `annualRent` and multiplies every existing value by 12.
6. Open the Units page and confirm that the old ₦150,000 monthly rent now appears as ₦1,800,000 yearly rent.

## Fresh database
For a new installation, import `database/schema.sql`. It now creates `units.annualRent`; do not run the migration on a fresh database.

## Annual arrears calculation
The estimated rent due is calculated by counting annual tenancy anniversaries reached from the tenancy start date through the earlier of today or the tenancy end date. This is an estimate; verify each tenancy agreement's actual due dates and any grace periods.

## Payment period
Use the annual period covered field to record the exact annual rental period paid, for example `30 Oct 2026 – 29 Oct 2027`.

## Security
The distributable ZIP intentionally excludes `.env`. Keep the existing local `.env` file private and do not upload it.
