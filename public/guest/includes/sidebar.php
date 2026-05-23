<?php $page = basename($_SERVER['PHP_SELF']); ?>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">

    <li class="nav-item">
      <a class="nav-link <?= ($page == 'index.php') ? '' : 'collapsed' ?>" href="index">
        <i class="ri-home-line"></i><span>Home</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= ($page == 'allProducts.php') ? '' : 'collapsed' ?>" href="allProducts">
        <i class="ri-store-line"></i><span>Browse All</span>
      </a>
    </li>

    <li class="nav-heading">Shop by Category</li>

    <li class="nav-item">
      <a class="nav-link <?= ($page == 'beverages.php') ? '' : 'collapsed' ?>" href="beverages">
        <i class="ri-cup-line"></i><span>Beverages</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'snacks.php') ? '' : 'collapsed' ?>" href="snacks">
        <i class="ri-cake-line"></i><span>Snacks</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'instantCanned.php') ? '' : 'collapsed' ?>" href="instantCanned">
        <i class="ri-archive-line"></i><span>Instant &amp; Canned</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'dairyEggs.php') ? '' : 'collapsed' ?>" href="dairyEggs">
        <i class="ri-drop-line"></i><span>Dairy &amp; Eggs</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'frozenFoods.php') ? '' : 'collapsed' ?>" href="frozenFoods">
        <i class="ri-temp-cold-line"></i><span>Frozen Foods</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'personalCare.php') ? '' : 'collapsed' ?>" href="personalCare">
        <i class="ri-hand-sanitizer-line"></i><span>Personal Care</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'household.php') ? '' : 'collapsed' ?>" href="household">
        <i class="ri-home-gear-line"></i><span>Household</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'healthWellness.php') ? '' : 'collapsed' ?>" href="healthWellness">
        <i class="ri-heart-pulse-line"></i><span>Health &amp; Wellness</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'breadBakery.php') ? '' : 'collapsed' ?>" href="breadBakery">
        <i class="ri-bread-line"></i><span>Bread &amp; Bakery</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'fruitsVegetables.php') ? '' : 'collapsed' ?>" href="fruitsVegetables">
        <i class="ri-plant-line"></i><span>Fruits &amp; Vegetables</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'condimentsSauces.php') ? '' : 'collapsed' ?>" href="condimentsSauces">
        <i class="ri-flask-line"></i><span>Condiments &amp; Sauces</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'riceGrains.php') ? '' : 'collapsed' ?>" href="riceGrains">
        <i class="ri-bowl-line"></i><span>Rice &amp; Grains</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'babyKids.php') ? '' : 'collapsed' ?>" href="babyKids">
        <i class="ri-user-smile-line"></i><span>Baby &amp; Kids</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'petSupplies.php') ? '' : 'collapsed' ?>" href="petSupplies">
        <i class="ri-bear-smile-line"></i><span>Pet Supplies</span>
      </a>
    </li>

    <!-- Login / Register CTAs at bottom of sidebar -->
    <li class="nav-heading">My Account</li>
    <li class="nav-item">
      <a class="nav-link collapsed" href="/WST-QuickCart/public/login">
        <i class="bi bi-box-arrow-in-right"></i><span>Login</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link collapsed" href="/WST-QuickCart/public/registration">
        <i class="bi bi-person-plus"></i><span>Create Account</span>
      </a>
    </li>

  </ul>
</aside><!-- End Sidebar -->

<main id="main" class="main">
