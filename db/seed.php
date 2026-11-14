<?php
// Fills a local database with FAKE people and fixtures so every page has
// something to show. Never run this against a live database: it deletes
// everything first. The data and logins are listed in refereeapp/lib/seed.php.
//
//   php db/seed.php          (asks first)
//   php db/seed.php --yes

if (PHP_SAPI !== 'cli') {
    exit("Run from the command line.\n");
}
require __DIR__ . '/../refereeapp/lib/bootstrap.php';
require __DIR__ . '/../refereeapp/lib/seed.php';

if (!in_array('--yes', $argv, true)) {
    fwrite(STDOUT, 'This wipes database "' . cfg('DB_NAME') . '" on ' . cfg('DB_HOST') . ". Type yes to continue: ");
    if (trim((string) fgets(STDIN)) !== 'yes') exit("Stopped.\n");
}
ncf_seed(db());
echo "Seeded. Log in as admin@example.test / password123 (see refereeapp/lib/seed.php for the others).\n";
