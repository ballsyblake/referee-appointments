<?php
// Every referee and venue manager for the admin Users page.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q("SELECT 'referee' AS type, username, FirstName, LastName, NULL AS payRate, NULL AS firstAid, password IS NOT NULL AS active
            FROM referees
            UNION ALL
            SELECT 'venuemanager', username, FirstName, LastName, payRate, firstAid, password IS NOT NULL
            FROM venuemanagers
            ORDER BY active, type, FirstName, LastName")->fetchAll(PDO::FETCH_ASSOC));
