<?php
// Simulated program data (you can also load from database here)
$programs = [
  "3d-printing" => [
    "title" => "3D Printing",
    "name" => "3D PRINTING",
    "description" => [
      "Belajar mencetak objek nyata dari desain digital menggunakan printer 3D.", "Anak-anak akan membuat desain mereka sendiri dan mencetaknya jadi objek nyata."
    ],
    "price" => "Rp 40.000 / Sesi",
    "duration" => "2 Jam",
    "image" => "../assets/3dprint.png",
    "facilities" => [
      "Alat & bahan 3D Printing",
      "Bimbingan mentor",
      "Hasil karya dibawa pulang"
    ]
    ],
  "programmer" => [
    "title" => "Programmer Cilik",
    "name" => "PROGRAMMER CILIK",
    "description" => [
      "Mengajarkan logika pemrograman kepada anak-anak dengan cara yang menyenangkan.", "Gunakan alat visual seperti Scratch dan Blockly."
    ],
    "price" => "Rp 30.000 / Sesi",
    "duration" => "2 Jam",
    "image" => "../assets/programmer.png",
    "facilities" => [
      "Laptop disediakan", "Modul coding visual", "Mentor profesional"
    ]
    ],
  "sablon" => [
    "title" => "Sablon",
    "name" => "SABLON",
    "description" => [
      "Ekspresikan dirimu melalui karya kaos. Tunjukkan kreativitasmu yang mencerminkan dirimu."
    ],
    "price" => "Rp 30.000 / Sesi",
    "duration" => "2 Jam",
    "image" => "../assets/sablon.png",
    "facilities" => [
      "Alat Sablon", "Designer App", "Karya dibawa pulang"
    ]
    ],
  "aci" => [
    "title" => "Aku Cinta Indonesia",
    "name" => "AKU CINTA INDONESIA",
    "description" => [
      "Aku Cinta Indonesia adalah program unggulan kidsnesia yang mana nantinya anak anak akan menggunakan pakaian adat yang udah disediakan dan mereka bakalan melakukan berbagai aktifitas edukasi mengenai budaya. Contohnya bakalan ada cerita menggunakan media interaktif petualangan nusantara, game edukasi budaya, VR Rumah adat, dan bakalan ada photobooth nya juga gitu."
    ],
    "price" => "Rp 10.000 / Sesi",
    "duration" => "2 Jam",
    "image" => "../assets/AACI.png",
    "facilities" => [
      "VR", "Photobooth", "Pakaian Adat"
    ]
    ],
];

$programKey = $_GET['program'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kidsnesia - Detail Program</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="detail.css" />
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

    <section class="program-detail">
      <div class="header-banner">
        <h4>DETAIL PROGRAM</h4>
        <h1></h1>
      </div>

      <div class="program-content">
        <div class="program-image">
          <img src="" alt="" />
        </div>
        <div class="program-description">
          <h2></h2>
          <p></p>
          <p></p>
        </div>
      </div>

      <div class="price-facility">
        <h2>HARGA & FASILITAS</h2>
        <div class="price-cards">
          <div class="card">
            <h3>Harga</h3>
            <p></p>
            <p></p>
          </div>
          <div class="card">
            <h3>Fasilitas</h3>
            <ul></ul>
          </div>
        </div>
      </div>

      <div class="cta-button">
        <a id="btnReservasi" class="btn-reservasi">Reservasi Sekarang</a>
      </div>
    </section>
  </div>

  <script>
    const programData = <?php echo json_encode($programs); ?>;
    const programKey = "<?php echo $programKey; ?>";
    const data = programData[programKey];

    if (data) {
      document.querySelector(".header-banner h1").textContent = data.title;
      document.querySelector(".program-image img").src = data.image;
      document.querySelector(".program-image img").alt = data.title + " Image";
      document.querySelector(".program-description h2").textContent = data.name;

      const descContainer = document.querySelector(".program-description");
      const descParagraphs = descContainer.querySelectorAll("p");
      descParagraphs[0].textContent = data.description[0] || "";
      if (descParagraphs[1]) {
        descParagraphs[1].textContent = data.description[1] || "";
      }

      const priceCard = document.querySelector(".card:nth-child(1)");
      priceCard.querySelector("p:nth-child(2)").textContent = data.price;
      priceCard.querySelector("p:nth-child(3)").textContent = "Durasi: " + data.duration;

      const facilityList = document.querySelector(".card:nth-child(2) ul");
      facilityList.innerHTML = "";
      data.facilities.forEach((fac) => {
        const li = document.createElement("li");
        li.textContent = fac;
        facilityList.appendChild(li);
      });
    } else {
      document.querySelector(".program-detail").innerHTML = "<h2>Program tidak ditemukan.</h2>";
    }
    document.getElementById("btnReservasi").href = "../register.php?program=" + encodeURIComponent(data.name);
  </script>
</body>
</html>
