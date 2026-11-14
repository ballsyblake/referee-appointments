<?php
// The logged-in user changes their own details and profile photo.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['referee', 'venuemanager']);
$table = $user['type'] === 'referee' ? 'referees' : 'venuemanagers';

$changes = [];
$first = clean_text(post('firstName'), 100);
$last = clean_text(post('lastName'), 100);
$dob = post('dob');
$bsb = preg_replace('/\D/', '', post('bsb'));
$acc = preg_replace('/\D/', '', post('accNum'));

if ($first !== '') $changes['FirstName'] = $first;
if ($last !== '') $changes['LastName'] = $last;
if ($dob !== '') {
    if (!valid_date($dob)) text_out('Date of birth must be a valid date.');
    $changes['DOB'] = $dob;
}
if ($bsb !== '') {
    if (strlen($bsb) !== 6) text_out('BSB must be 6 digits.');
    $changes['bsb'] = $bsb;
}
if ($acc !== '') {
    if (strlen($acc) < 6 || strlen($acc) > 10) text_out('Account number must be 6 to 10 digits.');
    $changes['accNum'] = $acc;
}
$pic = save_upload('file', IMAGE_TYPES);
if ($pic !== null) $changes['profilePic'] = $pic;

if (!$changes) {
    text_out('Nothing to change.');
}
// Column names come from the fixed list above, never from the request.
$set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($changes)));
q("UPDATE $table SET $set WHERE username = ?", [...array_values($changes), $user['username']]);
text_out('Changes made successfully.');
