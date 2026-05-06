<?php
session_start();
require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Harus login dulu
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$slugMap = [
    '3d-printing' => '3D Printing',
    'programmer'  => 'Programmer Cilik',
    'sablon'      => 'Kreasi Sablon',
    'aci'         => 'Aku Cinta Indonesia',
];

$slug = $_GET['program'] ?? '';
if (!array_key_exists($slug, $slugMap)) {
    header("Location: ../program.html");
    exit;
}

$title = $slugMap[$slug];
$stmt = $conn->prepare("SELECT * FROM programs WHERE title = ? LIMIT 1");
$stmt->bind_param("s", $title);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
$stmt->close();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id'];
    $programId = $program['program_id'];
    $reservationDate = $_POST['reservation_date'] ?? '';

    if (empty($reservationDate)) {
        $error = "Tanggal reservasi wajib diisi.";
    } elseif (strtotime($reservationDate) < strtotime('today')) {
        $error = "Tanggal tidak boleh di masa lalu.";
    } else {
        // Simpan reservasi dengan status pending
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, program_id, reservation_date, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $userId, $programId, $reservationDate);

        if ($stmt->execute()) {
            $reservationId = $stmt->insert_id;

            // Buat record pembayaran dengan status unpaid
            $amount = $program['price'];
            $pStmt = $conn->prepare("INSERT INTO payments (reservation_id, amount, status) VALUES (?, ?, 'unpaid')");
            $pStmt->bind_param("id", $reservationId, $amount);
            $pStmt->execute();
            $pStmt->close();

            // Redirect ke payment dengan reservation_id
            header("Location: ../payment/payment.php?reservation_id=" . $reservationId);
            exit;
        } else {
            $error = "Gagal membuat reservasi: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Booking <?= htmlspecialchars($program['title']) ?> - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f4f7f8; padding-top: 90px; color: #1c1c1c; }

        .navbar {
            position: fixed; top: 0; left: 0; width: 100%;
            background: white; z-index: 999;
            border-bottom: 1px solid #dcdcdc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .navbar-content { display: flex; justify-content: space-between; align-items: center; padding: 5px 10%; }
        .logo img { height: 80px; }
        .nav-links { display: flex; gap: 70px; align-items: center; }
        .nav-links a { text-decoration: none; color: #1c1c1c; font-weight: 500; font-size: 15px; }
        .nav-links a:hover { color: #0089d4; }

        .booking-container {
            max-width: 500px; margin: 40px auto; padding: 0 20px;
        }
        .booking-card {
            background: white; border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 40px;
        }
        .booking-card h2 { font-size: 24px; margin-bottom: 6px; }
        .booking-card h2 span { color: #ff3c8a; }
        .program-info {
            background: #fff4f9; border-radius: 10px; padding: 16px;
            margin: 20px 0; font-size: 14px;
        }
        .program-info .program-title { font-weight: 700; font-size: 16px; color: #ff3c8a; }
        .program-info .program-price { color: #555; margin-top: 4px; }

        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        input[type="date"] {
            width: 100%; padding: 12px; border: 1px solid #ccc;
            border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-submit {
            width: 100%; padding: 14px; background: #ff3c8a;
            color: white; border: none; border-radius: 50px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: background 0.3s;
        }
        .btn-submit:hover { background: #e6006b; }

        .error { background: #ffe5e5; color: #cc0000; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #008bd8; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <a href="../index.html"><img src="../assets/Logo.png" alt="Logo Kidsnesia" /></a>
            </div>
            <nav class="nav-links">
                <a href="../index.html">Beranda</a>
                <a href="../program.html">Program</a>
                <a href="../hubungi.html">Hubungi</a>
            </nav>
        </div>
    </header>

    <div class="booking-container">
        <a href="detail.php?program=<?= urlencode($slug) ?>" class="back-link">← Kembali ke Detail Program</a>

        <div class="booking-card">
            <h2>Booking <span><?= htmlspecialchars($program['title']) ?></span></h2>
            <p style="color:#777; font-size:14px; margin-bottom:10px;">Pilih tanggal kegiatan yang kamu inginkan</p>

            <div class="program-info">
                <div class="program-title"><?= htmlspecialchars($program['title']) ?></div>
                <div class="program-price">Rp <?= number_format($program['price'], 0, ',', '.') ?> / peserta</div>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="reservation_date">Tanggal Kegiatan</label>
                <input type="date" name="reservation_date" id="reservation_date"
                    min="<?= date('Y-m-d') ?>" required />
                <button type="submit" class="btn-submit">Lanjut ke Pembayaran</button>
            </form>
        </div>
    </div>
</body>
</html>