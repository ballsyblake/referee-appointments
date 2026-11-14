<?php
// Create or reset an admin login on any database (local or live).
//   php db/create-admin.php you@example.com "First" "Last"
// Asks for the password so it never ends up in your shell history.
if (PHP_SAPI !== 'cli') exit("Run from the command line.\n");
require __DIR__ . '/../refereeapp/lib/bootstrap.php';

[$_, $email, $first, $last] = array_pad($argv, 4, '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) exit("Usage: php db/create-admin.php email \"First\" \"Last\"\n");
echo 'Password (8+ characters): ';
system('stty -echo 2>/dev/null');
$password = trim((string) fgets(STDIN));
system('stty echo 2>/dev/null');
echo "\n";
if (strlen($password) < 8) exit("Too short.\n");
q('INSERT INTO admins (username, password, firstName, lastName) VALUES (?, ?, ?, ?)
   ON DUPLICATE KEY UPDATE password = VALUES(password), firstName = VALUES(firstName), lastName = VALUES(lastName)',
    [strtolower($email), password_hash($password, PASSWORD_DEFAULT), $first, $last]);
echo "Admin $email saved.\n";
