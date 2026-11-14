#!/usr/bin/env bash
# Builds a ready-to-upload demo package for a shared PHP host:
#   dist/ncf-referee-demo.zip   site files with a demo config.php to fill in
#   dist/ncf-schema.sql         import once in phpMyAdmin
# Demo mode loads fake data on the first visit and resets it daily.
set -euo pipefail
cd "$(dirname "$0")/.."
out=dist
stage=$(mktemp -d)
mkdir -p "$out"
git ls-files -z | grep -z -v -E '^(db/|tests/|docs/|tools/|router\.php|CLAUDE\.md|README\.md|leaderboards\.html|coachingSheet\.php)' \
  | xargs -0 -I{} cp --parents {} "$stage"
cat > "$stage/refereeapp/config.php" <<'CFG'
<?php
// Demo site settings. Fill in the four DB_ values from your host's
// MySQL Databases page, then save. Nothing else needs changing.
return [
    'DB_HOST' => 'sqlXXX.infinityfree.com',
    'DB_NAME' => 'if0_XXXXXXXX_ncf',
    'DB_USER' => 'if0_XXXXXXXX',
    'DB_PASS' => 'your-mysql-password',

    'DEMO' => '1',          // demo logins on the login page, daily reset to fake data
    'MAIL_MODE' => 'off',   // never send email from the demo
    'TIMEZONE' => 'Australia/Sydney',
    'DEBUG' => '0',
];
CFG
rm -f "$out/ncf-referee-demo.zip"
(cd "$stage" && zip -qr -X "$OLDPWD/$out/ncf-referee-demo.zip" .)
cp db/schema.sql "$out/ncf-schema.sql"
rm -rf "$stage"
ls -la "$out"
