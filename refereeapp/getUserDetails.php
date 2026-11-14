<?php
// The logged-in user's own profile.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['referee', 'venuemanager']);

$table = $user['type'] === 'referee' ? 'referees' : 'venuemanagers';
$rows = q("SELECT FirstName, LastName, username, DOB, bsb, accNum, profilePic FROM $table WHERE username = ?",
    [$user['username']])->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$row) {
    $row['profilePic'] = 'refereeapp/file.php?f=' . rawurlencode($row['profilePic'] ?? '');
}
json_out($rows);
