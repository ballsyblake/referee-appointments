<?php
// Whether one of the logged-in venue manager's shifts is confirmed (0/1).
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['venuemanager']);
$v = q('SELECT confirmed FROM shifts WHERE shiftID = ? AND venuemanagerID = ?',
    [(int) post('id'), $user['username']])->fetchColumn();
text_out($v === false ? '0' : (string) $v);
