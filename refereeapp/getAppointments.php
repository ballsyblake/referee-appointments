<?php
// The logged-in referee's matches for this fixture weekend.
require __DIR__ . '/lib/bootstrap.php';
$user = require_login(['referee']);
$u = $user['username'];
json_out(q("SELECT Date, TIME_FORMAT(MatchTime, '%l:%i %p') AS MatchTime, Venue, Court, AgeGroup,
                   referee1, referee2, referee3, referee4, coach
            FROM appointments
            WHERE Date BETWEEN ? AND ?
              AND ? IN (referee1, referee2, referee3, referee4, coach)
            ORDER BY Date, MatchTime, Court",
    [this_friday(), this_sunday(), $u])->fetchAll(PDO::FETCH_BOTH));
