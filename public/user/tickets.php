<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;
$filter = $_GET['status'] ?? '';

$where  = "t.userId = ?";
$params = [$userId];
$types  = 'i';

if ($filter !== '') {
    $where  .= " AND t.status = ?";
    $params[] = $filter;
    $types   .= 's';
}

$stmt = $conn->prepare("
    SELECT t.*,
           (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticketId = t.ticketId) AS messageCount
    FROM support_tickets t
    WHERE $where
    ORDER BY t.updatedAt DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>Support Tickets</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item active">Support Tickets</li>
    </ol>
  </nav>
</div>

<section class="section">

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">

    <!-- Status filter pills -->
    <div class="d-flex gap-2 flex-wrap">
      <?php foreach (['','open','in_progress','resolved','closed'] as $s): ?>
        <a href="tickets<?= $s ? '?status='.$s : '' ?>"
           class="pill <?= $filter === $s ? 'active' : '' ?>">
          <?= $s === '' ? 'All' : ucfirst(str_replace('_', ' ', $s)) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <a href="ticketCreate" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i> New Ticket
    </a>
  </div>

  <?php if (empty($tickets)): ?>
    <div class="card">
      <div class="card-body">
        <div class="empty-state">
          <i class="bi bi-headset"></i>
          <h5>No tickets found</h5>
          <p>Need help? Create a support ticket and we'll get back to you.</p>
          <a href="ticketCreate" class="btn btn-primary mt-2">
            <i class="bi bi-plus-lg me-1"></i> Create Ticket
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">My Tickets <span>(<?= count($tickets) ?>)</span></h5>
        <div class="table-responsive">
          <table class="table table-borderless table-hover">
            <thead style="background:#e6f4ea;">
              <tr>
                <th>Ticket #</th>
                <th>Subject</th>
                <th>Category</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Messages</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t):
                $statusBadge = match($t['status']) {
                    'open'        => 'bg-success',
                    'in_progress' => 'bg-info text-dark',
                    'resolved'    => 'bg-primary',
                    'closed'      => 'bg-secondary',
                    default       => 'bg-secondary',
                };
                $priorityBadge = match($t['priority']) {
                    'high'   => 'bg-danger',
                    'medium' => 'bg-warning text-dark',
                    'low'    => 'bg-secondary',
                    default  => 'bg-secondary',
                };
              ?>
              <tr>
                <td class="fw-bold"><?= htmlspecialchars($t['ticketNumber']) ?></td>
                <td><?= htmlspecialchars($t['subject']) ?></td>
                <td><?= ucfirst($t['category']) ?></td>
                <td><span class="badge <?= $priorityBadge ?>"><?= ucfirst($t['priority']) ?></span></td>
                <td><span class="badge <?= $statusBadge ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                <td><?= $t['messageCount'] ?></td>
                <td><?= date('M d, Y', strtotime($t['createdAt'])) ?></td>
                <td>
                  <a href="ticketView?id=<?= $t['ticketId'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-eye me-1"></i> View
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

</section>

<?php include('includes/footer.php'); ?>