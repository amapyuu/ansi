document.addEventListener('DOMContentLoaded', () => {
  // Check user session dan update navbar
  fetch('check-session.php')
    .then(res => res.json())
    .then(data => {
      const userLink = document.getElementById('user-link');
      if (userLink) {
        if (data.logged_in) {
          userLink.href = data.user_role === 'admin' ? '/admin-dashboard.php' : '/user-dashboard.php';
          userLink.title = `${data.user_name} (${data.user_role})`;
        } else {
          userLink.href = 'detail/login.php';
          userLink.title = 'Login';
        }
      }
    })
    .catch(err => console.log('Session check error:', err));

  // Filter programs on program.html
  const filterButtons = document.querySelectorAll('.filters button');
  const filterableCards = document.querySelectorAll('.cards .card');

  if (!filterButtons.length || !filterableCards.length) return; // nothing to do on this page

  const filterCards = (e) => {
    const btn = e.currentTarget || e.target;
    const filterName = btn.getAttribute('data-name');

    // Update tombol yang aktif
    filterButtons.forEach((b) => b.classList.remove('active'));
    btn.classList.add('active');

    // Filter kartu
    filterableCards.forEach((card) => {
      const link = card.querySelector('a');
      let program = null;
      if (link && link.href) {
        const parts = link.href.split('program=');
        program = parts.length > 1 ? parts[1].split('&')[0] : null;
      }

      if (filterName === 'all' || filterName === program) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  };

  // Tambahkan event listener ke semua tombol filter
  filterButtons.forEach((button) => button.addEventListener('click', filterCards));
});
