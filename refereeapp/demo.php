<?php
// Public: on the demo site, the logins visitors can use. Empty otherwise.
require __DIR__ . '/lib/bootstrap.php';
if (cfg('DEMO') !== '1') {
    json_out(['demo' => false]);
}
json_out(['demo' => true, 'password' => 'password123', 'logins' => [
    ['role' => 'Admin', 'username' => 'admin@example.test'],
    ['role' => 'Referee', 'username' => 'ref1@example.test'],
    ['role' => 'Venue manager', 'username' => 'vm1@example.test'],
]]);
