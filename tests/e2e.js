// End-to-end browser test of every main flow. Needs a freshly seeded local
// database and the dev server running:
//   php db/seed.php --yes && php -S 127.0.0.1:8000 router.php
//   npm install playwright && npx playwright install chromium
//   node tests/e2e.js
// Screenshots go to tests/screenshots/.
const { chromium } = require('playwright');
const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000/';
const path = require('path');
const out = process.argv[2] || path.join(__dirname, 'screenshots');
const LOGO = path.join(__dirname, '..', 'img', 'logo.png');
require('fs').mkdirSync(out, { recursive: true });
let failures = 0;
function check(cond, msg) { console.log((cond ? 'PASS ' : 'FAIL ') + msg); if (!cond) failures++; }

async function login(page, user) {
  await page.goto(BASE + 'index.html');
  await page.fill('#username', user);
  await page.fill('#password', 'password123');
  await Promise.all([page.waitForURL(/homePage|admin/), page.click('#submit')]);
}

(async () => {
  const browser = await chromium.launch();
  const errors = [];
  const newPage = async () => {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const p = await ctx.newPage();
    p.on('pageerror', e => errors.push(p.url() + ' ' + e.message));
    p.on('dialog', d => { p.lastDialog = d.message(); d.accept(d.type() === 'prompt' ? '35' : undefined); });
    return p;
  };

  // --- not logged in
  let p = await newPage();
  await p.goto(BASE + 'homePage.html');
  await p.waitForURL(/index\.html/);
  check(true, 'protected page redirects to login when logged out');
  await p.fill('#username', 'ref1@example.test'); await p.fill('#password', 'wrong');
  await p.click('#submit'); await p.waitForSelector('#incorPass:not([hidden])');
  check(true, 'wrong password shows error');

  // --- referee
  p = await newPage();
  await login(p, 'ref1@example.test');
  await p.waitForFunction(() => document.querySelector('.usernameArea').textContent.length > 0);
  check((await p.textContent('.usernameArea')) === 'Sam Lee', 'referee home shows name');
  await p.screenshot({ path: out + '/ref-home.png' });
  await p.goto(BASE + 'appointments.html');
  await p.waitForSelector('.appointmentList');
  const n = await p.locator('.appointmentList').count();
  check(n >= 1, 'referee sees ' + n + ' appointments');
  await p.screenshot({ path: out + '/ref-appointments.png' });
  await p.goto(BASE + 'availability.html');
  await p.waitForSelector('#sunrad10-11');
  await p.click('#sunrad10-11'); await p.click('#frilyn20-21');
  await p.fill('#comments', 'Test comment from e2e');
  await p.screenshot({ path: out + '/ref-availability.png' });
  await Promise.all([p.waitForURL(/homePage/), p.click('#submit')]);
  await p.waitForSelector('#radsun td');
  check((await p.textContent('#radsun')).includes('10AM-12PM'), 'availability saved and shown on home');
  await p.goto(BASE + 'admin.html');
  await p.waitForURL(/homePage/);
  check(true, 'referee bounced off admin page');
  await p.goto(BASE + 'changeDetails.html');
  await p.fill('#firstName', 'Samuel');
  await p.setInputFiles('#image', LOGO);
  await Promise.all([p.waitForURL(/profile/), p.click('#submit')]);
  await p.waitForFunction(() => document.querySelector('#FirstName').textContent === 'Samuel');
  check(!(await p.getAttribute('#profilePic', 'src')).includes('avatar'), 'profile photo uploaded');
  await p.waitForTimeout(500);
  await p.screenshot({ path: out + '/ref-profile.png' });
  await p.goto(BASE + 'changeDetails.html');
  await p.fill('#currentPassword', 'password123'); await p.fill('#newPassword', 'newpass456'); await p.fill('#newPassword2', 'newpass456');
  await p.click('#passwordSubmit'); await p.waitForTimeout(800);
  check(p.lastDialog === 'Password changed.', 'password change: ' + p.lastDialog);

  // --- venue manager
  p = await newPage();
  await login(p, 'vm1@example.test');
  await p.goto(BASE + 'shifts.html');
  await p.waitForSelector('.appointmentList');
  check((await p.locator('.appointmentList').count()) >= 1, 'VM sees shifts');
  await p.screenshot({ path: out + '/vm-shifts.png' });
  await p.goto(BASE + 'availability.html');
  await p.waitForSelector('#sungcr12-13');
  await p.click('#sungcr12-13');
  await Promise.all([p.waitForURL(/homePage/), p.click('#submit')]);

  // --- public forms
  p = await newPage();
  await p.goto(BASE + 'nominationForm.html');
  await p.fill('#nameTeam', 'E2E Eagles'); await p.selectOption('#ageGroup', 'U10'); await p.selectOption('#gender', 'open'); await p.selectOption('#division', '1');
  await p.fill('#nameCoach', 'Chris Coach'); await p.fill('#emailCoach', 'chris@example.test'); await p.fill('#mobileCoach', '0400111222');
  await p.fill('#wwvpNoCoach', '123456'); await p.fill('#wwvpExCoach', '2027-06-30');
  for (let i = 1; i <= 5; i++) { await p.fill('#firstName' + i, 'Kid' + i); await p.fill('#lastName' + i, 'Eagle'); }
  await p.click('#submitBtn');
  await p.waitForSelector('.alert-success');
  check(true, 'team nomination submitted: ' + await p.textContent('.alert-success'));
  await p.goto(BASE + 'venueManagerForm.html');
  await p.fill('#firstName', 'Vic'); await p.fill('#lastName', 'Venue'); await p.fill('#email', 'vic@example.test');
  await p.setInputFiles('#firstAid', LOGO);
  await p.click('.submitBtn');
  await p.waitForSelector('.alert-success');
  check(true, 'venue manager application submitted');

  // --- admin
  p = await newPage();
  await login(p, 'admin@example.test');
  await p.waitForSelector('.commentElement');
  check((await p.textContent('#userComments')).includes('Test comment from e2e'), 'admin sees referee comment');
  await p.waitForTimeout(1500);
  await p.screenshot({ path: out + '/admin-dashboard.png' });

  await p.goto(BASE + 'refereeSchedule.html');
  await p.waitForSelector('#scheduleDiv input');
  check((await p.locator('#scheduleDiv input').count()) === 18, 'schedule shows 6 Friday matches x 3 slots');
  // next weekend Friday: ref1 + others available at lyn 18-19
  await p.selectOption('#weekNum', { index: 1 });
  await p.waitForTimeout(1000);
  const listOpts = await p.locator('#scheduleDiv datalist option').evaluateAll(o => o.map(x => x.value));
  check(listOpts.includes('Samuel Lee'), 'available referees offered in picker: ' + [...new Set(listOpts)].join(', '));
  const first = p.locator('#scheduleDiv input').first();
  await first.fill('Samuel Lee');
  await p.click('#submitBtn'); await p.waitForTimeout(1000);
  check(p.lastDialog === 'Schedule saved.', 'schedule saved: ' + p.lastDialog);
  await p.fill('#addMatchForm [name=date]', '2026-11-27'); await p.fill('#addMatchForm [name=time]', '19:00');
  await p.fill('#addMatchForm [name=ageGroup]', 'U16 Girls'); await p.click('#addMatchForm [type=submit]');
  await p.waitForTimeout(1000);
  check((await p.locator('#weekNum option').allTextContents()).some(t => t.includes('27 Nov')), 'added match creates new week');
  await p.setInputFiles('#importForm [name=csv]', { name: 'm.csv', mimeType: 'text/csv', buffer: Buffer.from('date,time,venue,court,agegroup\n2026-11-29,09:00,rad,1,U8 Open\n2026-11-29,10:00,rad,1,U9 Open\n') });
  await p.click('#importForm [type=submit]'); await p.waitForTimeout(1000);
  check(p.lastDialog === 'Imported 2 matches.', 'CSV import: ' + p.lastDialog);
  await p.selectOption('#weekNum', '2026-11-13'); await p.waitForTimeout(800);
  await p.screenshot({ path: out + '/admin-schedule.png', fullPage: true });
  await p.click('#notifyBtn'); await p.waitForTimeout(1000);
  check(/Emailed \d+ referees/.test(p.lastDialog), 'notify referees: ' + p.lastDialog);

  await p.goto(BASE + 'venueManagerSchedule.html');
  await p.waitForSelector('#userTable .row:not(.header)');
  await p.click('#mainvmBtns button:text("Availability")'); await p.waitForTimeout(800);
  check((await p.textContent('#lynfriaft')).includes('Pat Walker'), 'VM availability shown');
  await p.click('#mainvmBtns button:text("Shifts")');
  await p.click('#newShift');
  await p.fill('#userTable [name=name]', 'Robin');
  await p.click('.submitShift'); await p.waitForTimeout(800);
  check(p.lastDialog === 'Successful submission', 'admin adds shift: ' + p.lastDialog);

  await p.goto(BASE + 'payments.html');
  await p.waitForFunction(() => document.querySelector('#totalCost') && document.querySelector('#totalCost').textContent.length > 1);
  check(/\$\d/.test(await p.textContent('#totalCost')), 'payments total: ' + await p.textContent('#totalCost'));
  await p.waitForTimeout(1200);
  await p.screenshot({ path: out + '/admin-payments.png' });
  await p.click('#mainPaymentBtns button:text("Referee List")');
  await p.waitForTimeout(1000);
  await p.screenshot({ path: out + '/admin-referee-pay.png' });

  await p.goto(BASE + 'nominationFormData.html');
  await p.waitForSelector('.approve');
  check((await p.textContent('#mainTable')).includes('E2E Eagles'), 'admin sees new team');
  await p.locator('.approve').first().check(); await p.waitForTimeout(500);
  await p.screenshot({ path: out + '/admin-nominations.png' });

  await p.goto(BASE + 'users.html');
  await p.waitForSelector('#usersTable .row:not(.header)');
  check((await p.textContent('#usersTable')).includes('Waiting for approval'), 'pending VM listed');
  await p.fill('#addRefForm [name=firstName]', 'New'); await p.fill('#addRefForm [name=lastName]', 'Ref'); await p.fill('#addRefForm [name=email]', 'newref@example.test');
  await p.click('#addRefForm [type=submit]'); await p.waitForTimeout(800);
  const pw = (p.lastDialog.match(/is:\n\n(\S+)/) || [])[1];
  check(!!pw, 'admin added referee, temp password shown');
  await p.locator('.row:has-text("vic@example.test") button[data-action=reset]').click(); await p.waitForTimeout(800);
  const vmpw = (p.lastDialog.match(/:\n\n(\S+)/) || [])[1];
  check(!!vmpw, 'admin approved VM application');
  await p.locator('.row:has-text("vic@example.test") button[data-action=payRate]').click(); await p.waitForTimeout(800);
  await p.screenshot({ path: out + '/admin-users.png', fullPage: true });

  // new referee can log in with temp password
  const p2 = await newPage();
  await p2.goto(BASE + 'index.html');
  await p2.fill('#username', 'newref@example.test'); await p2.fill('#password', pw);
  await Promise.all([p2.waitForURL(/homePage/), p2.click('#submit')]);
  check(true, 'new referee logs in with temp password');

  // phone width
  const phone = await browser.newContext({ viewport: { width: 390, height: 844 } });
  const pp = await phone.newPage();
  await pp.goto(BASE + 'index.html'); await pp.fill('#username', 'ref2@example.test'); await pp.fill('#password', 'password123');
  await Promise.all([pp.waitForURL(/homePage/), pp.click('#submit')]);
  await pp.goto(BASE + 'appointments.html'); await pp.waitForSelector('.appointmentList');
  await pp.screenshot({ path: out + '/phone-appointments.png' });

  check(errors.length === 0, 'no page JS errors ' + JSON.stringify(errors));
  await browser.close();
  console.log(failures ? failures + ' FAILED' : 'ALL PASSED');
  process.exit(failures ? 1 : 0);
})().catch(e => { console.error(e); process.exit(1); });
