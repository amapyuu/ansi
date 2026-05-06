<?php
session_start();
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Konfigurasi Midtrans
\Midtrans\Config::$serverKey = $_ENV['MIDTRANS_SERVER_KEY'] ?? '';
\Midtrans\Config::$isProduction = false; // Ganti true saat production
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil data reservasi
$reservationId = intval($_GET['reservation_id'] ?? 0);
if (!$reservationId) {
    header("Location: ../program.html");
    exit;
}

// Join reservasi + program + payment + user
$sql = "SELECT r.*, p.title AS program_title, p.price, py.payment_id, py.status AS payment_status,
               u.name AS user_name, u.email AS user_email, u.phone AS user_phone
        FROM reservations r
        JOIN programs p ON r.program_id = p.program_id
        JOIN payments py ON py.reservation_id = r.reservation_id
        JOIN users u ON r.user_id = u.user_id
        WHERE r.reservation_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    die("Reservasi tidak ditemukan.");
}

// Kalau sudah dibayar, redirect ke success
if ($data['payment_status'] === 'paid') {
    header("Location: success.php?reservation_id=$reservationId");
    exit;
}

// Generate Snap Token dari Midtrans
$orderId = 'KIDS-' . $reservationId . '-' . time();
$snapToken = null;
$snapError = null;

try {
    $params = [
        'transaction_details' => [
            'order_id'     => $orderId,
            'gross_amount' => (int)$data['price'],
        ],
        'customer_details' => [
            'first_name' => $data['user_name'],
            'email'      => $data['user_email'],
            'phone'      => $data['user_phone'],
        ],
        'item_details' => [[
            'id'       => 'PROG-' . $data['program_id'],
            'price'    => (int)$data['price'],
            'quantity' => 1,
            'name'     => $data['program_title'],
        ]],
    ];

    $snapToken = \Midtrans\Snap::getSnapToken($params);
} catch (Exception $e) {
    $snapError = $e->getMessage();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pembayaran - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="<?= htmlspecialchars($_ENV['MIDTRANS_CLIENT_KEY'] ?? '') ?>"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7f8; min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }

        .payment-card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 50px 40px; max-width: 460px; width: 100%;
            text-align: center;
        }

        .logo { margin-bottom: 24px; }
        .logo img { height: 70px; }

        h2 { font-size: 22px; color: #1c1c1c; margin-bottom: 6px; }
        .subtitle { color: #777; font-size: 14px; margin-bottom: 30px; }

        .order-summary {
            background: #f8f9fb; border-radius: 12px; padding: 20px;
            text-align: left; margin-bottom: 30px;
        }
        .order-summary h4 { font-size: 13px; text-transform: uppercase; color: #999; margin-bottom: 12px; }
        .order-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .order-row .key { color: #555; }
        .order-row .val { font-weight: 600; color: #1c1c1c; }
        .order-row .val.total { color: #ff3c8a; font-size: 18px; }
        .divider { border: none; border-top: 1px dashed #ddd; margin: 12px 0; }

        .status-badge {
            display: inline-block; padding: 4px 12px; border-radius: 50px;
            font-size: 12px; font-weight: 600; margin-top: 4px;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d1edff; color: #0066cc; }

        .btn-pay {
            width: 100%; padding: 16px; background: #ff3c8a;
            color: white; border: none; border-radius: 50px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            transition: background 0.3s; margin-bottom: 12px;
        }
        .btn-pay:hover { background: #e6006b; }
        .btn-pay:disabled { background: #ccc; cursor: not-allowed; }

        .back-link { color: #008bd8; text-decoration: none; font-size: 13px; }
        .back-link:hover { text-decoration: underline; }

        .error-box {
            background: #ffe5e5; color: #cc0000;
            border-radius: 10px; padding: 16px;
            font-size: 13px; margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <div class="logo">
            <a href="../index.html"><img src="../assets/Logo.png" alt="Kidsnesia" /></a>
        </div>

        <h2>Konfirmasi Pembayaran</h2>
        <p class="subtitle">Selesaikan pembayaran untuk mengkonfirmasi reservasimu</p>

        <?php if ($snapError): ?>
            <div class="error-box">
                Gagal menghubungi Midtrans: <?= htmlspecialchars($snapError) ?>
            </div>
        <?php endif; ?>

        <div class="order-summary">
            <h4>Ringkasan Pesanan</h4>
            <div class="order-row">
                <span class="key">Program</span>
                <span class="val"><?= htmlspecialchars($data['program_title']) ?></span>
            </div>
            <div class="order-row">
                <span class="key">Tanggal</span>
                <span class="val"><?= date('d M Y', strtotime($data['reservation_date'])) ?></span>
            </div>
            <div class="order-row">
                <span class="key">Atas Nama</span>
                <span class="val"><?= htmlspecialchars($data['user_name']) ?></span>
            </div>
            <div class="order-row">
                <span class="key">Status</span>
                <span class="val">
                    <span class="status-badge status-<?= $data['payment_status'] ?>">
                        <?= ucfirst($data['payment_status']) ?>
                    </span>
                </span>
            </div>
            <hr class="divider" />
            <div class="order-row">
                <span class="key">Total</span>
                <span class="val total">Rp <?= number_format($data['price'], 0, ',', '.') ?></span>
            </div>
        </div>

        <?php if ($snapToken): ?>
            <button class="btn-pay" id="pay-btn">Bayar Sekarang</button>
        <?php else: ?>
            <button class="btn-pay" disabled>Bayar Sekarang</button>
        <?php endif; ?>

        <br />
        <a href="../program.html" class="back-link">← Kembali ke Program</a>
    </div>

    <?php if ($snapToken): ?>
    <script>
        document.getElementById('pay-btn').onclick = function () {
            snap.pay('<?= $snapToken ?>', {
                onSuccess: function(result) {
                    // Update status pembayaran via AJAX
                    fetch('../payment/update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            reservation_id: <?= $reservationId ?>,
                            payment_id: <?= $data['payment_id'] ?>,
                            order_id: result.order_id,
                            status: 'paid'
                        })
                    }).then(() => {
                        window.location.href = 'success.php?reservation_id=<?= $reservationId ?>';
                    });
                },
                onPending: function(result) {
                    alert('Pembayaran pending. Silakan selesaikan pembayaranmu.');
                },
                onError: function(result) {
                    fetch('../payment/update_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            reservation_id: <?= $reservationId ?>,
                            payment_id: <?= $data['payment_id'] ?>,
                            status: 'failed'
                        })
                    });
                    alert('Pembayaran gagal. Silakan coba lagi.');
                },
                onClose: function() {
                    // User menutup popup tanpa bayar
                }
            });
        };
    </script>
    <?php endif; ?>
</body>
</html>