<?php
require_once 'config.php'; // konfigurasi Midtrans dan DB

$data = json_decode(file_get_contents("php://input"), true);

// Ambil data dari request
$title = $data['name'] ?? 'Program Anak';
$price = (int)($data['price'] ?? 10000);
$user_id = (int)($data['user_id'] ?? 0); // user_id dari frontend
$description = '';
$created_at = date("Y-m-d H:i:s");

// Koneksi ke DB
$conn = new mysqli("localhost", "root", "", "reservation");
if ($conn->connect_error) {
    echo json_encode(["error" => "Koneksi ke database gagal"]);
    exit;
}

// Cari program berdasarkan title
$stmt = $conn->prepare("SELECT id FROM programs WHERE title = ?");
$stmt->bind_param("s", $title);
$stmt->execute();
$stmt->store_result();

$program_id = 0;

if ($stmt->num_rows > 0) {
    $stmt->bind_result($program_id);
    $stmt->fetch();
    $stmt->close();
} else {
    $stmt->close();
    // Jika belum ada, tambahkan program
    $description = "Program edukatif dari Kidsnesia.";
    $insert = $conn->prepare("INSERT INTO programs (title, description, price, created_at) VALUES (?, ?, ?, ?)");
    $insert->bind_param("ssis", $title, $description, $price, $created_at);
    $insert->execute();
    $program_id = $insert->insert_id;
    $insert->close();
}

// Simpan ke tabel reservations
$reservation_date = date("Y-m-d H:i:s");
$status = "pending";

if ($user_id > 0 && $program_id > 0) {
    $res = $conn->prepare("INSERT INTO reservations (user_id, program_id, reservation_date, status) VALUES (?, ?, ?, ?)");
    $res->bind_param("iiss", $user_id, $program_id, $reservation_date, $status);
    $res->execute();
    $res->close();
}

$conn->close();

// Buat Snap Token dari Midtrans
$order_id = uniqid('ORDER-');

$transaction = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => $price,
    ],
    'item_details' => [
        [
            'id' => strtolower(str_replace(' ', '-', $title)),
            'price' => $price,
            'quantity' => 1,
            'name' => $title,
        ],
    ],
    'customer_details' => [
        'first_name' => 'Guest',
        'email' => 'guest@example.com',
    ],
];

try {
    $snapToken = \Midtrans\Snap::getSnapToken($transaction);
    echo json_encode(['token' => $snapToken]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
