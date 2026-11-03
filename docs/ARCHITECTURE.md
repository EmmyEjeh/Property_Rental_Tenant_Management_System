# Architecture overview

The application follows the three-tier design described in Chapter Three:

1. **Presentation tier:** HTML, CSS, JavaScript and PHP-rendered views in `public/` and `app/views/`.
2. **Application tier:** PHP controllers, authentication, validation, access checks, payment/business rules, and audit logging in `app/`.
3. **Data tier:** MySQL tables and relationships defined in `database/schema.sql`.

The optional AI vision service is isolated under `ai_service/`. It should only produce a reviewable recommendation. The PHP application remains responsible for authorization, storing evidence, applying approved deduction rules, recording the manager's final decision, and writing the audit trail.
