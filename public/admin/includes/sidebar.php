<?php $page = basename($_SERVER['PHP_SELF']); ?>
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- Dashboard -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'index.php') ? '' : "collapsed" ?> " href="index">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <li class="nav-heading">Management</li>

    <!-- Customers -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'customers.php') ? '' : "collapsed" ?> " href="customers">
        <i class="bi bi-people"></i>
        <span>Customers</span>
      </a>
    </li>

    <!-- Orders -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'orders.php') ? '' : "collapsed" ?> " href="orders">
        <i class="bi bi-bag-check"></i>
        <span>Orders</span>
      </a>
    </li>

    <!-- Payments -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'payments.php') ? '' : "collapsed" ?> " href="payments">
        <i class="bi bi-credit-card"></i>
        <span>Payments</span>
      </a>
    </li>

    <!-- Shipping -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'shipping.php') ? '' : "collapsed" ?> " href="shipping">
        <i class="bi bi-truck"></i>
        <span>Shipping</span>
      </a>
    </li>

    <!-- Inventory -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'inventory.php') ? '' : "collapsed" ?> " href="inventory">
        <i class="bi bi-boxes"></i>
        <span>Inventory</span>
      </a>
    </li>

    <!-- Categories -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'categories.php') ? '' : "collapsed" ?> " href="categories">
        <i class="bi bi-tags"></i>
        <span>Categories</span>
      </a>
    </li>

    <!-- Suppliers -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'suppliers.php') ? '' : "collapsed" ?> " href="suppliers">
        <i class="bi bi-truck-flatbed"></i>
        <span>Suppliers</span>
      </a>
    </li>


    <li class="nav-heading">Support</li>

    <!-- Support Tickets -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'tickets.php') ? '' : "collapsed" ?> " href="tickets">
        <i class="bi bi-headset"></i>
        <span>Customer Support</span>
      </a>
    </li>

    <!-- Reviews -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'reviews.php') ? '' : "collapsed" ?> " href="reviews">
        <i class="bi bi-star"></i>
        <span>Reviews</span>
      </a>
    </li>


    <li class="nav-heading">Reports</li>

    <!-- Sales -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'sales.php') ? '' : "collapsed" ?> " href="sales">
        <i class="bi bi-graph-up-arrow"></i>
        <span>Sales</span>
      </a>
    </li>

    <!-- Reports -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'reports.php') ? '' : "collapsed" ?> " href="reports">
        <i class="bi bi-table"></i>
        <span>Reports</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link <?= ($page == 'logs.php') ? '' : "collapsed" ?>" href="logs">
        <i class="bi bi-journal-text"></i>
        <span>Activity Logs</span>
      </a>
    </li>


    <li class="nav-heading">Accounts</li>

    <!-- Accounts -->
    <li class="nav-item">
      <a class="nav-link <?= ($page == 'accounts.php') ? '' : "collapsed" ?> " href="accounts">
        <i class="bi bi-person-gear"></i>
        <span>Account Settings</span>
      </a>
    </li>

  </ul>

</aside><!-- End Sidebar-->

<main id="main" class="main">