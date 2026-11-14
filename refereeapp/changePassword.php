<?php
// The logged-in user changes their own password.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login();
forbid_in_demo('Changing passwords');
$table = ['admin' => 'admins', 'referee' => 'referees', 'venuemanager' => 'venuemanagers'][$user['type']];

$current = $_POST['current'] ?? '';
$new = $_POST['new'] ?? '';
if (!is_string($current) || !is_string($new)) fail('Bad request.');
if (strlen($new) < 8) fail('New password must be at least 8 characters.');

$stored = q("SELECT password FROM $table WHERE username = ?", [$user['username']])->fetchColumn();
if (!$stored || !password_verify($current, $stored)) {
    fail('Current password is wrong.');
}
q("UPDATE $table SET password = ? WHERE username = ?", [password_hash($new, PASSWORD_DEFAULT), $user['username']]);
json_out(['success' => true]);
