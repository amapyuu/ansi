<?php
session_start();

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../detail/login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$message = '';
$action = $_GET['action'] ?? '';
$reservation_id = $_GET['id'] ?? 0;

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $res_id = $_POST['reservation_id'] ?? 0;
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if ($action === 'approve') {
        $status = 'approved';
        $stmt = $conn->prepare("UPDATE reservations SET status = ?, admin_notes = ? WHERE reservation_id = ?");
        $stmt->bind_param("ssi", $status, $admin_notes, $res_id);
        $stmt->execute();
        $stmt->close();
        
        // Get user email untuk kirim notifikasi
        $stmt = $conn->prepare("
            SELECT u.email, u.name, p.title, r.reservation_date
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            JOIN programs p ON r.program_id = p.program_id
            WHERE r.reservation_id = ?
        ");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_assoc();
        $stmt->close();
        
        if ($res) {
            sendApprovalEmail($res['email'], $res['name'], $res['title'], $res['reservation_date']);
        }
        
        $message = "✓ Reservasi disetujui dan email notifikasi sudah dikirim!";
    } 
    elseif ($action === 'reject') {
        $status = 'rejected';
        $stmt = $conn->prepare("UPDATE reservations SET status = ?, admin_notes = ? WHERE reservation_id = ?");
        $stmt->bind_param("ssi", $status, $admin_notes, $res_id);
        $stmt->execute();
        $stmt->close();
        
        // Get user email untuk kirim notifikasi rejection
        $stmt = $conn->prepare("
            SELECT u.email, u.name, p.title, r.reservation_date
            FROM reservations r
            JOIN users u ON r.user_id = u.user_id
            JOIN programs p ON r.program_id = p.program_id
            WHERE r.reservation_id = ?
        ");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $res = $result->fetch_assoc();
        $stmt->close();
        
        if ($res) {
            sendRejectionEmail($res['email'], $res['name'], $res['title'], $admin_notes);
        }
        
        $message = "✓ Reservasi ditolak dan email notifikasi sudah dikirim!";
    }
}

// Ambil semua reservasi dengan status pending
$reservations = [];
$stmt = $conn->prepare("
    SELECT r.*, p.title AS program_title, p.price, u.name, u.email, u.phone
    FROM reservations r
    JOIN programs p ON r.program_id = p.program_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status = 'pending'
    ORDER BY r.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reservations[] = $row;
}
$stmt->close();

// Ambil reservasi yang sudah di-process
$processed = [];
$stmt = $conn->prepare("
    SELECT r.*, p.title AS program_title, p.price, u.name, u.email, u.phone
    FROM reservations r
    JOIN programs p ON r.program_id = p.program_id
    JOIN users u ON r.user_id = u.user_id
    WHERE r.status IN ('approved', 'rejected')
    ORDER BY r.created_at DESC
    LIMIT 20
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $processed[] = $row;
}
$stmt->close();

$conn->close();

// Email functions
function sendApprovalEmail($email, $name, $program, $date) {
    $to = $email;
    $subject = "Reservasi Anda Disetujui - Kidsnesia";
    $message = "Halo $name,\n\n";
    $message .= "Selamat! Reservasi Anda untuk program $program pada tanggal $date telah DISETUJUI.\n\n";
    $message .= "Anda sekarang dapat melakukan pembayaran untuk mengkonfirmasi kehadiran.\n";
    $message .= "Silakan login ke dashboard untuk melanjutkan pembayaran.\n\n";
    $message .= "Terima kasih,\nTim Kidsnesia";
    
    $headers = "From: admin@kidsnesia.id\r\n";
    $headers .= "Reply-To: admin@kidsnesia.id\r\n";
    
    mail($to, $subject, $message, $headers);
}

function sendRejectionEmail($email, $name, $program, $reason) {
    $to = $email;
    $subject = "Update Reservasi - Kidsnesia";
    $message = "Halo $name,\n\n";
    $message .= "Kami informasikan bahwa reservasi Anda untuk program $program tidak dapat kami proses.\n\n";
    $message .= "Alasan: $reason\n\n";
    $message .= "Silakan hubungi tim kami untuk informasi lebih lanjut atau coba reservasi program lain.\n";
    $message .= "Terima kasih,\nTim Kidsnesia";
    
    $headers = "From: admin@kidsnesia.id\r\n";
    $headers .= "Reply-To: admin@kidsnesia.id\r\n";
    
    mail($to, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manajemen Reservasi</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .header {
            background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex;
            justify-content: space-between; align-items: center;
        }
        
        .header h1 { color: #1c1c1c; font-size: 24px; }
        
        .logout-btn {
            background: #ff3c8a; color: white; border: none;
            padding: 10px 20px; border-radius: 6px; cursor: pointer;
            font-size: 14px; font-weight: 600;
        }
        
        .logout-btn:hover { background: #e61f6c; }
        
        .message {
            background: #d4edda; color: #155724; padding: 15px;
            border-radius: 6px; margin-bottom: 20px; border: 1px solid #c3e6cb;
        }
        
        .section {
            background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px;
        }
        
        .section h2 { 
            color: #1c1c1c; font-size: 18px; margin-bottom: 15px;
            border-bottom: 2px solid #ff3c8a; padding-bottom: 10px;
        }
        
        .reservation-card {
            border: 1px solid #e0e0e0; padding: 15px; margin-bottom: 15px;
            border-radius: 6px; background: #fafafa;
        }
        
        .reservation-card h3 { color: #ff3c8a; margin-bottom: 8px; }
        
        .info-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 10px; margin: 10px 0;
        }
        
        .info-item { font-size: 14px; }
        .info-item strong { color: #555; }
        
        .action-buttons {
            display: flex; gap: 10px; margin-top: 15px;
        }
        
        .btn {
            padding: 8px 16px; border: none; border-radius: 6px;
            cursor: pointer; font-size: 13px; font-weight: 600;
        }
        
        .btn-approve {
            background: #4CAF50; color: white;
        }
        
        .btn-approve:hover { background: #45a049; }
        
        .btn-reject {
            background: #f44336; color: white;
        }
        
        .btn-reject:hover { background: #da190b; }
        
        .modal {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
            z-index: 999; align-items: center; justify-content: center;
        }
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white; padding: 30px; border-radius: 8px;
            width: 90%; max-width: 500px;
        }
        
        .modal-content h3 { margin-bottom: 15px; color: #1c1c1c; }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block; margin-bottom: 5px; font-weight: 600;
            color: #555; font-size: 14px;
        }
        
        .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid #ddd;
            border-radius: 4px; font-family: 'Poppins', sans-serif;
            min-height: 80px;
        }
        
        .modal-buttons {
            display: flex; gap: 10px; justify-content: flex-end;
        }
        
        .modal-buttons button {
            padding: 10px 20px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 600;
        }
        
        .modal-buttons .btn-cancel {
            background: #e0e0e0; color: #333;
        }
        
        .status-badge {
            display: inline-block; padding: 4px 8px; border-radius: 4px;
            font-size: 12px; font-weight: 600;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        
        .empty-state {
            text-align: center; padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Manajemen Reservasi Admin</h1>
            <a href="../detail/logout.php" class="logout-btn">Logout</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <!-- Pending Reservations -->
        <div class="section">
            <h2>⏳ Reservasi Menunggu Persetujuan (<?= count($reservations) ?>)</h2>
            
            <?php if (empty($reservations)): ?>
                <div class="empty-state">
                    <p>Tidak ada reservasi yang menunggu persetujuan</p>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $res): ?>
                    <div class="reservation-card">
                        <h3><?= htmlspecialchars($res['program_title']) ?></h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <strong>Nama:</strong> <?= htmlspecialchars($res['name']) ?>
                            </div>
                            <div class="info-item">
                                <strong>Email:</strong> <?= htmlspecialchars($res['email']) ?>
                            </div>
                            <div class="info-item">
                                <strong>Telepon:</strong> <?= htmlspecialchars($res['phone']) ?>
                            </div>
                            <div class="info-item">
                                <strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($res['reservation_date'])) ?>
                            </div>
                            <div class="info-item">
                                <strong>Harga:</strong> Rp <?= number_format($res['price'], 0, ',', '.') ?>
                            </div>
                            <div class="info-item">
                                <strong>Status:</strong> <span class="status-badge status-pending">Pending</span>
                            </div>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-approve" onclick="openApproveModal(<?= $res['reservation_id'] ?>, '<?= htmlspecialchars($res['name']) ?>')">
                                ✓ Setujui
                            </button>
                            <button class="btn btn-reject" onclick="openRejectModal(<?= $res['reservation_id'] ?>, '<?= htmlspecialchars($res['name']) ?>')">
                                ✗ Tolak
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Processed Reservations -->
        <div class="section">
            <h2>✓ Riwayat Reservasi (<?= count($processed) ?>)</h2>
            
            <?php if (empty($processed)): ?>
                <div class="empty-state">
                    <p>Belum ada riwayat reservasi</p>
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                            <th style="padding: 10px; text-align: left;">Program</th>
                            <th style="padding: 10px; text-align: left;">Nama</th>
                            <th style="padding: 10px; text-align: left;">Tanggal</th>
                            <th style="padding: 10px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($processed as $res): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;"><?= htmlspecialchars($res['program_title']) ?></td>
                                <td style="padding: 10px;"><?= htmlspecialchars($res['name']) ?></td>
                                <td style="padding: 10px;"><?= date('d/m/Y', strtotime($res['reservation_date'])) ?></td>
                                <td style="padding: 10px;">
                                    <span class="status-badge status-<?= strtolower($res['status']) ?>">
                                        <?= ucfirst($res['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <h3 id="approveName"></h3>
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" id="approveResId" name="reservation_id">
                
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="admin_notes" placeholder="Tambahkan catatan untuk pengguna..."></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModals()">Batal</button>
                    <button type="submit" class="btn btn-approve">Setujui Reservasi</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <h3 id="rejectName"></h3>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" id="rejectResId" name="reservation_id">
                
                <div class="form-group">
                    <label>Alasan Penolakan *</label>
                    <textarea name="admin_notes" placeholder="Jelaskan alasan penolakan..." required></textarea>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModals()">Batal</button>
                    <button type="submit" class="btn btn-reject">Tolak Reservasi</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openApproveModal(resId, name) {
            document.getElementById('approveResId').value = resId;
            document.getElementById('approveName').textContent = `Setujui Reservasi ${name}?`;
            document.getElementById('approveModal').classList.add('active');
        }
        
        function openRejectModal(resId, name) {
            document.getElementById('rejectResId').value = resId;
            document.getElementById('rejectName').textContent = `Tolak Reservasi ${name}?`;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function closeModals() {
            document.getElementById('approveModal').classList.remove('active');
            document.getElementById('rejectModal').classList.remove('active');
        }
    </script>
</body>
</html>
