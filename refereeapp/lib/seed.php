<?php
// Fake demo data: people, fixtures for this weekend and next, availability,
// shifts and teams. Used by db/seed.php and by demo mode's daily reset.
// It deletes every row first, so it must never touch a real database.
//
// Logins it creates (password for all: password123):
//   admin@example.test       admin
//   ref1@example.test ...    referees (ref1 to ref8)
//   vm1@example.test, vm2@example.test   venue managers
//   vm3@example.test         venue manager application waiting for approval

declare(strict_types=1);

function ncf_seed(PDO $pdo): void
{
    foreach (['admins', 'referees', 'venuemanagers', 'emergencycontacts', 'availability', 'appointments',
              'shifts', 'teams', 'players', 'staff', 'pfRegistrations'] as $t) {
        $pdo->exec("DELETE FROM $t");
    }

    $hash = password_hash('password123', PASSWORD_DEFAULT);
    q('INSERT INTO admins (username, password, firstName, lastName) VALUES (?, ?, ?, ?)', ['admin@example.test', $hash, 'Alex', 'Admin']);

    $refs = [['Sam', 'Lee'], ['Jordan', 'Patel'], ['Riley', 'Nguyen'], ['Casey', 'Smith'],
             ['Morgan', 'Brown'], ['Taylor', 'Wilson'], ['Jamie', 'Kaur'], ['Drew', 'Martin']];
    foreach ($refs as $i => [$f, $l]) {
        q('INSERT INTO referees (username, password, FirstName, LastName, DOB, bsb, accNum) VALUES (?, ?, ?, ?, ?, ?, ?)',
            ['ref' . ($i + 1) . '@example.test', $hash, $f, $l, (2000 + $i) . '-0' . (($i % 9) + 1) . '-15', '000000', '1234567' . $i]);
    }
    $vms = [['Pat', 'Walker', 32.50], ['Robin', 'Hall', 30.00]];
    foreach ($vms as $i => [$f, $l, $rate]) {
        q('INSERT INTO venuemanagers (username, password, FirstName, LastName, payRate, mobile, bsb, accNum) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            ['vm' . ($i + 1) . '@example.test', $hash, $f, $l, $rate, '0400000000', '000000', '7654321' . $i]);
    }
    q("INSERT INTO venuemanagers (username, FirstName, LastName, mobile) VALUES ('vm3@example.test', 'Lou', 'Applicant', '0400000001')");
    q("INSERT INTO emergencycontacts (firstName, lastName, mobile, dependant) VALUES ('Kim', 'Applicant', '0400000002', 'vm3@example.test')");

    // Fixtures for this weekend and next.
    $weekends = [this_friday(), date('Y-m-d', strtotime(this_friday() . ' +7 days'))];
    $ages = ['U8', 'U10', 'U12', 'U14', 'U16', 'U18'];
    foreach ($weekends as $w => $fri) {
        $sun = date('Y-m-d', strtotime("$fri +2 days"));
        foreach (['17:00', '18:00', '19:00'] as $t => $time) {
            foreach ([1, 2] as $court) {
                q('INSERT INTO appointments (Date, MatchTime, Venue, Court, AgeGroup) VALUES (?, ?, ?, ?, ?)',
                    [$fri, $time, 'lyn', $court, $ages[($t + $court) % 6] . ' Open']);
            }
        }
        foreach (['lyn', 'gcr', 'rad'] as $venue) {
            foreach (['09:00', '10:00', '11:00'] as $t => $time) {
                q('INSERT INTO appointments (Date, MatchTime, Venue, Court, AgeGroup) VALUES (?, ?, ?, ?, ?)',
                    [$sun, $time, $venue, 1, $ages[$t + 2] . ($t % 2 ? ' Girls' : ' Open')]);
            }
        }
    }
    // Appoint referees to this weekend's Friday games.
    $fri = $weekends[0];
    foreach (q('SELECT appointID FROM appointments WHERE Date = ? ORDER BY appointID', [$fri])->fetchAll(PDO::FETCH_COLUMN) as $n => $id) {
        q('UPDATE appointments SET referee1 = ?, referee2 = ? WHERE appointID = ?',
            ['ref' . ($n % 4 + 1) . '@example.test', 'ref' . ($n % 4 + 5) . '@example.test', $id]);
    }

    // Availability for the next weekend from a few referees and one VM.
    $af = availability_friday();
    $as = availability_sunday();
    foreach (['ref1', 'ref2', 'ref3'] as $r) {
        foreach (['16-17', '18-19'] as $h) q('INSERT INTO availability (username, date, hour, venue) VALUES (?, ?, ?, ?)', ["$r@example.test", $af, $h, 'lyn']);
        q('INSERT INTO availability (username, date, hour, venue) VALUES (?, ?, ?, ?)', ["$r@example.test", $as, '08-9', 'gcr']);
    }
    foreach (['16-17', '18-19', '20-21'] as $h) q('INSERT INTO availability (username, date, hour, venue) VALUES (?, ?, ?, ?)', ['vm1@example.test', $af, $h, 'lyn']);
    q("UPDATE referees SET comments = 'Can only do early games this week.', commentDate = CURDATE() WHERE username = 'ref2@example.test'");

    // Shifts this weekend.
    q("INSERT INTO shifts (date, timeStart, timeEnd, venue, venuemanagerID, confirmed) VALUES (?, '16:15', '22:00', 'lyn', 'vm1@example.test', 1)", [$fri]);
    q("INSERT INTO shifts (date, timeStart, timeEnd, venue, venuemanagerID) VALUES (?, '08:15', '13:45', 'gcr', 'vm2@example.test')", [this_sunday()]);

    // Two nominated teams.
    foreach ([['Northside Hornets', 'U12', 'open', '1'], ['Belconnen Comets', 'U14', 'girls', '2']] as $k => $team) {
        q('INSERT INTO teams (name, age, gender, division, approved) VALUES (?, ?, ?, ?, ?)', [...$team, $k]);
        $tid = (int) $pdo->lastInsertId();
        q("INSERT INTO staff (name, email, mobile, type, team) VALUES (?, ?, '0400000003', 'coach', ?)", ["Coach $k", "coach$k@example.test", $tid]);
        for ($p = 1; $p <= 6; $p++) {
            q('INSERT INTO players (ffa, firstName, lastName, dob, team) VALUES (?, ?, ?, ?, ?)',
                [(string) (100000 + $k * 10 + $p), "Player$p", "Team$k", '2013-05-0' . $p, $tid]);
        }
    }
    q("INSERT INTO pfRegistrations (ffa, firstName, lastName) VALUES ('100001', 'Player1', 'Team0'), ('', 'Player2', 'Team0')");
}

/**
 * Demo mode only: reset to fresh fake data once a day (and on first visit,
 * when the tables are empty). Shared hosts often have no cron, so the first
 * request of the day does it. A lock file stops two requests resetting at once.
 */
function ncf_demo_autoreset(PDO $pdo): void
{
    $dir = NCF_ROOT . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $stamp = $dir . '/demo-reset';
    $fresh = is_file($stamp) && filemtime($stamp) > time() - 86400 && date('Y-m-d', (int) filemtime($stamp)) === date('Y-m-d');
    if ($fresh) {
        return;
    }
    $lock = fopen($dir . '/demo-reset.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return; // another request is resetting right now
    }
    ncf_seed($pdo);
    touch($stamp);
    // Clear uploaded demo photos and certificates as well.
    foreach (glob(cfg('UPLOAD_DIR') . '/*.{jpg,png,gif,pdf,doc,docx}', GLOB_BRACE) ?: [] as $f) {
        if (preg_match('/\/[a-f0-9]{32}\.\w+$/', $f)) @unlink($f);
    }
    flock($lock, LOCK_UN);
    fclose($lock);
}
