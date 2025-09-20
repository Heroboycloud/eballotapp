
DROP TABLE IF EXISTS voters;
CREATE TABLE voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    email VARCHAR(100),
    has_paid BOOLEAN DEFAULT FALSE,
    has_voted BOOLEAN DEFAULT FALSE,
    vote_president VARCHAR(100) DEFAULT NULL,
    vote_secretary VARCHAR(100) DEFAULT NULL,
    payment_source_1 BOOLEAN DEFAULT FALSE,
    payment_source_2 BOOLEAN DEFAULT FALSE,
    payment_amount_1 DECIMAL(10,2) DEFAULT 0,
    payment_amount_2 DECIMAL(10,2) DEFAULT 0,
    total_payment_amount DECIMAL(10,2) DEFAULT 0
);

-- Update candidates table to include position information
DROP TABLE IF EXISTS candidates;
CREATE TABLE candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    position VARCHAR(100)
);

-- Insert sample candidates for both positions
INSERT INTO candidates (name, position) VALUES 
('Akintayo Ogunjide','President'),
('Funmi Olabiyi','Vice President'),
('Omolade Adeniji','Vice President'),
('Hammed Kamorudeen','General Secretary'),
('Olagunju Akeem','General Secretary'),
('Adekunle Raji','Fin.Secrerary'),
('Adeleke Adeniyi','Treasurer'),
('Olaniyi Wakeel','PRO');

