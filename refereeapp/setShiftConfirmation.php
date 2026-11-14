<?php
// The logged-in venue manager confirms or un-confirms one of their shifts.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['venuemanager']);
$stmt = q('UPDATE shifts SET confirmed = ? WHERE shiftID = ? AND venuemanagerID = ?',
    [post('confirm') === '1' ? 1 : 0, (int) post('id'), $user['username']]);
json_out(['success' => $stmt->rowCount() >= 0]);
