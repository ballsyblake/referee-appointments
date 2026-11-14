-- NCF Referee App: database schema
--
-- Built from two sources:
--   1. the queries in refereeapp/*.php (column names), and
--   2. the table structure (structure only, no rows) of 2021 phpMyAdmin
--      exports found in older repos. Those gave the real types, the primary
--      keys, and the two generated columns: appointments.cost and shifts.hours.
--
-- Loaded and tested against MariaDB 10.11. Works on MySQL 8 too.
--
-- Load:  mysql -u root ncf < db/schema.sql
-- Seed:  php db/seed.php            (fake people only, never real data)

SET NAMES utf8mb4;

-- Admin logins. Admins are kept apart from referees so they never show up
-- in referee lists, payments or appointment pickers.
CREATE TABLE IF NOT EXISTS admins (
  username     VARCHAR(255) NOT NULL,
  password     VARCHAR(255) NOT NULL,          -- password_hash() output
  firstName    VARCHAR(100) NOT NULL DEFAULT '',
  lastName     VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Referees. `username` is the login and is an email address.
CREATE TABLE IF NOT EXISTS referees (
  username     VARCHAR(255) NOT NULL,
  password     VARCHAR(255) NULL,              -- password_hash() output; NULL = cannot log in yet
  FirstName    VARCHAR(100) NOT NULL DEFAULT '',
  LastName     VARCHAR(100) NOT NULL DEFAULT '',
  DOB          DATE         NULL,
  Level        INT          NULL,
  bsb          VARCHAR(7)   NULL,
  accNum       VARCHAR(12)  NULL,
  profilePic   VARCHAR(255) NULL,
  comments     TEXT         NULL,
  commentDate  DATE         NULL,
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Venue managers. Rows are created by the public application form
-- (addNewVM.php) with no password; an admin approves them by setting a
-- pay rate and a password on the Users page.
CREATE TABLE IF NOT EXISTS venuemanagers (
  username     VARCHAR(255) NOT NULL,
  password     VARCHAR(255) NULL,
  FirstName    VARCHAR(100) NOT NULL DEFAULT '',
  LastName     VARCHAR(100) NOT NULL DEFAULT '',
  DOB          DATE         NULL,
  street       VARCHAR(255) NULL,
  suburb       VARCHAR(100) NULL,
  postcode     VARCHAR(10)  NULL,
  mobile       VARCHAR(20)  NULL,
  wwvpNo       VARCHAR(50)  NULL,   -- Working With Vulnerable People number
  wwvpEx       DATE         NULL,   -- and its expiry
  accName      VARCHAR(100) NULL,
  bsb          VARCHAR(7)   NULL,
  accNum       VARCHAR(12)  NULL,
  firstAid     VARCHAR(255) NULL,   -- stored file name of the uploaded certificate
  payRate      DECIMAL(6,2) NOT NULL DEFAULT 0,
  profilePic   VARCHAR(255) NULL,
  comments     TEXT         NULL,
  commentDate  DATE         NULL,
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency contact for a venue manager. `dependant` holds the venue
-- manager's username (the original code's naming).
CREATE TABLE IF NOT EXISTS emergencycontacts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  firstName    VARCHAR(100) NULL,
  lastName     VARCHAR(100) NULL,
  email        VARCHAR(255) NULL,
  mobile       VARCHAR(20)  NULL,
  altMobile    VARCHAR(20)  NULL,
  dependant    VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_ec_dependant (dependant)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per person, date, hour slot and venue.
-- hour is a slot label such as '16-17'; venue is a short code: lyn, gcr, rad.
CREATE TABLE IF NOT EXISTS availability (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username     VARCHAR(255) NOT NULL,
  date         DATE         NOT NULL,
  hour         VARCHAR(10)  NOT NULL,
  venue        VARCHAR(10)  NOT NULL,
  PRIMARY KEY (id),
  KEY idx_availability_user (username),
  KEY idx_availability_slot (date, venue, hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matches and who is appointed to them. referee1-4 and coach hold referee
-- usernames, or NULL when the slot is empty.
-- cost is what one referee is paid for the match. It was a generated column
-- in production; the rule here follows the 2022-23 portal text:
-- U10 and older $25, U8/U9 $20.
CREATE TABLE IF NOT EXISTS appointments (
  appointID    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  Date         DATE         NOT NULL,
  MatchTime    TIME         NOT NULL,
  Venue        VARCHAR(10)  NOT NULL,
  Court        VARCHAR(20)  NOT NULL DEFAULT '1',
  AgeGroup     VARCHAR(25)  NOT NULL,
  referee1     VARCHAR(255) NULL,
  referee2     VARCHAR(255) NULL,
  referee3     VARCHAR(255) NULL,
  referee4     VARCHAR(255) NULL,
  coach        VARCHAR(255) NULL,
  cost         INT GENERATED ALWAYS AS (
                 IF(CAST(SUBSTR(AgeGroup, 2, 2) AS SIGNED) >= 10, 25, 20)
               ) VIRTUAL,
  PRIMARY KEY (appointID),
  KEY idx_appointments_date (Date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Venue manager shifts. venuemanagerID holds a venuemanagers.username.
-- hours is generated from the start and end time, as in production.
CREATE TABLE IF NOT EXISTS shifts (
  shiftID        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  date           DATE         NOT NULL,
  timeStart      TIME         NOT NULL,
  timeEnd        TIME         NOT NULL,
  venue          VARCHAR(10)  NOT NULL,
  venuemanagerID VARCHAR(255) NOT NULL,
  hours          DECIMAL(5,2) GENERATED ALWAYS AS (
                   TIMESTAMPDIFF(MINUTE, timeStart, timeEnd) / 60
                 ) VIRTUAL,
  confirmed      TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (shiftID),
  KEY idx_shifts_date (date),
  KEY idx_shifts_vm (venuemanagerID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team nominations (public form: nominationForm.html).
CREATE TABLE IF NOT EXISTS teams (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(150) NOT NULL,
  age          VARCHAR(5)   NOT NULL,
  gender       VARCHAR(10)  NOT NULL,
  division     VARCHAR(5)   NOT NULL,
  approved     TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Players. team is a teams.id; 0 means removed from a team.
CREATE TABLE IF NOT EXISTS players (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ffa          VARCHAR(30)  NULL,   -- Football Australia registration number
  firstName    VARCHAR(100) NOT NULL,
  lastName     VARCHAR(100) NOT NULL,
  dob          DATE         NULL,
  team         INT UNSIGNED NOT NULL DEFAULT 0,
  registered   TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_players_team (team)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team staff. type is 'coach', 'manager' or '3rd'. team is a teams.id.
CREATE TABLE IF NOT EXISTS staff (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name         VARCHAR(200) NOT NULL,
  email        VARCHAR(255) NULL,
  mobile       VARCHAR(20)  NULL,
  wwvpNo       VARCHAR(50)  NULL,
  wwvpEx       DATE         NULL,
  type         VARCHAR(20)  NOT NULL,
  team         INT UNSIGNED NOT NULL,
  registered   TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_staff_team (team)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Player registration list (e.g. an export from the registration system)
-- that updateReg.php checks players against. Import it by hand.
CREATE TABLE IF NOT EXISTS pfRegistrations (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ffa          VARCHAR(30)  NULL,
  firstName    VARCHAR(100) NULL,
  lastName     VARCHAR(100) NULL,
  PRIMARY KEY (id),
  KEY idx_pf_ffa (ffa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
