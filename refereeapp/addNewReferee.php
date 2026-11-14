<?php
// Admin adds a referee with a starting password (shown once to the admin
// to pass on; the referee should change it on their profile page).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$email = strtolower(post('email'));
$first = clean_text(post('firstName'), 100);
$last = clean_text(post('lastName'), 100);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Enter a valid email address.');
if ($first === '' || $last === '') fail('Enter a first and last name.');
$taken = q('SELECT 1 FROM referees WHERE username = ? UNION SELECT 1 FROM venuemanagers WHERE username = ?
            UNION SELECT 1 FROM admins WHERE username = ?', [$email, $email, $email])->fetchColumn();
if ($taken) fail('That email already has an account.');
$password = rtrim(strtr(base64_encode(random_bytes(9)), '+/', 'Kx'), '=');
q('INSERT INTO referees (username, password, FirstName, LastName) VALUES (?, ?, ?, ?)',
    [$email, password_hash($password, PASSWORD_DEFAULT), $first, $last]);
json_out(['success' => true, 'password' => $password]);
