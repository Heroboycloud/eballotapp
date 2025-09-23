CREATE DATABASE if not exists eballot;
USE eballot;

/*
CREATE TABLE voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    has_paid BOOLEAN DEFAULT FALSE,
    has_voted BOOLEAN DEFAULT FALSE,
    vote_candidate VARCHAR(100) DEFAULT NULL,
    payment_amount DECIMAL(10,2),
    payment_date DATETIME
);
*/



/*
-- Update the voters table to track payments from multiple sheets
ALTER TABLE voters ADD COLUMN payment_source_1 BOOLEAN DEFAULT FALSE;
ALTER TABLE voters ADD COLUMN payment_source_2 BOOLEAN DEFAULT FALSE;
ALTER TABLE voters ADD COLUMN payment_amount_1 DECIMAL(10,2) DEFAULT 0;
ALTER TABLE voters ADD COLUMN payment_amount_2 DECIMAL(10,2) DEFAULT 0;
*/
-- Or recreate the table if needed:
DROP TABLE IF EXISTS voters;
CREATE TABLE voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    has_paid BOOLEAN DEFAULT FALSE,
    has_voted BOOLEAN DEFAULT FALSE,
    vote_candidate VARCHAR(100) DEFAULT NULL,
    payment_source_1 BOOLEAN DEFAULT FALSE,
    payment_source_2 BOOLEAN DEFAULT FALSE,
    payment_amount_1 DECIMAL(10,2) DEFAULT 0,
    payment_amount_2 DECIMAL(10,2) DEFAULT 0,
    total_payment_amount DECIMAL(10,2) DEFAULT 0
);



CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    position VARCHAR(100)
);


-- insert or edit name odf peopleto o vote for

INSERT INTO candidates (name, position) VALUES 
('Dewale thomas', 'President'),
('Daniel josiah', 'President'),
('Michael Adeola', 'Secretary'),
('Emily Ayomide', 'Secretary');
