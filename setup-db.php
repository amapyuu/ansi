<?php
/**
 * Database Setup Script untuk Kidsnesia
 * Jalankan: php setup-db.php
 */

$conn = new mysqli("localhost", "root", "", "");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Buat database
$sql = "CREATE DATABASE IF NOT EXISTS kidsnesia_db;";
if ($conn->query($sql) === TRUE) {
    echo "✓ Database 'kidsnesia_db' created/exists\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Switch ke database
$conn->select_db("kidsnesia_db");

// Buat tabel users
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);
echo "✓ Table 'users' ready\n";

// Buat tabel programs
$sql = "CREATE TABLE IF NOT EXISTS programs (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price INT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);
echo "✓ Table 'programs' ready\n";

// Buat tabel reservations
$sql = "CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    program_id INT NOT NULL,
    reservation_date DATE NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (program_id) REFERENCES programs(program_id)
)";
$conn->query($sql);
echo "✓ Table 'reservations' ready\n";

// Buat tabel payments
$sql = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount INT NOT NULL,
    status ENUM('unpaid', 'pending', 'paid', 'failed') DEFAULT 'unpaid',
    payment_method VARCHAR(50),
    midtrans_token VARCHAR(255),
    transaction_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
)";
$conn->query($sql);
echo "✓ Table 'payments' ready\n";

// Insert sample programs
$programs = [
    ['3D Printing', 'Belajar membuat miniatur 3D dengan desain sendiri.', 30000],
    ['Kreasi Sablon', 'Cetak desain di kaos dan bawa pulang hasilnya.', 30000],
    ['Programmer Cilik', 'Belajar coding sederhana untuk anak-anak.', 30000],
    ['Aku Cinta Indonesia', 'Mengenal budaya Indonesia dengan cara interaktif.', 10000]
];

foreach ($programs as $prog) {
    $title = $prog[0];
    $desc = $prog[1];
    $price = (int)$prog[2];
    $stmt = $conn->prepare("INSERT IGNORE INTO programs (title, description, price) VALUES (?, ?, ?)");
    if (!$stmt) {
        echo "Prepare error: " . $conn->error . "\n";
        continue;
    }
    $stmt->bind_param("ssi", $title, $desc, $price);
    $stmt->execute();
    $stmt->close();
}
echo "✓ Sample programs inserted\n";

// Insert admin account
$adminEmail = 'admin@kidsnesia.id';
$adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
$adminName = 'Admin Kidsnesia';
$adminPhone = '021234567890';
$adminRole = 'admin';

$stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    echo "Prepare error: " . $conn->error . "\n";
} else {
    $stmt->bind_param("sssss", $adminName, $adminEmail, $adminPhone, $adminPassword, $adminRole);
    $stmt->execute();
    $stmt->close();
    echo "✓ Admin account created (admin@kidsnesia.id / admin123)\n";
}

$conn->close();
echo "\n✅ Database setup complete!\n";
?>
