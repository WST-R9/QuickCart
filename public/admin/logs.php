<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

$logsQuery = "SELECT 
    l.*,
    CONCAT(u.firstName, ' ', u.lastName) AS adminName,
    u.username
FROM activity_logs l
JOIN users u ON l.userId = u.userId
ORDER BY l.createdAt DESC
LIMIT 200";

$logsResult = mysqli_query($conn, $logsQuery);
?>

<div class="pagetitle">
  <h1>Activity Logs</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Activity Logs</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">
    <div class="col-lg-12">
      <div class="card recent-sales overflow-auto">
        <div class="card-body">
          <h5 class="card-title">All Activity <span>| Latest 200</span></h5>

          <table class="table table-borderless datatable">
            <thead>
              <tr>
                <th>Admin</th>
                <th>Action</th>
                <th>Module</th>
                <th>Target</th>
                <th>Details</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($logsResult)): ?>
                <?php
                $moduleBadge = match($row['module']) {
                    'inventory'  => 'bg-success',
                    'categories' => 'bg-primary',
                    'orders'     => 'bg-warning text-dark',
                    'tickets'    => 'bg-info text-dark',
                    default      => 'bg-secondary'
                };

                $actionIcon = match(true) {
                    str_contains($row['action'], 'added')   => '<i class="bi bi-plus-circle text-success me-1"></i>',
                    str_contains($row['action'], 'updated') => '<i class="bi bi-pencil text-primary me-1"></i>',
                    str_contains($row['action'], 'deleted') => '<i class="bi bi-trash text-danger me-1"></i>',
                    str_contains($row['action'], 'replied') => '<i class="bi bi-reply text-info me-1"></i>',
                    default => '<i class="bi bi-activity me-1"></i>'
                };
                ?>
                <tr>
                  <td>
                    <?= htmlspecialchars($row['adminName']) ?>
                    <br>
                    <small class="text-muted">@<?= htmlspecialchars($row['username']) ?></small>
                  </td>
                  <td>
                    <?= $actionIcon ?>
                    <?= ucfirst(str_replace('_', ' ', $row['action'])) ?>
                  </td>
                  <td>
                    <span class="badge <?= $moduleBadge ?>">
                      <?= ucfirst($row['module']) ?>
                    </span>
                  </td>
                  <td>
                    <?= $row['targetLabel'] ? htmlspecialchars($row['targetLabel']) : '<span class="text-muted">N/A</span>' ?>
                    <?php if ($row['targetId']): ?>
                      <br><small class="text-muted">ID: <?= $row['targetId'] ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?= $row['details'] ? htmlspecialchars($row['details']) : '<span class="text-muted">—</span>' ?>
                  </td>
                  <td><?= date("M d, Y h:i A", strtotime($row['createdAt'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>