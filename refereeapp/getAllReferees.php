<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q('SELECT FirstName, LastName, bsb AS BSB, accNum AS AccNum, username FROM referees ORDER BY FirstName, LastName')
    ->fetchAll(PDO::FETCH_ASSOC));
