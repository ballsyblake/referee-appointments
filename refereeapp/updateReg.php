<?php
// Mark players as registered when they appear in pfRegistrations (matched
// on FFA number, or on first and last name).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$n = q('UPDATE players p SET registered = EXISTS (
            SELECT 1 FROM pfRegistrations r
            WHERE (p.ffa <> \'\' AND r.ffa = p.ffa) OR (r.firstName = p.firstName AND r.lastName = p.lastName))')->rowCount();
json_out(['success' => true, 'changed' => $n]);
