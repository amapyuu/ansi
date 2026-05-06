<?php
session_start();

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$program = $_GET['program'] ?? '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $program  = $_POST['program'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Semua field wajib diisi.";
    } else {
        // Cek apakah email sudah ada
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email sudah terdaftar. Silakan <a href='login.php'>masuk</a>.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
            $stmt->bind_param("ssss", $name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                // Auto-login setelah daftar
                $_SESSION['user_id']   = $stmt->insert_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = 'user';

                // Jika ada program, arahkan ke booking
                if (!empty($program)) {
                    header("Location: detail/booking.php?program=" . urlencode($program));
                } else {
                    header("Location: index.html");
                }
                exit;
            } else {
                $error = "Gagal mendaftar: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Daftar - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif; background: #f4f7f8;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .register-card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 50px 40px; max-width: 420px; width: 100%;
        }
        .logo { text-align: center; margin-bottom: 28px; }
        .logo img { height: 70px; }
        h2 { font-size: 24px; text-align: center; margin-bottom: 6px; }
        .subtitle { text-align: center; color: #777; font-size: 14px; margin-bottom: 28px; }

        <?php if (!empty($program)): ?>
        .program-notice {
            background: #fff4f9; border: 1px solid #ffc8e0;
            border-radius: 10px; padding: 12px 16px;
            font-size: 13px; color: #cc0066; margin-bottom: 20px; text-align: center;
        }
        <?php endif; ?>

        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #555; }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%; padding: 13px; border: 1px solid #ddd;
            border-radius: 10px; font-family: 'Poppins', sans-serif;
            font-size: 14px; margin-bottom: 18px;
        }
        input:focus { border-color: #008bd8; outline: none; }
        .btn-register {
            width: 100%; padding: 14px; background: #ff3c8a; color: white;
            border: none; border-radius: 50px; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: background 0.3s; margin-bottom: 16px;
        }
        .btn-register:hover { background: #e6006b; }
        .error { background: #ffe5e5; color: #cc0000; border-radius: 10px; padding: 12px; font-size: 13px; margin-bottom: 16px; }
        .login-link { display: block; text-align: center; font-size: 14px; color: #555; }
        .login-link a { color: #ff3c8a; font-weight: 600; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
            <a href="index.html"><img src="assets/Logo.png" alt="Kidsnesia" /></a>
        </div>

        <h2>Buat Akun Baru</h2>
        <p class="subtitle">Daftar untuk mulai menikmati program Kidsnesia</p>

        <?php if (!empty($program)): ?>
        <div class="program-notice">
            Daftar dulu untuk melanjutkan ke program <strong><?= htmlspecialchars($program) ?></strong>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>" />

            <label>Nama Lengkap</label>
            <input type="text" name="name" placeholder="Nama kamu" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required />

            <label>Email</label>
            <input type="email" name="email" placeholder="nama@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />

            <label>No. Telepon</label>
            <input type="text" name="phone" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required />

            <label>Kata Sandi</label>
            <input type="password" name="password" placeholder="Min. 8 karakter" required />

            <button type="submit" class="btn-register">Daftar Sekarang</button>
        </form>

        <p class="login-link">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
    </div>
</body>
</html>