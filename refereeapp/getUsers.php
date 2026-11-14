<?php
// Display name for a username (used to show who is on each match).
require __DIR__ . '/lib/bootstrap.php';
require_login();

$username = post('username');
if ($username === '') {
    text_out('');
}
foreach (['referees', 'venuemanagers', 'admins'] as $table) {
    $row = q("SELECT FirstName, LastName FROM $table WHERE username = ?", [$username])->fetch();
    if ($row) {
        text_out(h(trim($row[0] . ' ' . $row[1])));
    }
}
text_out('');
