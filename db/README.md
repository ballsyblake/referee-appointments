# Database

- `schema.sql` - all tables. Column names come from the code; types, keys and
  the generated `appointments.cost` and `shifts.hours` columns come from the
  structure of 2021 phpMyAdmin exports. Tested on MariaDB 10.11.
- `seed.php` - fills a local database with fake people and fixtures for this
  weekend and next. Wipes every table first. Never run it on a live database.
- `create-admin.php` - creates or resets an admin login on any database.

Match pay (`appointments.cost`) follows the 2022-23 portal: U10 and older $25,
U8 and U9 $20. Change the expression in `schema.sql` if the rates change.

Do not commit data exports: these tables hold names, dates of birth, bank
details and contact numbers.
