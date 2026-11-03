# Property Rental and Tenant Management System

Starter project structure based on Chapter Three of the project document: **Web-Based Property Rental and Tenant Management System with AI-Driven Deposit Dispute Resolution**.

## Technology stack
- Front end: HTML5, CSS3, JavaScript (Bootstrap can be added if desired)
- Application server: PHP 8.1+ using PDO and prepared statements
- Database: MySQL 8+
- Optional AI service: Python 3 with OpenCV/TensorFlow or another approved computer-vision model, exposed through a local API
- Local development: XAMPP/WAMP or another PHP/MySQL environment

## Folder map

```text
Property_Rental_Tenant_Management_System/
├── app/
│   ├── config/       # Environment and database configuration
│   ├── core/         # Router, authentication, CSRF, validation, shared helpers
│   ├── controllers/ # Request handling by module
│   ├── models/       # Database access and business rules
│   └── views/        # HTML templates, grouped by feature
├── database/         # Schema and optional seed instructions
├── docs/             # Architecture and implementation notes
├── public/           # Web root; only this folder should be publicly served
│   ├── assets/css/
│   ├── assets/js/
│   └── index.php
├── ai_service/       # Optional Python image comparison API starter
├── scripts/          # Setup and database backup scripts
├── storage/          # Private uploads and logs; block direct web access
├── tests/            # Test plan and starter test cases
└── .env.example
```

## Setup (XAMPP/WAMP)
1. Copy this folder into your local web workspace.
2. Create a MySQL database named `property_rental`.
3. Import `database/schema.sql` using phpMyAdmin or the MySQL client.
4. Copy `.env.example` to `.env` and set the database credentials.
5. Configure Apache's document root to the project's `public/` folder. Do not expose `app/`, `.env`, or `storage/` directly.
6. Make sure PHP extensions `pdo_mysql` and `fileinfo` are enabled.
7. Open the site through the local server, e.g. `http://localhost/` when `public/` is the document root.
8. Create the first manager account using the one-time setup instructions in `docs/SETUP.md`.

## Important implementation notes
- The scaffold provides the folder layout, database schema, shared security helpers, and module entry points. It is a **starter project**, not a claim that every requirement in Chapter Three is already fully implemented.
- All data-changing operations should use server-side validation, CSRF protection, prepared statements, and transactions where multiple records must stay consistent.
- Tenant routes must verify that the signed-in tenant owns the tenancy/record being requested. Hiding links in the UI is not authorization.
- AI-generated damage classifications and deductions are recommendations for manager review, not automatic final decisions. Keep evidence and the final manager decision in the audit trail.
- Store uploaded files outside the public web root, validate MIME type and size, generate random filenames, and never trust a supplied file path.
- Change all example credentials and configure HTTPS before deployment.

## Main modules mapped from Chapter Three
1. Authentication and role-based access
2. Property, unit, tenant, and tenancy management
3. Rent payments, balances, arrears, and receipts
4. Maintenance requests and photo evidence
5. Occupancy and financial reports
6. AI-assisted deposit dispute resolution
7. Audit history, data integrity, and backup

See `docs/CHAPTER_THREE_MAPPING.md` for the mapping from the document's requirements to project files.


## Annual rent update
See `docs/ANNUAL_RENT_UPDATE.md` for the one-time migration from monthly rent to yearly rent. Existing database users must back up first and run `database/migrations/2026_annual_rent_once.sql` exactly once.

## Local deposit-dispute module

The integrated module is available from **Deposit disputes** in the manager navigation. Existing installations must back up the database and run `database/migrations/2026_deposit_disputes_module.sql` exactly once. Do not re-import `database/schema.sql` into an existing database.

### Start the local vision service on Windows
1. Install Python 3.10 or later and ensure `python` works in Command Prompt.
2. Open Command Prompt and run `cd C:\\xampp\\htdocs\\Property_Rental_Tenant_Management_System\\ai_service`.
3. Create a virtual environment: `python -m venv .venv`.
4. Activate it: `.venv\\Scripts\\activate`.
5. Install packages: `python -m pip install --upgrade pip` then `pip install -r requirements.txt`.
6. Start the service: `python -m uvicorn main:app --host 127.0.0.1 --port 8001`.
7. Keep this Command Prompt window open while using local image comparison. Visit `http://127.0.0.1:8001/health` to confirm it reports `ready`.

In XAMPP's `php.ini`, enable `extension=curl` and `extension=fileinfo` if disabled, then restart Apache. The app uses `AI_SERVICE_URL=http://127.0.0.1:8001` by default. Uploaded images are stored privately under `storage/uploads/disputes/` and are not sent to an external service.

### Important capability boundary
The included OpenCV engine performs pixel-level visual change detection between paired images. It is not a trained model that recognizes or proves specific property damage. Lighting, camera angle, shadows, furniture and image alignment can cause false differences. It does not determine fault, ordinary wear and tear, liability, or repair costs. A manager must review original evidence and supporting estimates and enter the final deduction. The refund is calculated as caution fee minus manager-approved deduction.
