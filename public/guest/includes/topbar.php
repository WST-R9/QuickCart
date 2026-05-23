<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>

<!-- ======= Header/Topbar ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index" class="logo d-flex align-items-center">
      <img src="../user/assets/img/qc-logo.png" alt="QuickCart Logo">
      <span class="d-none d-lg-block">QuickCart</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <!-- Search Bar -->
  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="GET" action="search">
      <input type="text" name="search" placeholder="Search products…" title="Enter search keyword"
        autocomplete="off" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center gap-2">

      <!-- Login Button -->
      <li class="nav-item">
        <a href="/WST-QuickCart/public/login"
           class="btn btn-sm fw-semibold px-3"
           style="background:#fff; color:#005d21; border:1.5px solid #005d21; border-radius:6px;">
          <i class="bi bi-box-arrow-in-right me-1"></i> Login
        </a>
      </li>

      <!-- Register Button -->
      <li class="nav-item me-2">
        <a href="/WST-QuickCart/public/registration"
           class="btn btn-sm fw-semibold px-3"
           style="background:#005d21; color:#fff; border-radius:6px;">
          <i class="bi bi-person-plus me-1"></i> Register
        </a>
      </li>

    </ul>
  </nav>

</header><!-- End Header/Topbar -->
