<?php
session_start();

// Jika belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: detail/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

// Jika admin, redirect ke admin dashboard
if ($userRole === 'admin') {
    header("Location: admin-dashboard.php");
    exit;
}

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ambil history booking user
$bookings = [];
$stmt = $conn->prepare("
    SELECT r.*, p.title AS program_title, p.price, 
           py.payment_id, py.status AS payment_status, py.amount
    FROM reservations r
    JOIN programs p ON r.program_id = p.program_id
    LEFT JOIN payments py ON py.reservation_id = r.reservation_id
    WHERE r.user_id = ?
    ORDER BY r.reservation_date DESC
");
if (!$stmt) {
    die("Prepare gagal: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard User - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --blue: #008BD8;
            --pink: #FF3C8A;
            --text: #1C1C1C;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --bg: #F9FAFB;
            --white: #FFFFFF;
            --green: #10B981;
            --red: #EF4444;
            --radius: 12px;
            --shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text);
            background: var(--bg);
            overflow-x: hidden;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: var(--white);
            z-index: 999;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }
        .navbar-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 8%;
            height: 72px;
        }
        .logo {
            font-family: 'Nunito', sans-serif;
            font-size: 22px;
            font-weight: 900;
            color: var(--blue);
        }
        .logo span { color: var(--pink); }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        .logout-btn {
            background: var(--red);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .logout-btn:hover { background: #DC2626; }

        .dashboard-container {
            padding-top: 100px;
            padding: 100px 8% 40px;
        }

        .page-header {
            margin-bottom: 40px;
        }
        .page-header h1 {
            font-family: 'Nunito', sans-serif;
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 8px;
        }
        .page-header p {
            color: var(--text-muted);
            font-size: 15px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: var(--shadow);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            flex-shrink: 0;
        }
        .stat-icon.blue { background: #EFF6FF; }
        .stat-icon.pink { background: #FFF0F6; }
        .stat-icon.green { background: #ECFDF5; }

        .stat-content h3 {
            font-size: 24px;
            font-weight: 900;
            margin-bottom: 4px;
        }
        .stat-content p {
            font-size: 13px;
            color: var(--text-muted);
        }

        .section-title {
            font-family: 'Nunito', sans-serif;
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bookings-list {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .booking-item {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: start;
        }
        .booking-item:last-child { border-bottom: none; }

        .booking-info h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }
        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .detail-label {
            font-weight: 600;
            color: var(--text);
            font-size: 11px;
            text-transform: uppercase;
        }
        .detail-value {
            font-size: 14px;
        }

        .status-badges {
            display: flex;
            gap: 8px;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-confirmed {
            background: #D1F4D1;
            color: #106B10;
        }
        .badge-pending {
            background: #FFF3CD;
            color: #856404;
        }
        .badge-paid {
            background: #D1EDFF;
            color: #0066CC;
        }
        .badge-unpaid {
            background: #FFE5E5;
            color: #CC0000;
        }

        .booking-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-action {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-pay {
            background: var(--pink);
            color: var(--white);
        }
        .btn-pay:hover { background: #E6006B; }
        .btn-detail {
            background: var(--blue);
            color: var(--white);
        }
        .btn-detail:hover { background: #0070B0; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--radius);
            border: 1px dashed var(--border);
        }
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-primary {
            display: inline-block;
            background: var(--pink);
            color: var(--white);
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #E6006B; }

        footer {
            background: var(--text);
            color: var(--white);
            text-align: center;
            padding: 24px;
            margin-top: 60px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-content">
        <div class="logo">Kids<span>nesia</span></div>
        <div class="nav-right">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div style="font-size: 11px; color: var(--text-muted);">Pengguna</div>
                </div>
            </div>
            <form method="POST" style="display: inline;">
                <button name="logout" class="logout-btn">Logout</button>
            </form>
        </div>
    </div>
</nav>

<!-- DASHBOARD -->
<div class="dashboard-container">
    <div class="page-header">
        <h1>Halo, <?= htmlspecialchars($user['name']) ?>! 👋</h1>
        <p>Kelola reservasi dan pembayaran programmu di Kidsnesia</p>
    </div>

    <!-- STATS -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📅</div>
            <div class="stat-content">
                <h3><?= count($bookings) ?></h3>
                <p>Total Reservasi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pink">💳</div>
            <div class="stat-content">
                <h3><?= count(array_filter($bookings, fn($b) => $b['payment_status'] === 'paid')) ?></h3>
                <p>Pembayaran Sukses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">⏳</div>
            <div class="stat-content">
                <h3><?= count(array_filter($bookings, fn($b) => $b['payment_status'] === 'pending' || $b['payment_status'] === 'unpaid')) ?></h3>
                <p>Menunggu Pembayaran</p>
            </div>
        </div>
    </div>

    <!-- RESERVASI -->
    <div class="section-title">📋 Riwayat Reservasi Anda</div>
    
    <?php if (count($bookings) > 0): ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-item">
                <div class="booking-info">
                    <h4><?= htmlspecialchars($booking['program_title']) ?></h4>
                    <div class="booking-details">
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Kegiatan</span>
                            <span class="detail-value"><?= date('d M Y', strtotime($booking['reservation_date'])) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Harga</span>
                            <span class="detail-value">Rp <?= number_format($booking['price'], 0, ',', '.') ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Total Dibayar</span>
                            <span class="detail-value">Rp <?= number_format($booking['amount'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="status-badges">
                        <span class="badge badge-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
                        <span class="badge badge-<?= $booking['payment_status'] ?>"><?= ucfirst($booking['payment_status']) ?></span>
                    </div>
                </div>
                <div class="booking-actions">
                    <?php if ($booking['payment_status'] === 'unpaid' || $booking['payment_status'] === 'pending'): ?>
                        <a href="detail/payment.php?reservation_id=<?= $booking['reservation_id'] ?>" class="btn-action btn-pay">Bayar Sekarang</a>
                    <?php endif; ?>
                    <a href="detail/detail.php?program=<?= urlencode($booking['program_title']) ?>" class="btn-action btn-detail">Lihat Detail</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="icon">🎯</div>
            <h3>Belum Ada Reservasi</h3>
            <p>Mulai petualanganmu dengan memilih program Kidsnesia favorit</p>
            <a href="program.html" class="btn-primary">Lihat Program</a>
        </div>
    <?php endif; ?>
</div>

<footer>
    © 2025 Kidsnesia - Wisata Edukasi Terbaik untuk Anak Indonesia
</footer>

<?php
// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.html");
    exit;
}
?>

</body>
</html>