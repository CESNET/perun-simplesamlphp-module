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