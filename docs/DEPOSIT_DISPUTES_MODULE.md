# Deposit Disputes Module — Installation and Operation

## Existing XAMPP installation
1. Back up the whole project folder and export the `property_rental` database in phpMyAdmin.
2. Extract this update over the existing project folder, preserving `.env`. Do not replace `.env` with `.env.example`.
3. In phpMyAdmin, select `property_rental`, open **Import**, and import `database/migrations/2026_deposit_disputes_module.sql` once.
4. Ensure PHP `curl` and `fileinfo` extensions are enabled in XAMPP's `php.ini`; restart Apache.
5. Install and start the Python service as described in `ai_service/README.md` and the main README.
6. Open the manager dashboard, choose **Deposit disputes**, create a case for a tenancy, upload matching move-in and move-out photos, then run local visual comparison.
7. Review the report and original images. Enter a manager decision and deduction only when supported. The app calculates the remaining refund from the recorded caution fee.

## Security and data handling
- Only authenticated managers can access dispute pages and evidence, and queries are restricted to their managed properties.
- Evidence is stored outside `public/`, with random stored filenames and MIME validation.
- Forms use the application's CSRF token. SQL uses prepared statements.
- The Python service binds to loopback (`127.0.0.1`) and does not call an online service.
- Keep backups protected; evidence may contain personal information.

## AI/vision limitation
This release provides local OpenCV visual-difference analysis, not a trained semantic damage-recognition model. It reports the proportion of pixels that differ and a broad visual-change band. It cannot identify the cause of a change, distinguish damage from ordinary wear reliably, determine tenant liability, or price repairs. A human manager must make and document the final decision.
