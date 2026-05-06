<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['reservation_id'], $data['payment_id'], $data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
    exit;
}

$status = $data['status']; // 'paid' / 'failed'
$paymentId = intval($data['payment_id']);
$reservationId = intval($data['reservation_id']);

// Update tabel payments
$stmt = $conn->prepare("UPDATE payments SET status = ? WHERE payment_id = ?");
$stmt->bind_param("si", $status, $paymentId);
$stmt->execute();
$stmt->close();

// Update tabel reservations
$reservationStatus = ($status === 'paid') ? 'confirmed' : 'cancelled';
$stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
$stmt->bind_param("si", $reservationStatus, $reservationId);
$stmt->execute();
$stmt->close();

$conn->close();

echo json_encode(['success' => true]);