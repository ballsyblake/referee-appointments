<?php
// Availability for the logged-in user (admins may ask for anyone).
require __DIR__ . '/lib/bootstrap.php';
$user = require_login();
$username = $user['type'] === 'admin' && post('username') !== '' ? post('username') : $user['username'];

json_out(q("SELECT date, hour, venue FROM availability WHERE username = ? AND date >= CURDATE() - INTERVAL 7 DAY ORDER BY date, hour",
    [$username])->fetchAll(PDO::FETCH_ASSOC));
