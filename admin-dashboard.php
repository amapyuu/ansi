<?php
session_start();

// Jika belum login atau bukan admin, redirect
if (!isset($_SESSION['user_id'])) {
    header("Location: detail/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: user-dashboard.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$admin = $_SESSION['user_name'] ?? 'Admin';

// Ambil statistik
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Total reservasi
$result = $conn->query("SELECT COUNT(*) as total FROM reservations");
$stats['total_bookings'] = $result->fetch_assoc()['total'];

// Total pembayaran sukses
$result = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'paid'");
$stats['paid_payments'] = $result->fetch_assoc()['total'];

// Total revenue
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'paid'");
$row = $result->fetch_assoc();
$stats['revenue'] = $row['total'] ?? 0;

// Ambil daftar pembayaran pending
$pending_payments = [];
$stmt = $conn->prepare("
    SELECT py.*, r.*, p.title AS program_title, u.name, u.email
    FROM payments py
    JOIN reservations r ON py.reservation_id = r.reservation_id
    JOIN programs p ON r.program_id = p.program_id
    JOIN users u ON r.user_id = u.user_id
    WHERE py.status = 'unpaid' OR py.status = 'pending'
    ORDER BY r.reservation_date DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pending_payments[] = $row;
}
$stmt->close();

// Ambil recent reservasi
$recent_bookings = [];
$stmt = $conn->prepare("
    SELECT r.*, p.title AS program_title, u.name, u.email
    FROM reservations r
    JOIN programs p ON r.program_id = p.program_id
    JOIN users u ON r.user_id = u.user_id
    ORDER BY r.reservation_date DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_bookings[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Kidsnesia</title>
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
            --yellow: #F59E0B;
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

        .admin-badge {
            display: inline-block;
            background: var(--red);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
            margin-left: 8px;
        }

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
            background: var(--red);
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        .stat-number {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 4px;
            font-family: 'Nunito', sans-serif;
        }
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        .section-title {
            font-family: 'Nunito', sans-serif;
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 20px;
            margin-top: 40px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-table {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: var(--bg);
            border-bottom: 2px solid var(--border);
        }
        th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
        }
        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        tbody tr:hover {
            background: var(--bg);
        }
        tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-paid {
            background: #D1F4D1;
            color: #106B10;
        }
        .badge-unpaid {
            background: #FFE5E5;
            color: #CC0000;
        }
        .badge-pending {
            background: #FFF3CD;
            color: #856404;
        }
        .badge-confirmed {
            background: #D1EDFF;
            color: #0066CC;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state .icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
        }

        footer {
            background: var(--text);
            color: var(--white);
            text-align: center;
            padding: 24px;
            margin-top: 60px;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 12px 8px;
            }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-content">
        <div class="logo">Kids<span>nesia</span><span class="admin-badge">ADMIN</span></div>
        <div class="nav-right">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($admin) ?></div>
                    <div style="font-size: 11px; color: var(--text-muted);">Administrator</div>
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
        <h1>Dashboard Admin 📊</h1>
        <p>Kelola bisnis Kidsnesia dari sini</p>
    </div>

    <!-- STATS -->
    <div class="dashboard-grid">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-number"><?= $stats['total_users'] ?></div>
            <div class="stat-label">Total Pengguna</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-number"><?= $stats['total_bookings'] ?></div>
            <div class="stat-label">Total Reservasi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-number"><?= $stats['paid_payments'] ?></div>
            <div class="stat-label">Pembayaran Sukses</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-number">Rp <?= number_format($stats['revenue'], 0, ',', '.') ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>

    <!-- PENDING PAYMENTS -->
    <div class="section-title">⏳ Pembayaran Menunggu Konfirmasi</div>
    <?php if (count($pending_payments) > 0): ?>
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Program</th>
                    <th>Email</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_payments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['name']) ?></td>
                    <td><?= htmlspecialchars($payment['program_title']) ?></td>
                    <td><?= htmlspecialchars($payment['email']) ?></td>
                    <td>Rp <?= number_format($payment['amount'], 0, ',', '.') ?></td>
                    <td><span class="badge badge-<?= $payment['status'] ?>"><?= ucfirst($payment['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">✨</div>
        <p>Tidak ada pembayaran yang menunggu</p>
    </div>
    <?php endif; ?>

    <!-- RECENT BOOKINGS -->
    <div class="section-title">📋 Reservasi Terbaru</div>
    <?php if (count($recent_bookings) > 0): ?>
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Program</th>
                    <th>Tanggal Kegiatan</th>
                    <th>Email</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_bookings as $booking): ?>
                <tr>
                    <td><?= htmlspecialchars($booking['name']) ?></td>
                    <td><?= htmlspecialchars($booking['program_title']) ?></td>
                    <td><?= date('d M Y', strtotime($booking['reservation_date'])) ?></td>
                    <td><?= htmlspecialchars($booking['email']) ?></td>
                    <td><span class="badge badge-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="icon">📭</div>
        <p>Belum ada reservasi</p>
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