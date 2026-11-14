<?php
// Everyone's availability from a week ago onwards (admin schedule builder).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q('SELECT username, date, hour, venue FROM availability WHERE date >= CURDATE() - INTERVAL 14 DAY ORDER BY date, hour')
    ->fetchAll(PDO::FETCH_ASSOC));
