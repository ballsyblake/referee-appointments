<?php
// Public venue manager application form. Creates a venue manager with no
// password; an admin approves them on the Users page.
require __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/mail.php';
public_endpoint();

$email = strtolower(post('email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
$first = clean_text(post('firstName'), 100);
$last = clean_text(post('lastName'), 100);
if ($first === '' || $last === '') fail('Please enter your name.');
foreach (['dob', 'wwvpEx'] as $d) {
    if (post($d) !== '' && !valid_date(post($d))) fail('Please check the dates.');
}
$exists = q('SELECT 1 FROM venuemanagers WHERE username = ? UNION SELECT 1 FROM referees WHERE username = ?', [$email, $email])->fetchColumn();
if ($exists) fail('That email address has already applied or has an account.');

$file = save_upload('file', DOCUMENT_TYPES);
if ($file === null) fail('Please attach your first aid certificate.');

$pdo = db();
$pdo->beginTransaction();
q('INSERT INTO venuemanagers (username, FirstName, LastName, DOB, street, suburb, postcode, mobile, wwvpNo, wwvpEx, accName, bsb, accNum, firstAid)
   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
    $email, $first, $last, null_if_empty(post('dob')),
    clean_text(post('street')), clean_text(post('suburb'), 100), clean_text(post('postcode'), 10),
    clean_text(post('mobile'), 20), clean_text(post('wwvpNo'), 50), null_if_empty(post('wwvpEx')),
    clean_text(post('accName'), 100), preg_replace('/\D/', '', post('bsb')), preg_replace('/\D/', '', post('accNum')), $file,
]);
q('INSERT INTO emergencycontacts (firstName, lastName, email, mobile, altMobile, dependant) VALUES (?, ?, ?, ?, ?, ?)', [
    clean_text(post('firstNameEC'), 100), clean_text(post('lastNameEC'), 100), clean_text(post('emailEC')),
    clean_text(post('mobileEC'), 20), clean_text(post('mobileAltEC'), 20), $email,
]);
$pdo->commit();

if (cfg('ADMIN_EMAIL') !== '') {
    send_mail('Venue manager application - ' . $first . ' ' . $last,
        '<p>' . h("$first $last ($email)") . ' applied to be a venue manager. Approve them on the Users page.</p>',
        "$first $last ($email) applied to be a venue manager. Approve them on the Users page.",
        [cfg('ADMIN_EMAIL') => 'NCF admin']);
}
json_out(['success' => true, 'message' => 'Application received. You will hear from us soon.']);
