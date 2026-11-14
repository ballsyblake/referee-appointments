<?php
// Log in. Checks referees, venue managers and admins, starts a session and
// tells the page where to go next.
require __DIR__ . '/lib/bootstrap.php';
public_endpoint();

$username = strtolower(post('username'));
$password = $_POST['password'] ?? '';
if ($username === '' || !is_string($password) || $password === '') {
    json_out(['success' => false]);
}

$tables = ['admin' => 'admins', 'referee' => 'referees', 'venuemanager' => 'venuemanagers'];
$found = null;
foreach ($tables as $type => $table) {
    $row = q("SELECT username, password FROM $table WHERE username = ?", [$username])->fetch();
    if ($row && $row['password'] !== null && $row['password'] !== '') {
        $stored = $row['password'];
        $ok = false;
        if (password_get_info($stored)['algo'] !== null) {
            $ok = password_verify($password, $stored);
        } else {
            // Legacy plain-text password from an old import: accept it once,
            // then replace it with a hash so it is never stored in clear again.
            $ok = hash_equals($stored, $password);
        }
        if ($ok) {
            if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                q("UPDATE $table SET password = ? WHERE username = ?",
                    [password_hash($password, PASSWORD_DEFAULT), $row['username']]);
            }
            $found = ['username' => $row['username'], 'type' => $type];
            break;
        }
    }
}

if ($found === null) {
    usleep(300000); // slow down password guessing a little
    json_out(['success' => false]);
}

start_session();
session_regenerate_id(true); // new session id at login stops session fixation
$_SESSION['username'] = $found['username'];
$_SESSION['type'] = $found['type'];

json_out([
    'success' => true,
    'userType' => $found['type'],
    'username' => $found['username'],
    'destination' => $found['type'] === 'admin' ? 'admin.html' : 'homePage.html',
]);
