<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index" class="logo d-flex align-items-center">
      <img src="assets/img/qc-logo.png" alt="">
      <span class="d-none d-lg-block">QuickCart</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <div class="search-bar">
    <form class="search-form d-flex align-items-center" method="GET" action="search">
      <input type="text" name="query" placeholder="Search" title="Enter search keyword" autocomplete="off">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      <!-- Mobile Search Toggle -->
      <li class="nav-item d-block d-lg-none">
        <a class="nav-link nav-icon search-bar-toggle" href="#">
          <i class="bi bi-search"></i>
        </a>
      </li>

      <!-- Profile Dropdown -->
      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <!-- Avatar initials circle -->
          <div class="rounded-circle d-flex align-items-center justify-content-center"
               style="width:36px;height:36px;background-color:#fff;flex-shrink:0;">
            <span style="font-size:14px;font-weight:700;color:#005d21;font-family:'Nunito',sans-serif;line-height:1;">
              <?php
                $fullName = $_SESSION['authUser']['fullName'] ?? 'Admin';
                $parts    = explode(' ', trim($fullName));
                $initials = strtoupper(substr($parts[0], 0, 1));
                if (count($parts) > 1) {
                    $initials .= strtoupper(substr(end($parts), 0, 1));
                }
                echo $initials;
              ?>
            </span>
          </div>
          <span class="d-none d-md-block dropdown-toggle ps-2">
            <?= htmlspecialchars($_SESSION['authUser']['fullName'] ?? 'Admin') ?>
          </span>
        </a><!-- End Profile Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">

          <li class="dropdown-header">
            <h6><?= htmlspecialchars($_SESSION['authUser']['fullName'] ?? 'Admin') ?></h6>
            <span>@<?= htmlspecialchars($_SESSION['authUser']['username'] ?? '') ?></span>
          </li>

          <li><hr class="dropdown-divider"></li>

          <li>
            <a class="dropdown-item d-flex align-items-center" href="accounts">
              <i class="bi bi-person"></i>
              <span>My Profile</span>
            </a>
          </li>

          <li><hr class="dropdown-divider"></li>

          <li>
            <form action="../../app/controllers/adminController.php" method="post">
              <button type="submit" name="logoutButton" class="dropdown-item d-flex align-items-center" style="color: red;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </button>
            </form>
          </li>

        </ul><!-- End Profile Dropdown Items -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header -->
