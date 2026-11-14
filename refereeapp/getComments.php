<?php
// Latest comments left with availability, newest first.
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q("SELECT FirstName, LastName, comments, commentDate FROM referees WHERE commentDate IS NOT NULL AND comments <> ''
            UNION ALL
            SELECT FirstName, LastName, comments, commentDate FROM venuemanagers WHERE commentDate IS NOT NULL AND comments <> ''
            ORDER BY commentDate DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC));
