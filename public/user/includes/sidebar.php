<?php $page = basename($_SERVER['PHP_SELF']); ?>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">
  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Home -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'index.php') ? '' : 'collapsed' ?>" href="index">
        <i class="ri-home-line"></i>
        <span>Home</span>
      </a>
    </li>

    <!-- Browse All -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'allProducts.php') ? '' : 'collapsed' ?>" href="allProducts">
        <i class="ri-store-line"></i>
        <span>Browse All</span>
      </a>
    </li>

    <!-- Categories -->
    <li class="nav-heading">Shop by Category</li>

    <!-- Beverages -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'beverages.php') ? '' : 'collapsed' ?>" href="beverages">
        <i class="ri-cup-line"></i>
        <span>Beverages</span>
      </a>
    </li>

    <!-- Snacks -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'snacks.php') ? '' : 'collapsed' ?>" href="snacks">
        <i class="ri-cake-line"></i>
        <span>Snacks</span>
      </a>
    </li>

    <!-- Instant & Canned -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'instantCanned.php') ? '' : 'collapsed' ?>" href="instantCanned">
        <i class="ri-archive-line"></i>
        <span>Instant & Canned</span>
      </a>
    </li>

    <!-- Dairy & Eggs -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'dairyEggs.php') ? '' : 'collapsed' ?>" href="dairyEggs">
        <i class="ri-drop-line"></i>
        <span>Dairy & Eggs</span>
      </a>
    </li>

    <!-- Frozen Foods -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'frozenFoods.php') ? '' : 'collapsed' ?>" href="frozenFoods">
        <i class="ri-temp-cold-line"></i>
        <span>Frozen Foods</span>
      </a>
    </li>

    <!-- Personal Care -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'personalCare.php') ? '' : 'collapsed' ?>" href="personalCare">
        <i class="ri-hand-sanitizer-line"></i>
        <span>Personal Care</span>
      </a>
    </li>

    <!-- Household -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'household.php') ? '' : 'collapsed' ?>" href="household">
        <i class="ri-home-gear-line"></i>
        <span>Household</span>
      </a>
    </li>

    <!-- Health & Wellness -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'healthWellness.php') ? '' : 'collapsed' ?>" href="healthWellness">
        <i class="ri-heart-pulse-line"></i>
        <span>Health & Wellness</span>
      </a>
    </li>

    <!-- Bread & Bakery -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'breadBakery.php') ? '' : 'collapsed' ?>" href="breadBakery">
        <i class="ri-bread-line"></i>
        <span>Bread & Bakery</span>
      </a>
    </li>

    <!-- Fruits & Vegetables -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'fruitsVegetables.php') ? '' : 'collapsed' ?>" href="fruitsVegetables">
        <i class="ri-plant-line"></i>
        <span>Fruits & Vegetables</span>
      </a>
    </li>

    <!-- Condiments & Sauces -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'condimentsSauces.php') ? '' : 'collapsed' ?>" href="condimentsSauces">
        <i class="ri-flask-line"></i>
        <span>Condiments & Sauces</span>
      </a>
    </li>

    <!-- Rice & Grains -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'riceGrains.php') ? '' : 'collapsed' ?>" href="riceGrains">
        <i class="ri-bowl-line"></i>
        <span>Rice & Grains</span>
      </a>
    </li>

    <!-- Baby & Kids -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'babyKids.php') ? '' : 'collapsed' ?>" href="babyKids">
        <i class="ri-user-smile-line"></i>
        <span>Baby & Kids</span>
      </a>
    </li>

    <!-- Pet Supplies -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'petSupplies.php') ? '' : 'collapsed' ?>" href="petSupplies">
        <i class="ri-bear-smile-line"></i>
        <span>Pet Supplies</span>
      </a>
    </li>

  </ul>
</aside><!-- End Sidebar -->

<main id="main" class="main">