-- Create Database
CREATE DATABASE IF NOT EXISTS kidsnesia_db;
USE kidsnesia_db;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Programs Table
CREATE TABLE IF NOT EXISTS programs (
    program_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Reservations Table
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    program_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (program_id) REFERENCES programs(program_id)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    amount INT NOT NULL,
    status ENUM('unpaid', 'pending', 'paid', 'failed') DEFAULT 'unpaid',
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- Insert Sample Programs
INSERT INTO programs (title, description, price, image_path) VALUES
('3D Printing', 'Belajar membuat miniatur 3D dengan desain sendiri.', 30000, 'assets/3dprint.png'),
('Kreasi Sablon', 'Cetak desain di kaos dan bawa pulang hasilnya.', 30000, 'assets/sablon.png'),
('Programmer Cilik', 'Belajar coding sederhana untuk anak-anak.', 30000, 'assets/programmer.png'),
('Aku Cinta Indonesia', 'Mengenal budaya Indonesia dengan cara interaktif.', 10000, 'assets/AACI.png');

-- Insert Sample Admin User
INSERT INTO users (name, email, phone, password, role) VALUES
('Admin Kidsnesia', 'admin@kidsnesia.id', '081234567890', '$2y$10$YourHashedPasswordHere', 'admin');

-- Note: Password hashing should be done in PHP using password_hash()
-- Example for future use: password_hash('password123', PASSWORD_BCRYPT)
