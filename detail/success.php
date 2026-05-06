<?php
session_start();
require_once '../vendor/autoload.php';

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$reservationId = intval($_GET['reservation_id'] ?? 0);

$sql = "SELECT r.*, p.title AS program_title, p.price, u.name AS user_name
        FROM reservations r
        JOIN programs p ON r.program_id = p.program_id
        JOIN users u ON r.user_id = u.user_id
        WHERE r.reservation_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran Berhasil - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif; background: #f4f7f8;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 50px 40px; max-width: 440px; width: 100%; text-align: center;
        }
        .check-icon {
            width: 80px; height: 80px; background: #e8f5e9;
            border-radius: 50%; display: flex; align-items: center;
            justify-content: center; margin: 0 auto 20px;
        }
        .check-icon svg { width: 40px; height: 40px; color: #2e7d32; }
        h2 { font-size: 24px; color: #1c1c1c; margin-bottom: 8px; }
        .subtitle { color: #777; font-size: 14px; margin-bottom: 30px; }

        .summary {
            background: #f8f9fb; border-radius: 12px; padding: 20px;
            text-align: left; margin-bottom: 30px;
        }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .summary-row .key { color: #555; }
        .summary-row .val { font-weight: 600; }
        .badge {
            display: inline-block; padding: 4px 12px;
            background: #d4edda; color: #155724; border-radius: 50px; font-size: 12px; font-weight: 600;
        }

        .btn-home {
            display: inline-block; background: #ff3c8a; color: white;
            padding: 14px 36px; border-radius: 50px; text-decoration: none;
            font-weight: 600; font-size: 15px; transition: background 0.3s;
        }
        .btn-home:hover { background: #e6006b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="check-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="#2e7d32" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h2>Pembayaran Berhasil! 🎉</h2>
        <p class="subtitle">Reservasimu sudah dikonfirmasi. Sampai jumpa di Kidsnesia!</p>

        <?php if ($data): ?>
        <div class="summary">
            <div class="summary-row">
                <span class="key">Nama</span>
                <span class="val"><?= htmlspecialchars($data['user_name']) ?></span>
            </div>
            <div class="summary-row">
                <span class="key">Program</span>
                <span class="val"><?= htmlspecialchars($data['program_title']) ?></span>
            </div>
            <div class="summary-row">
                <span class="key">Tanggal</span>
                <span class="val"><?= date('d M Y', strtotime($data['reservation_date'])) ?></span>
            </div>
            <div class="summary-row">
                <span class="key">Total</span>
                <span class="val" style="color:#ff3c8a;">Rp <?= number_format($data['price'], 0, ',', '.') ?></span>
            </div>
            <div class="summary-row">
                <span class="key">Status</span>
                <span class="val"><span class="badge">Confirmed</span></span>
            </div>
        </div>
        <?php endif; ?>

        <a href="../index.html" class="btn-home">Kembali ke Beranda</a>
    </div>
</body>
</html>