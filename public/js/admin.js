/* JS panel admin. Tanpa skrip inline agar bisa memakai CSP script-src 'self'. */
(function () {
  'use strict';
  var body = document.body;

  // Menu samping di layar kecil
  function menu(buka) { body.classList.toggle('menu-buka', buka); }
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-menu]');
    if (t) { menu(t.getAttribute('data-menu') === 'buka'); return; }
    var tutup = e.target.closest('[data-tutup]');
    if (tutup && tutup.parentNode) { tutup.parentNode.remove(); }
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') menu(false); });

  // Konfirmasi sebelum menghapus (teks dari atribut data, bukan dari string JS)
  document.addEventListener('submit', function (e) {
    var f = e.target, tanya = f.getAttribute('data-konfirmasi');
    if (tanya && !window.confirm(tanya)) { e.preventDefault(); return; }
    // Cegah klik ganda saat mengirim
    var b = f.querySelector('button[type=submit]');
    if (b && !e.defaultPrevented) { setTimeout(function () { b.disabled = true; }, 0); }
  });
  // Tombol "kembali" dari cache browser jangan membiarkan tombol tetap nonaktif
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('button[type=submit]:disabled').forEach(function (b) { b.disabled = false; });
  });

  // Gambar logo yang gagal dimuat disembunyikan (pengganti onerror inline)
  document.addEventListener('error', function (e) {
    if (e.target.tagName === 'IMG' && e.target.hasAttribute('data-sembunyi-galat')) e.target.style.display = 'none';
  }, true);

  // Gambar yang sudah gagal dimuat sebelum skrip ini jalan
  document.querySelectorAll('img[data-sembunyi-galat]').forEach(function (i) {
    if (i.complete && i.naturalWidth === 0) i.style.display = 'none';
  });

  // Notifikasi sukses hilang sendiri
  var ok = document.querySelector('.notif--ok');
  if (ok) { setTimeout(function () { ok.remove(); }, 6000); }

  // Cari di dalam tabel (hanya baris pada halaman ini)
  var cari = document.getElementById('cari-tabel');
  if (cari) {
    var baris = document.querySelectorAll('.tabel tbody tr[data-cari]');
    var ket = document.getElementById('hasil-cari');
    cari.addEventListener('input', function () {
      var q = cari.value.trim().toLowerCase(), n = 0;
      baris.forEach(function (tr) {
        var cocok = q === '' || tr.getAttribute('data-cari').indexOf(q) !== -1;
        tr.hidden = !cocok; if (cocok) n++;
      });
      if (ket) ket.textContent = q === '' ? '' : n + ' cocok';
    });
  }

  // Pratinjau gambar sebelum diunggah
  document.querySelectorAll('input[type=file][accept^="image"]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var file = inp.files && inp.files[0];
      if (!file || file.type.indexOf('image/') !== 0) return;
      var img = inp.parentNode.querySelector('img.pratinjau');
      if (!img) { img = document.createElement('img'); img.className = 'pratinjau'; img.alt = 'Pratinjau'; inp.parentNode.insertBefore(img, inp); }
      img.src = URL.createObjectURL(file);
    });
  });

  // Tampilkan/sembunyikan password
  document.querySelectorAll('[data-lihat-sandi]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var inp = btn.parentNode.querySelector('input');
      var tampil = inp.type === 'password';
      inp.type = tampil ? 'text' : 'password';
      btn.firstElementChild.className = tampil ? 'bi bi-eye-slash' : 'bi bi-eye';
      btn.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });
})();
