-- DATABASE CREATION SCRIPT
-- David Campos R.
-- 31/12/2016
-- This script creates the database for the server in MySQL.

START TRANSACTION;

-- Table Users
-- Saves the data of the users that use the app
CREATE TABLE Users (
	_id         INT         NOT NULL AUTO_INCREMENT,
	phone       VARCHAR(22) NOT NULL UNIQUE,
	name        VARCHAR(30) NOT NULL,
	picture_id  INT         NOT NULL,
	last_update DATETIME    NOT NULL,
	PRIMARY KEY (_id)
);

-- Table Blocked
-- Saves the relation between users that the decided to block the other
CREATE TABLE Blocked (
	blocker INT NOT NULL,
	blocked INT NOT NULL,
	FOREIGN KEY (blocker) REFERENCES Users (_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (blocked) REFERENCES Users (_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (blocker, blocked)
);

-- Table AppointmentTypes
-- Saves the different types of appointments ther users can choose
-- for the appointments they create.
CREATE TABLE AppointmentTypes (
	name        VARCHAR(50) NOT NULL,
	description TEXT,
	icon_id     INT         NOT NULL,
	PRIMARY KEY (name)
);

-- Table Reasons
-- Saves the data of the different reasons the users can choose to decline
-- an appointment or to propose another timestamp.
CREATE TABLE Reasons (
	name        VARCHAR(50) NOT NULL,
	description TEXT        NOT NULL,
	PRIMARY KEY (name)
);

-- Table Propositions
-- Saves the different propositions of times the users do for a concrete
-- appointment
CREATE TABLE Propositions (
	appointment INT          NOT NULL,
	timestamp   DATETIME     NOT NULL,
	placeLat    FLOAT(10, 6) NOT NULL,
	placeLon    FLOAT(10, 6) NOT NULL,
	placeName   VARCHAR(100) NOT NULL,
	proposer    INT          NOT NULL,
	reason      VARCHAR(50) DEFAULT NULL,
	-- The next is done down because Appointments doesn't exist yet!
	-- FOREIGN KEY (appointment) REFERENCES Appointments(_id)
	--	ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (proposer) REFERENCES Users (_id)
		ON DELETE CASCADE ON UPDATE CASCADE,
	FOREIGN KEY (reason) REFERENCES Reasons (name)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	PRIMARY KEY (appointment, timestamp, placeName)
);

-- Table Appointments
-- Saves the data of the appointments the users can propose
CREATE TABLE Appointments (
	_id              INT         NOT NULL AUTO_INCREMENT,
	name             VARCHAR(50) NOT NULL,
	description      TEXT,
	closed           TINYINT(1)           DEFAULT 0,
	type             VARCHAR(50) NOT NULL,
	creator          INT         NOT NULL,
	currentProposal  DATETIME,
	currentPlaceName VARCHAR(100),
	FOREIGN KEY (type) REFERENCES appointmentTypes (name)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (creator) REFERENCES Users (_id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (_id, currentProposal, currentPlaceName)
	REFERENCES propositions (appointment, timestamp, placeName)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	PRIMARY KEY (_id)
);

-- We alter the table Propositions to add the foreign key to appointments
ALTER TABLE Propositions
	ADD CONSTRAINT fk_appointment
FOREIGN KEY (appointment) REFERENCES appointments (_id)
		ON DELETE CASCADE ON UPDATE CASCADE;

-- Table InvitedTo
-- Saves the relation between users invited to an appointment
-- and the appointment.
CREATE TABLE InvitedTo (
	user        INT                                     NOT NULL,
	appointment INT                                     NOT NULL,
	state       ENUM ('pending', 'accepted', 'refused') NOT NULL DEFAULT 'pending',
	reason      VARCHAR(50)                                      DEFAULT NULL,
	FOREIGN KEY (user) REFERENCES users (_id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (appointment) REFERENCES appointments (_id)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	FOREIGN KEY (reason) REFERENCES reasons (name)
		ON DELETE RESTRICT ON UPDATE CASCADE,
	PRIMARY KEY (user, appointment)
);

-- Table Sessions
-- Saves the information about the current and past sessions the moviles
-- initiated in the server.
CREATE TABLE Sessions (
	_id               INT       NOT NULL AUTO_INCREMENT,
	user              INT       NOT NULL,
	session_key       CHAR(128) NOT NULL,
	initial_timestamp DATETIME  NOT NULL,
	final_timestamp   DATETIME           DEFAULT '0000-00-00 00:00:00',
	FOREIGN KEY (user) REFERENCES users (_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
	PRIMARY KEY (_id)
);

COMMIT;