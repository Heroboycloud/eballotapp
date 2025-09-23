CREATE DATABASE if not exists eballot;
USE eballot;

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

CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    position VARCHAR(100)
);

INSERT INTO candidates (name, position) VALUES 
('John Smith', 'President'),
('Sarah Johnson', 'President'),
('Michael Brown', 'Secretary'),
('Emily Davis', 'Secretary');
