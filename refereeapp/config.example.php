<?php
// Copy this file to config.php and fill in real values.
// config.php is git-ignored and must never be committed.
//
// Every value can instead be set as an environment variable with an NCF_
// prefix (e.g. NCF_DB_PASS). Environment variables win over this file.
// Use whichever your host supports: cPanel-style hosts usually need this
// file; Docker, Render, Railway and similar use environment variables.

return [
    // Database
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3306',
    'DB_NAME' => 'ncf',
    'DB_USER' => 'ncf_app',
    'DB_PASS' => 'change-me',

    // Email. MAIL_MODE: 'smtp' sends for real, 'log' writes to
    // storage/mail.log (good for testing), 'off' sends nothing.
    'MAIL_MODE' => 'log',
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => '587',
    'SMTP_USER' => 'you@example.com',
    'SMTP_PASS' => 'an-app-password',   // for Gmail, an App Password, never the account password
    'MAIL_FROM' => 'you@example.com',
    'MAIL_FROM_NAME' => 'North Canberra Futsal',
    'ADMIN_EMAIL' => 'you@example.com',  // receives venue manager applications

    // Public address of the site, used in email links.
    'APP_URL' => 'https://refs.example.com',

    // Where uploaded photos and certificates go. Best outside the web root.
    // 'UPLOAD_DIR' => '/home/you/ncf-uploads',

    'TIMEZONE' => 'Australia/Sydney',
    'DEBUG' => '0',   // '1' shows error details in responses; never on a live site
    'DEMO' => '0',    // '1' on a public demo: shows demo logins, blocks password changes
];
