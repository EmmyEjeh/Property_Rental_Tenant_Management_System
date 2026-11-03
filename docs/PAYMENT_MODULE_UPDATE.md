# Rent Payment Module Update

This update builds on the Tenant and Tenancy module. It uses the existing `payments` table in `database/schema.sql`; no database re-import or schema alteration is required.

## Included
- Record payments against an active tenancy owned by the signed-in manager.
- Validate positive amounts, dates, methods, and optional reference numbers.
- Store full or partial payments; each submission is a separate immutable payment-history entry.
- Show payment history and manager-level totals.
- Show estimated rent due and balance for active tenancies.
- Use CSRF protection and prepared SQL statements.

## Balance estimate
The displayed estimate counts whole calendar months from the tenancy start month through the current month, capped at the tenancy end month. It is a simple estimate, not a substitute for a contractual rent schedule, proration, payment allocation by invoice, or late-fee rules. Confirm the tenancy agreement before treating an estimate as a formal arrears demand.

## Install
1. Back up the current project folder.
2. Extract the update ZIP to a temporary folder.
3. Copy the extracted project contents over the existing project folder and replace matching files.
4. Preserve the existing `.env` file.
5. Do not re-import `schema.sql`.
6. Open `public/index.php?page=payments` while signed in as the manager.
