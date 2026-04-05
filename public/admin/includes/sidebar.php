  <?php $page = basename($_SERVER['PHP_SELF']); ?>
  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'index.php') ? '' : "collapsed" ?> " href="index">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-heading">Management</li>

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'customers.php') ? '' : "collapsed" ?> " href="customers">
          <i class="bi bi-person"></i>
          <span>Customers</span>
        </a>
      </li><!-- End Customers Page Nav -->

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'inventory.php') ? '' : "collapsed" ?> " href="inventory">
          <i class="bi bi-question-circle"></i>
          <span>Inventory</span>
        </a>
      </li><!-- End Inventory Page Nav -->

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'sales.php') ? '' : "collapsed" ?> " href="sales">
          <i class="bi bi-envelope"></i>
          <span>Sales</span>
        </a>
      </li><!-- End Sales Page Nav -->

      <li class="nav-heading">Accounts</li>

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'accounts.php') ? '' : "collapsed" ?> " href="accounts">
          <i class="bi bi-card-list"></i>
          <span>Accounts</span>
        </a>
      </li><!-- End Accounts Page Nav -->

      <li class="nav-heading">Report</li>

      <li class="nav-item">
        <a class="nav-link <?= ($page == 'reports.php') ? '' : "collapsed" ?> " href="reports">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Reports</span>
        </a>
      </li><!-- End Reports Page Nav -->

    </ul>

  </aside><!-- End Sidebar-->

  <main id="main" class="main">