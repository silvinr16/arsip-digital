<?php
 $menu = [
    ['Dashboard', '../dashboard/index.php', 'bi-speedometer2'],
    ['Surat Masuk', '../modules/surat_masuk.php', 'bi-inbox'],
    ['Disposisi', '../modules/disposisi.php', 'bi-diagram-3'],
    ['Arsip Digital', '../modules/arsip.php', 'bi-archive'],
    
];
?>

<!-- Sidebar Offcanvas -->
<div class="offcanvas offcanvas-start sidebar-custom text-white shadow-lg" tabindex="-1" id="sidebarMenu">
  <!-- Header Sidebar (Logo dan Judul) -->
  <div class="offcanvas-header">
    <h5 class="offcanvas-title text-center">
      <img src="../assets/img/logo.jpg" alt="Logo" 
      style="width:65px;height:65px;object-fit:cover;" 
      class="rounded-circle shadow-sm">
      <div class="mt-2 fw-bold fs-5"><?= APP_NAME; ?></div>
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body d-flex flex-column p-3">
    <ul class="nav nav-pills flex-column">
      <?php foreach ($menu as $item): 
        $isActive = (strpos($_SERVER['REQUEST_URI'], basename($item[1])) !== false);
      ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center 
              <?= $isActive ? 'active' : 'text-white-hover'; ?>"
             href="<?= $item[1]; ?>">
             <i class="bi <?= $item[2]; ?> me-3"></i>
             <span><?= $item[0]; ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Footer Sidebar -->
    <div class="mt-auto text-center">
      <i class="bi bi-shield-lock me-1"></i>  
      <div class="small text-white-50">
        Login sebagai:
      </div>
      <strong class="text-white"><?= htmlspecialchars($_SESSION['user']['name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></strong>
    </div>
  </div>
</div>

<!-- Konten utama -->
<main class="p-4">

<style>
/* ========== Custom CSS untuk Sidebar Modern ========== */
.sidebar-custom {
  /* Warna latar belakang biru primary standar */
  background: #0d6efd;
  color: white;
  /* Hapus border default dan ganti dengan bayangan */
  border: none;
  border-right: 1px solid rgba(0, 0, 0, 0.1);
}

/* --- Header Sidebar --- */
.sidebar-custom .offcanvas-header {
  background: rgba(0, 0, 0, 0.1); /* Latar sedikit gelap untuk kontras */
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  padding: 1.5rem 1rem;
}

.sidebar-custom .offcanvas-title {
  font-size: 1.25rem;
  font-weight: 700;
  width: 100%; /* Memastikan judul mengambil lebar penuh untuk perataan tengah */
}

.sidebar-custom .offcanvas-title img {
  border: 2px solid rgba(255, 255, 255, 0.5);
}

/* --- Menu Items --- */
.sidebar-custom .nav-link {
  /* Tampilan card-like untuk setiap menu */
  background-color: rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  margin-bottom: 0.5rem;
  padding: 1rem;
  color: rgba(255, 255, 255, 0.85);
  font-weight: 500;
  transition: all 0.3s ease;
  border: 1px solid transparent; /* Untuk transisi hover yang halus */
}

.sidebar-custom .nav-link i {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.7);
  transition: color 0.3s ease;
}

/* Efek Hover */
.sidebar-custom .nav-link:hover {
  background-color: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px); /* Efek mengangkat */
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  color: white;
}

.sidebar-custom .nav-link:hover i {
  color: white;
}

/* Status Aktif */
.sidebar-custom .nav-link.active {
  background: linear-gradient(90deg, #0a58ca, #0b5ed7); /* Gradien untuk menonjol */
  border-color: rgba(255, 255, 255, 0.3);
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 15px rgba(10, 88, 202, 0.4);
  position: relative;
}

/* Tambahkan indikator garis di kiri item aktif */
.sidebar-custom .nav-link.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 70%;
  background-color: white;
  border-radius: 0 4px 4px 0;
}

.sidebar-custom .nav-link.active i {
  color: white;
}

/* --- Footer Sidebar --- */
.sidebar-custom .offcanvas-body > div:last-child {
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background-color: rgba(0, 0, 0, 0.05);
  margin: 0 -1rem 0 -1rem; 
  padding: 1rem;
}

.sidebar-custom .offcanvas-body > div:last-child strong {
  color: white;
  font-weight: 600;
}

/* --- Tombol Close --- */
.sidebar-custom .btn-close {
  filter: brightness(0) invert(1);
  opacity: 0.8;
}
.sidebar-custom .btn-close:hover {
  opacity: 1;
}
</style>