<?php
// Cart item count — uses the flat `cart` table (userId + productId + quantity)
$cartCount = 0;

$stmt = $conn->prepare("SELECT IFNULL(SUM(quantity), 0) AS cartCount
                        FROM cart
                        WHERE userId = ?");
if ($stmt) {
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $cartCount = (int) ($stmt->get_result()->fetch_assoc()['cartCount'] ?? 0);
  $stmt->close();
}

//Role based Initials
$userId = $_SESSION['authUser']['userId'] ?? 0;

$role = $_SESSION['userRole'] ?? 'customer';

$roleBadge = match(strtolower($role)) {
  'admin'    => ['bg-danger',  'bi-shield-lock',  'Admin'],
  default    => ['bg-success', 'bi-shield-check', 'Customer'],
};
[$badgeClass, $badgeIcon, $badgeLabel] = $roleBadge;

// Build display name from session (stored as firstName / lastName separately)
$fullName  = htmlspecialchars($_SESSION['authUser']['fullName'] ?? 'User');
$username  = htmlspecialchars($_SESSION['authUser']['username'] ?? '');
$nameParts = explode(' ', trim($fullName));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)) ?: 'U';
?>

<!-- ======= Header/Topbar ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

  <div class="d-flex align-items-center justify-content-between">
    <a href="index" class="logo d-flex align-items-center">
      <img src="assets/img/qc-logo.png" alt="QuickCart Logo">
      <span class="d-none d-lg-block">QuickCart</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div><!-- End Logo -->

  <!-- Search Bar -->
  <div class="search-bar position-relative">
    <form class="search-form d-flex align-items-center" method="GET" action="shop">
      <input type="text" name="search" id="topbarSearch" placeholder="Search products…" title="Enter search keyword"
        autocomplete="off">
      <button type="submit" title="Search"><i class="bi bi-search"></i></button>
    </form>
  </div><!-- End Search Bar -->

  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">
  
      <!-- Cart Icon -->
      <li class="nav-item">
        <a class="nav-link nav-icon position-relative" href="cart" title="My Cart">
          <i class="bi bi-cart3"></i>
          <span class="cart-badge cart-count-badge" <?= $cartCount === 0 ? 'style="display:none;"' : '' ?>>
            <?= $cartCount ?>
          </span>
        </a>
      </li>

      <!-- Profile Dropdown -->
      <li class="nav-item dropdown pe-3">

        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <!-- Avatar initials circle -->
          <div class="rounded-circle d-flex align-items-center justify-content-center"
            style="width:36px;height:36px;background-color:#fff;flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:#005d21;font-family:'Nunito',sans-serif;line-height:1;">
              <?= $initials ?: 'U' ?>
            </span>
          </div>
          <span class="d-none d-md-block dropdown-toggle ps-2">
            <?= $fullName ?>
          </span>
        </a><!-- End Profile Icon -->

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">

          <li class="dropdown-header">
            <h6><?= $fullName ?></h6>
            <span>@<?= $username ?></span>
          </li>

          <li>
            <hr class="dropdown-divider">
          </li>

          <!-- My Profile -->
          <li>
            <a class="dropdown-item d-flex align-items-center" href="accounts">
              <i class="bi bi-person"></i>
              <span>My Account</span>
            </a>
          </li>

          <!-- My orders -->
          <li>
            <a class="dropdown-item d-flex align-items-center" href="orders">
              <i class="bi bi-bag-check"></i>
              <span>My Orders</span>
            </a>
          </li>

          <!-- Wishlist -->
          <li>
            <a class="dropdown-item d-flex align-items-center" href="wishlist">
              <i class="bi bi-heart"></i>
              <span>Wishlist</span>
            </a>
          </li>

          <!-- Track Order -->
          <li class="nav-item">
            <a class="dropdown-item d-flex align-items-center" href="track">
              <i class="bi bi-truck"></i>
              <span>Track Order</span>
            </a>
          </li>

          <!-- Tickets -->
          <li class="nav-item">
            <a class="dropdown-item d-flex align-items-center" href="tickets">
              <i class="bi bi-headset"></i>
              <span>Support Tickets</span>
            </a>
          </li>

          <li>
            <hr class="dropdown-divider">
          </li>

          <li>
            <form action="../../app/controllers/userController.php" method="post">
              <button type="submit" name="logoutButton" class="dropdown-item d-flex align-items-center"
                style="color: red;">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
              </button>
            </form>
          </li>

        </ul><!-- End Profile Dropdown -->
      </li><!-- End Profile Nav -->

    </ul>
  </nav><!-- End Icons Navigation -->

</header><!-- End Header/Topbar -->