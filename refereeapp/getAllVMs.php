<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q('SELECT username, FirstName, LastName, bsb AS BSB, accNum AS AccNum, payRate FROM venuemanagers
            WHERE password IS NOT NULL ORDER BY FirstName, LastName')->fetchAll(PDO::FETCH_ASSOC));
