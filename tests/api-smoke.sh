#!/usr/bin/env bash
# Quick security checks against a running local server with seed data.
#   php db/seed.php --yes && php -S 127.0.0.1:8000 router.php
#   bash tests/api-smoke.sh
set -u
B=${BASE_URL:-http://127.0.0.1:8000}
J=$(mktemp -d)
X=(-H 'X-Requested-With: XMLHttpRequest')
fail=0
expect() { # name, expected, actual
  if [[ "$3" == *"$2"* ]]; then echo "PASS $1"; else echo "FAIL $1: expected '$2', got '$3'"; fail=1; fi
}
code() { curl -s -o /dev/null -w '%{http_code}' "$@"; }

expect "logged out -> 401" 401 "$(code "${X[@]}" -X POST $B/refereeapp/getAllReferees.php)"
expect "POST without AJAX header -> 403" 403 "$(code -d 'username=a&password=b' $B/refereeapp/connect.php)"
expect "wrong password rejected" '"success":false' "$(curl -s "${X[@]}" -d 'username=ref1@example.test&password=nope' $B/refereeapp/connect.php)"
expect "referee login" '"userType":"referee"' "$(curl -s -c $J/ref "${X[@]}" -d 'username=ref1@example.test&password=password123' $B/refereeapp/connect.php)"
expect "referee blocked from admin endpoint" 403 "$(code -b $J/ref "${X[@]}" -X POST $B/refereeapp/getAllReferees.php)"
expect "referee cannot read others' availability" '"date"' "$(curl -s -b $J/ref "${X[@]}" -d 'username=ref2@example.test' $B/refereeapp/getAvailability.php)"
expect "admin login" '"userType":"admin"' "$(curl -s -c $J/adm "${X[@]}" -d 'username=admin@example.test&password=password123' $B/refereeapp/connect.php)"
expect "SQL injection in schedule is treated as a name" 'Nothing saved' "$(curl -s -b $J/adm "${X[@]}" -d "temp[1999]=x' OR '1'='1" $B/refereeapp/setSchedule.php)"
for p in refereeapp/lib/bootstrap.php db/schema.sql refereeapp/config.example.php storage/mail.log uploads/avatar.png; do
  c=$(code $B/$p)
  if [[ "$c" == 403 || "$c" == 404 ]]; then echo "PASS blocked: $p ($c)"; else echo "FAIL blocked: $p got $c"; fail=1; fi
done
expect "no CORS header" '' "$(curl -s -I $B/refereeapp/me.php | grep -i access-control || true)"
rm -rf $J
exit $fail
