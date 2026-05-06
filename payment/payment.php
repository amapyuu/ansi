<?php
$programs = [
  "3d-printing" => [
    "title" => "3D PRINTING",
    "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit...",
    "price" => 40000,
    "image" => "../assets/3dprint.png"
  ],
  "programmer" => [
    "title" => "Programmer Cilik",
    "description" => "Mengajarkan logika pemrograman kepada anak-anak dengan cara yang menyenangkan. Gunakan alat visual seperti Scratch dan Blockly.",
    "price" => 30000,
    "image" => "../assets/programmer.png"
  ],
  "sablon" => [
    "title" => "sablon",
    "description" => "Ekspresikan dirimu melalui karya kaos. Tunjukkan kreativitasmu yang mencerminkan dirimu.",
    "price" => 30000,
    "image" => "../assets/sablon.png"
  ],
  "aci" => [
    "title" => "Aku Cinta Indonesia",
    "description" => "Aku Cinta Indonesia adalah program unggulan kidsnesia yang mana nantinya anak anak akan menggunakan pakaian adat yang udah disediakan dan mereka bakalan melakukan berbagai aktifitas edukasi mengenai budaya. Contohnya bakalan ada cerita menggunakan media interaktif petualangan nusantara, game edukasi budaya, VR Rumah adat, dan bakalan ada photobooth nya juga gitu",
    "price" => 10000,
    "image" => "../assets/AACI.png"
  ]
];

// Ambil parameter dari URL
$requestedProgram = $_GET['program'] ?? '';

// Cari program berdasarkan judul (case-insensitive)
$selectedProgram = null;
$selectedProgramKey = '';

foreach ($programs as $key => $program) {
  if (strtoupper($program['title']) === strtoupper($requestedProgram)) {
    $selectedProgram = $program;
    $selectedProgramKey = $key;
    break;
  }
}

// Tampilkan pesan jika program tidak ditemukan
if (!$selectedProgram) {
  echo "<h2>Program tidak ditemukan: " . htmlspecialchars($requestedProgram) . "</h2>";
  exit;
}
?>


<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kidsnesia - Detail Program</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="payment.css" />
  </head>
  <body>
    <div class="container">
      <header class="navbar">
        <div class="navbar-content">
          <div class="logo">
            <img src="./Logo.png" alt="Logo Kidsnesia" />
          </div>
          <nav class="nav-links">
            <a href="/index.html">Beranda</a>
            <a href="/program.html">Program</a>
            <a href="/hubungi.html">Hubungi</a>
            <a href="#"><i class="fas fa-user-circle"></i></a>
          </nav>
        </div>
      </header>
    </div>

    <main class="payment-section">
      <div class="payment-container">
        <!-- Kiri: Detail Program -->
        <div class="program-details">
          <img src="<?= $selectedProgram['image'] ?>" alt="<?= $selectedProgram['title'] ?>" class="program-img" />
          <div class="program-info">
            <h2><?= $selectedProgram['title'] ?></h2>
            <p><?= $selectedProgram['description'] ?></p>
            <p class="price">
              Rp.
              <?= number_format($selectedProgram['price'], 0, ',', '.') ?>
            </p>
          </div>
        </div>

        <!-- Kanan: Harga & Total -->
        <div class="payment-summary">
          <div class="summary-info">
            <div class="row">
              <span>Harga</span>
              <span
                >: Rp. <span id="harga"><?= number_format($selectedProgram['price'], 0, ',', '.') ?></span></span
              >
            </div>
            <hr />
            <div class="row">
              <span>Total</span>
              <span>: Rp. <span id="total"></span></span>
            </div>
          </div>
          <div class="buy-btn">
            <button id="btn-beli" class="btn-buy">Beli</button>
            <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-PUhqfd0YUGnXewMp"></script>
          </div>
        </div>
      </div>
    </main>
  </body>

  <script>
    
    // Dapatkan data dari PHP ke JS
    const program = {
      name: "<?= $selectedProgram['title'] ?>",
      price: "<?= $selectedProgram['price'] ?>",
      user_id: localStorage.getItem("user_id") || 1,
    };

    // Tampilkan harga total
    document.getElementById("total").textContent = program.price.toLocaleString("id-ID");

    // Event saat tombol dibeli
    document.getElementById("btn-beli").addEventListener("click", async function () {
  const response = await fetch("get_snap_token.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(program)
  });

  const result = await response.json();

  if (result.token) {
    snap.pay(result.token); // Tampilkan UI pembayaran
  } else {
    alert("Gagal mendapatkan token pembayaran: " + result.error);
  }

      // Contoh simpan ke localStorage
      localStorage.setItem("programTerpilih", JSON.stringify(program));

      // Redirect (kalau perlu ke halaman checkout atau register)
      // window.location.href = "register.php";
    });
  </script>
</html>
