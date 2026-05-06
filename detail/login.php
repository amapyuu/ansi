<?php
session_start();

// Kalau sudah login, redirect ke beranda
if (isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "kidsnesia_db");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$error = '';
$redirect = $_GET['redirect'] ?? 'index.html';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan kata sandi wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect ke halaman asal atau beranda
            $safeRedirect = filter_var($redirect, FILTER_SANITIZE_URL);
            header("Location: " . $safeRedirect);
            exit;
        } else {
            $error = "Email atau kata sandi salah.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Masuk - Kidsnesia</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif; background: #f4f7f8;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }

        .login-card {
            background: white; border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            padding: 50px 40px; max-width: 420px; width: 100%;
        }

        .logo { text-align: center; margin-bottom: 28px; }
        .logo img { height: 70px; }

        h2 { font-size: 24px; text-align: center; margin-bottom: 6px; }
        .subtitle { text-align: center; color: #777; font-size: 14px; margin-bottom: 28px; }

        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #555; }

        input[type="email"],
        input[type="password"] {
            width: 100%; padding: 13px; border: 1px solid #ddd;
            border-radius: 10px; font-family: 'Poppins', sans-serif;
            font-size: 14px; margin-bottom: 18px; transition: border 0.2s;
        }
        input:focus { border-color: #008bd8; outline: none; }

        .btn-login {
            width: 100%; padding: 14px; background: #ff3c8a;
            color: white; border: none; border-radius: 50px;
            font-size: 16px; font-weight: 700; cursor: pointer;
            transition: background 0.3s; margin-bottom: 16px;
        }
        .btn-login:hover { background: #e6006b; }

        .error { background: #ffe5e5; color: #cc0000; border-radius: 10px; padding: 12px; font-size: 13px; margin-bottom: 16px; }

        .divider { text-align: center; color: #aaa; font-size: 13px; margin-bottom: 16px; }

        .register-link {
            display: block; text-align: center; font-size: 14px; color: #555;
        }
        .register-link a { color: #ff3c8a; font-weight: 600; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <a href="index.html"><img src="assets/Logo.png" alt="Kidsnesia" /></a>
        </div>

        <h2>Selamat Datang!</h2>
        <p class="subtitle">Masuk ke akun Kidsnesia kamu</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>" />

            <label for="email">Email</label>
            <input type="email" name="email" id="email"
                placeholder="nama@email.com"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />

            <label for="password">Kata Sandi</label>
            <input type="password" name="password" id="password"
                placeholder="••••••••" required />

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="divider">— atau —</div>

        <p class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </p>
    </div>
</body>
</html>