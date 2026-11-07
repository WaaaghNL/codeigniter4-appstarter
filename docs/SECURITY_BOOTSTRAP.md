# Security bootstrap (Shield)

Clean installs no longer create a demo administrator via database migrations. Create the first privileged account with Shield's CLI so credentials are never embedded in source or migration history.

## New installation

1. Run Shield and application migrations:

   ```console
   php spark migrate --all
   ```

2. Create the first superadmin interactively (password is prompted; it is not stored in the repository or logs by this project):

   ```console
   php spark shield:user create -n youradmin -e admin@yourdomain.example -g superadmin
   ```

3. Sign in at `/login` and open `/shield` for user management.

## Upgrading from a release that seeded the demo account

Earlier versions ran a migration that inserted a known demo user (`admin` / `admin@example.com`). That migration is now a no-op; it does **not** remove existing rows.

1. **Identify** accounts that still match the legacy demo footprint (username `admin` and/or email `admin@example.com`) **and** that you did not intentionally keep.

2. **Verify** each account before changes — do not delete users that legitimate operators adopted after renaming or password changes.

3. **Remediate** confirmed legacy demo accounts:
   - Disable: `php spark shield:user deactivate -n admin`
   - Delete: `php spark shield:user delete -n admin` (confirm when prompted)
   - Or rotate credentials: `php spark shield:user password -n admin`

4. **Ensure** at least one active `superadmin` remains (create one first if needed):

   ```console
   php spark shield:user create -n recovery-superadmin -e security@yourdomain.example -g superadmin
   ```

## CSRF and security headers

Global CSRF (session-based, matching Shield session auth) and `SecureHeaders` are enabled in `app/Config/Filters.php`. Shield auth views and `/shield` forms include `csrf_field()`.
