<?php
// Admin actions on one referee or venue manager.
//   action=reset    new random password (returned once); also approves a
//                   venue manager application
//   action=payRate  set a venue manager's hourly rate (rate=)
//   action=delete   remove the account and its availability
require __DIR__ . '/lib/bootstrap.php';
require_admin();
if (post('action') !== 'payRate') forbid_in_demo('Resetting and deleting accounts');
$type = post('type');
$table = ['referee' => 'referees', 'venuemanager' => 'venuemanagers'][$type] ?? null;
if ($table === null) fail('Bad user type.');
$u = post('username');
if (!q("SELECT 1 FROM $table WHERE username = ?", [$u])->fetchColumn()) fail('No such user.');

switch (post('action')) {
    case 'reset':
        $password = rtrim(strtr(base64_encode(random_bytes(9)), '+/', 'Kx'), '=');
        q("UPDATE $table SET password = ? WHERE username = ?", [password_hash($password, PASSWORD_DEFAULT), $u]);
        json_out(['success' => true, 'password' => $password]);
    case 'payRate':
        if ($table !== 'venuemanagers') fail('Only venue managers have a pay rate.');
        $rate = post('rate');
        if (!is_numeric($rate) || $rate < 0 || $rate > 200) fail('Pay rate must be a number of dollars per hour.');
        q('UPDATE venuemanagers SET payRate = ? WHERE username = ?', [$rate, $u]);
        json_out(['success' => true]);
    case 'delete':
        $pdo = db();
        $pdo->beginTransaction();
        q('DELETE FROM availability WHERE username = ?', [$u]);
        if ($table === 'venuemanagers') q('DELETE FROM emergencycontacts WHERE dependant = ?', [$u]);
        q("DELETE FROM $table WHERE username = ?", [$u]);
        $pdo->commit();
        json_out(['success' => true]);
}
fail('Unknown action.');
