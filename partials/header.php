<?php require_once __DIR__ . '/../config/app.php'; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    /* Sidebar */
    #sidebarMenu {
      width: 230px;
    }
    #sidebarMenu .nav-link {
      display: flex;
      align-items: center;
      font-size: 0.95rem;
      padding: 0.55rem 0.75rem;
      border-radius: .375rem;
      transition: all 0.2s ease;
    }
    #sidebarMenu .nav-link i {
      font-size: 1.1rem;
      margin-right: .65rem;
    }
    #sidebarMenu .nav-link.active {
      font-weight: 600;
      background: rgba(255,255,255,0.2);
    }

    /* Navbar */
    .navbar-custom {
      /* PERUBAHAN: Gradasi warna disesuaikan dengan sidebar untuk keserasian */
      background: linear-gradient(90deg, #0d6efd, #0a58ca);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      padding-top: 0.75rem;
      padding-bottom: 0.75rem;
    }
    
    /* Wrapper untuk logo dan teks brand */
    .navbar-brand-wrapper {
      display: flex;
      align-items: center;
    }

    /* Logo Statis */
    .navbar-logo {
      height: 45px; 
      width: 45px;
      object-fit: cover;
      border-radius: 50%;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-right: 12px;
    }
    
    /* Teks brand juga statis */
    .navbar-brand-text {
      font-weight: 700;
      letter-spacing: 0.5px;
      font-size: 1.8rem;
      color: white;
    }
    
    .navbar-toggler-custom {
      border: none;
      background: rgba(255,255,255,0.2);
      border-radius: 6px;
      transition: all 0.2s;
    }
    .navbar-toggler-custom:hover {
      background: rgba(255,255,255,0.35);
    }
    
    /* Styling tombol logout dengan hover senada header */
    .btn-logout-custom {
      background-color: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.4);
      color: white;
      padding: 0.4rem 1rem;
      font-size: 0.9rem;
      font-weight: 500;
      border-radius: 0.375rem;
      transition: all 0.2s ease-in-out;
    }
    .btn-logout-custom:hover {
      background-color: rgba(255, 255, 255, 0.25);
      border-color: white;
      color: white;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-light">

<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-3">
  <!-- Sidebar Toggle -->
  <button class="btn navbar-toggler-custom me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
    <i class="bi bi-list text-white fs-5"></i>
  </button>

  <!-- Logo dan Nama Keduanya Statis -->
  <div class="navbar-brand-wrapper">
    <!-- Logo Statis -->
    <img src="../assets/img/logo.jpg" alt="Logo Instansi" class="navbar-logo">
    <!-- Nama Brand Statis -->
    <span class="navbar-brand-text">
      <?= APP_NAME; ?>
    </span>
  </div>

  <!-- Tombol Logout -->
  <div class="ms-auto">
    <form action="../auth/logout.php" method="POST">
      <button type="submit" class="btn btn-logout-custom">
        <i class="bi bi-box-arrow-right me-2"></i>Logout
      </button>
    </form>
  </div>
</nav>