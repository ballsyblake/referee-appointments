# NCF Referee App — Claude Context

Referee and venue-manager scheduling app for North Canberra Futsal.
Plain PHP + MySQL + static HTML/jQuery. No build step, no framework. Tests in tests/.

## Hard rules

- **Never commit `refereeapp/config.php`** or any real credential. Real values
  go only in `config.php` (git-ignored); `config.example.php` holds placeholders.
- **Never connect to or modify the production database or the live host.**
  Work against a local database only.
- `uploads/` holds user files and stays out of git (only `avatar.png` is tracked).
- Do not push to GitHub or deploy without being asked.

## Architecture

- Root `*.html` pages + `js/index.js` + `css/` - front end. `index.js` calls `refereeapp/me.php`
  on load and runs the page's code; the server decides access.
- `refereeapp/*.php` - one endpoint per action. Each starts with
  `require __DIR__ . '/lib/bootstrap.php'` then `require_admin()`, `require_login([...])` or
  `public_endpoint()`. Use `q($sql, $params)` (prepared statements) for every query.
- `refereeapp/lib/bootstrap.php` - config (env `NCF_*` first, then `refereeapp/config.php`
  returning an array), PDO, sessions, role checks, CSRF check (`X-Requested-With`), uploads, dates.
- `refereeapp/lib/mail.php` - `send_mail()`; `MAIL_MODE=log` writes to `storage/mail.log`.
- Roles: `admins`, `referees`, `venuemanagers` tables. Passwords are `password_hash()`.
- `db/schema.sql`, `db/seed.php` (fake data only), `db/create-admin.php`.
- Hosting target: shared cPanel host (Apache + PHP + MySQL). `.htaccess` files block
  config, lib, uploads, storage, db. `router.php` mimics that for `php -S`.

## Running locally

```bash
php db/seed.php --yes && php -S 127.0.0.1:8000 router.php
bash tests/api-smoke.sh && node tests/e2e.js
```

## Known state

- Not yet tested: real SMTP sending, a real cPanel host, production data import.
- `leaderboards.html` and `coachingSheet.php` are unfinished mockups with no backend.
