const filterButtons = document.querySelectorAll(".filters button");
const filterableCards = document.querySelectorAll(".cards .card");

const filterCards = (e) => {
  const target = e.target;
  const filterName = target.getAttribute("data-name");

  // Update tombol yang aktif
  filterButtons.forEach((btn) => btn.classList.remove("active"));
  target.classList.add("active");

  // Filter kartu
  filterableCards.forEach((card) => {
    const program = card.querySelector("a").href.split("program=")[1];

    if (filterName === "all" || filterName === program) {
      card.style.display = "block";
    } else {
      card.style.display = "none";
    }
  });
};

// Tambahkan event listener ke semua tombol filter
filterButtons.forEach((button) => button.addEventListener("click", filterCards));
