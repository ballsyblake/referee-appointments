<?php
require __DIR__ . '/lib/bootstrap.php';
require_admin();
json_out(q('SELECT hours FROM shifts WHERE venuemanagerID = ?', [post('username')])->fetchAll(PDO::FETCH_ASSOC));
