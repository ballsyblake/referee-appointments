<?php
// Number of active referees and venue managers (dashboard tile).
require __DIR__ . '/lib/bootstrap.php';
require_admin();
$n = (int) q('SELECT (SELECT COUNT(*) FROM referees WHERE password IS NOT NULL)
                   + (SELECT COUNT(*) FROM venuemanagers WHERE password IS NOT NULL)')->fetchColumn();
text_out((string) $n);
