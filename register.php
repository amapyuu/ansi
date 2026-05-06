<?php
// Get the program key from the URL (should be something like "3d-printing")
$program = $_GET['program'] ?? 'Tidak Diketahui';
$name = $_GET['name'] ?? 'Peserta';

// Proses saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $program = $_POST['program']; // This should be the program key, not the title
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $conn = new mysqli("localhost", "root", "", "reservation");

    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }

    $sql = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $phone, $password);

   if ($stmt->execute()) {
    // Make sure we're passing the program key, not the title
    header("Location: payment/payment.php?program=" . urlencode($program) . "&name=" . urlencode($name));
    exit;
   } else {
    $error = "Gagal mendaftar: " . $stmt->error;
   }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Formulir Pendaftaran</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f7f8;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .form-container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #45a049;
        }

        .message {
            text-align: center;
            margin-bottom: 15px;
            color: green;
        }

        .error {
            text-align: center;
            margin-bottom: 15px;
            color: red;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Daftar Akun Baru</h2>

        <?php if (!empty($success)) echo "<div class='message'>$success</div>"; ?>
        <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

        <form method="POST" action="">
            <label>Nama Lengkap</label>
            <input type="text" name="name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>No. Telepon</label>
            <input type="text" name="phone" required>

            <label>Kata Sandi</label>
            <input type="password" name="password" required>

            <input type="hidden" name="program" value="<?= htmlspecialchars($program) ?>">
            <button type="submit">Daftar</button>
        </form>
    </div>
</body>
<script>
const urlParams = new URLSearchParams(window.location.search);
const program = urlParams.get("program");
const name = urlParams.get("name");

document.querySelector(".program-name").textContent = program;
document.querySelector(".user-name").textContent = name;</script>
</html>
