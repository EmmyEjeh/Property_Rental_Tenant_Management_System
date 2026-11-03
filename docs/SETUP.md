# Local setup and first manager account

1. Install PHP 8.1+, MySQL 8+, and Apache (XAMPP/WAMP is suitable for local development).
2. Create/import the database by running `database/schema.sql`.
3. Copy `.env.example` to `.env` and edit DB settings for your machine. Keep `.env` private.
4. Set Apache's document root to this project's `public` directory. If your local setup cannot change the document root, configure a virtual host pointing to `public/`; do not put private folders under a publicly served directory.
5. Create a manager account from the project root using the CLI:

   `php scripts/create_manager.php admin 'Use-A-Unique-Long-Password' 'System Manager'`

6. Visit the local site and sign in with the account you created.

The setup script is CLI-only and hashes the password with PHP's `password_hash()`. Delete or restrict the setup script after initial configuration if required by your deployment policy.
