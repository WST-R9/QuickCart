<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");

$filter   = $_GET['status'] ?? '';
$search   = trim($_GET['search'] ?? '');

$where  = "1=1";
$params = [];
$types  = '';

if ($filter !== '') {
    $where   .= " AND t.status = ?";
    $params[] = $filter;
    $types   .= 's';
}

if ($search !== '') {
    $where   .= " AND (t.ticketNumber LIKE ? OR t.subject LIKE ? OR CONCAT(u.firstName,' ',u.lastName) LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

$sql = "
    SELECT
        t.*,
        CONCAT(u.firstName,' ',u.lastName) AS customerName,
        u.emailAddress,
        (SELECT COUNT(*) FROM ticket_messages tm WHERE tm.ticketId = t.ticketId) AS messageCount
    FROM support_tickets t
    LEFT JOIN users u ON t.userId = u.userId
    WHERE $where
    ORDER BY t.updatedAt DESC
";

if ($types !== '') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $tickets = mysqli_fetch_all(mysqli_query($conn, $sql), MYSQLI_ASSOC);
}

// Count by status
$counts = [];
$countResult = mysqli_query($conn, "SELECT status, COUNT(*) AS cnt FROM support_tickets GROUP BY status");
while ($row = mysqli_fetch_assoc($countResult)) {
    $counts[$row['status']] = $row['cnt'];
}
$counts['all'] = array_sum($counts);
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

  <!-- Stat Cards -->
  <div class="row g-3 mb-3">
    <?php
    $statCards = [
      ['label' => 'Total',       'key' => 'all',         'icon' => 'bi-headset',          'color' => '#005d21'],
      ['label' => 'Open',        'key' => 'open',        'icon' => 'bi-envelope-open',    'color' => '#198754'],
      ['label' => 'In Progress', 'key' => 'in_progress', 'icon' => 'bi-hourglass-split',  'color' => '#0dcaf0'],
      ['label' => 'Resolved',    'key' => 'resolved',    'icon' => 'bi-check-circle',     'color' => '#0d6efd'],
      ['label' => 'Closed',      'key' => 'closed',      'icon' => 'bi-lock',             'color' => '#6c757d'],
    ];
    foreach ($statCards as $card): ?>
      <div class="col-6 col-md-2">
        <div class="card text-center h-100">
          <div class="card-body py-3">
            <i class="bi <?= $card['icon'] ?>" style="font-size:24px; color:<?= $card['color'] ?>;"></i>
            <div class="fw-bold fs-5 mt-1"><?= $counts[$card['key']] ?? 0 ?></div>
            <div class="text-muted small"><?= $card['label'] ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="card-title mb-0">All Tickets</h5>

        <!-- Search -->
        <form method="GET" action="" class="d-flex gap-2 align-items-center">
          <?php if ($filter): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
          <?php endif; ?>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Search ticket, subject, customer…"
                 value="<?= htmlspecialchars($search) ?>"
                 style="width:240px; border-color:#d4e8da;">
          <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search"></i></button>
          <?php if ($search || $filter): ?>
            <a href="tickets" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Status Filter Pills -->
      <div class="d-flex gap-2 flex-wrap mb-3">
        <?php foreach ([''=>'All','open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $val => $label): ?>
          <a href="tickets<?= $val ? '?status='.$val : '' ?><?= $search ? '&search='.urlencode($search) : '' ?>"
             class="pill <?= $filter === $val ? 'active' : '' ?>">
            <?= $label ?>
            <?php if (isset($counts[$val === '' ? 'all' : $val])): ?>
              <span class="ms-1">(<?= $counts[$val === '' ? 'all' : $val] ?>)</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($tickets)): ?>
        <div class="empty-state">
          <i class="bi bi-headset"></i>
          <h5>No tickets found</h5>
          <p>Try a different filter or search term.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-borderless table-hover align-middle datatable">
            <thead style="background:#e6f4ea;">
              <tr>
                <th>Ticket #</th>
                <th>Customer</th>
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
                    'low'    => 'bg-info text-dark',
                    default  => 'bg-secondary',
                };
              ?>
                <tr>
                  <td class="fw-bold"><?= htmlspecialchars($t['ticketNumber']) ?></td>
                  <td><?= htmlspecialchars($t['customerName'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($t['subject']) ?></td>
                  <td><?= ucfirst($t['category']) ?></td>
                  <td><span class="badge <?= $priorityBadge ?>"><?= ucfirst($t['priority']) ?></span></td>
                  <td><span class="badge <?= $statusBadge ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                  <td><?= $t['messageCount'] ?></td>
                  <td class="text-muted small"><?= date('M d, Y', strtotime($t['createdAt'])) ?></td>
                  <td>
                    <a href="ticketsView?id=<?= $t['ticketId'] ?>" class="btn btn-sm btn-primary">
                      <i class="bi bi-eye me-1"></i> View
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</section>

<?php include('./includes/footer.php'); ?>
