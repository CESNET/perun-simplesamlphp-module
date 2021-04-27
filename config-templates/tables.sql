-- Script for creating tables
CREATE TABLE whiteList (
	date DATETIME NOT NULL DEFAULT current_timestamp,
	entityId VARCHAR(255) NOT NULL,
	reason VARCHAR(255),
	INDEX (entityId),
	PRIMARY KEY (entityId)
);

CREATE TABLE greyList (
	date DATETIME NOT NULL DEFAULT current_timestamp,
	entityId VARCHAR(255) NOT NULL,
	reason VARCHAR(255),
	INDEX (entityId),
	PRIMARY KEY (entityId)
);

CREATE TABLE scriptChallenges (
    id VARCHAR(255) NOT NULL,
    challenge VARCHAR(255) NOT NULL,
    script VARCHAR(255) NOT NULL,
    date DATETIME NOT NULL DEFAULT current_timestamp,
    PRIMARY KEY (id)
);

CREATE INDEX idx_date on scriptChallenges (date);
