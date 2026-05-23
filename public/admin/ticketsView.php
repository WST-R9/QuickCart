<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");

if (!isset($_GET['id'])) {
    header("Location: tickets");
    exit;
}

$ticketId = (int) $_GET['id'];

// Fetch ticket
$stmt = $conn->prepare("
    SELECT t.*,
           CONCAT(u.firstName,' ',u.lastName) AS customerName,
           u.emailAddress,
           u.phoneNumber,
           u.userId AS customerId
    FROM support_tickets t
    LEFT JOIN users u ON t.userId = u.userId
    WHERE t.ticketId = ?
");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ticket not found.'];
    header("Location: tickets");
    exit;
}

// Handle admin reply — BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendReply'])) {
    $message = trim($_POST['message'] ?? '');
    if (!$message) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Reply cannot be empty.'];
        header("Location: ticketsView?id=$ticketId");
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO ticket_messages (ticketId, senderRole, message) VALUES (?, 'admin', ?)");
    $stmt->bind_param("is", $ticketId, $message);
    $stmt->execute();
    $stmt->close();
    if ($ticket['status'] === 'open') {
        $conn->query("UPDATE support_tickets SET status='in_progress', updatedAt=NOW() WHERE ticketId=$ticketId");
    } else {
        $conn->query("UPDATE support_tickets SET updatedAt=NOW() WHERE ticketId=$ticketId");
    }
    logActivity($conn, $_SESSION['authUser']['userId'], 'replied_ticket', 'support_tickets', $ticketId, $ticket['ticketNumber']);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Reply sent successfully.'];
    header("Location: ticketsView?id=$ticketId");
    exit;
}

// Handle status update — BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateStatus'])) {
    $validStatuses = ['open', 'in_progress', 'resolved', 'closed'];
    $newStatus = $_POST['status'] ?? '';
    if (!in_array($newStatus, $validStatuses)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid status.'];
        header("Location: ticketsView?id=$ticketId");
        exit;
    }
    $stmt = $conn->prepare("UPDATE support_tickets SET status=?, updatedAt=NOW() WHERE ticketId=?");
    $stmt->bind_param("si", $newStatus, $ticketId);
    $stmt->execute();
    $stmt->close();
    logActivity($conn, $_SESSION['authUser']['userId'], 'updated_ticket_status', 'support_tickets', $ticketId, $ticket['ticketNumber']);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status updated to <strong>' . ucfirst(str_replace('_', ' ', $newStatus)) . '</strong>.'];
    header("Location: ticketsView?id=$ticketId");
    exit;
}

// Fetch messages
$stmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticketId = ? ORDER BY createdAt ASC");
$stmt->bind_param("i", $ticketId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusBadge = match($ticket['status']) {
    'open'        => 'bg-success',
    'in_progress' => 'bg-info text-dark',
    'resolved'    => 'bg-primary',
    'closed'      => 'bg-secondary',
    default       => 'bg-secondary',
};
$priorityBadge = match($ticket['priority']) {
    'high'   => 'bg-danger',
    'medium' => 'bg-warning text-dark',
    'low'    => 'bg-info text-dark',
    default  => 'bg-secondary',
};

// HTML output starts AFTER all redirects
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
?>

<div class="pagetitle">
  <h1>Ticket #<?= htmlspecialchars($ticket['ticketNumber']) ?></h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="tickets">Support Tickets</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($ticket['ticketNumber']) ?></li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row">
    <div class="col-lg-8">

      <div class="card mb-3">
        <div class="card-body py-3">
          <h5 class="mb-1 fw-bold"><?= htmlspecialchars($ticket['subject']) ?></h5>
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge <?= $statusBadge ?>"><?= ucfirst(str_replace('_',' ',$ticket['status'])) ?></span>
            <span class="badge <?= $priorityBadge ?>"><?= ucfirst($ticket['priority']) ?> Priority</span>
            <span class="badge bg-light text-dark border"><?= ucfirst($ticket['category']) ?></span>
            <span class="text-muted small align-self-center">Opened <?= date('M d, Y h:i A', strtotime($ticket['createdAt'])) ?></span>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-chat-dots me-2 text-success"></i>Conversation</h5>
          <?php if (empty($messages)): ?>
            <p class="text-muted">No messages yet.</p>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($messages as $msg):
                $isAdmin = $msg['senderRole'] === 'admin'; ?>
                <div class="d-flex <?= $isAdmin ? 'justify-content-end' : 'justify-content-start' ?>">
                  <div style="max-width:80%;">
                    <div class="px-3 py-2 rounded-3 <?= $isAdmin ? 'text-white' : 'bg-light border' ?>"
                         style="<?= $isAdmin ? 'background:#005d21;' : '' ?>">
                      <div class="small fw-bold mb-1 <?= $isAdmin ? 'text-white-50' : 'text-success' ?>">
                        <?= $isAdmin ? '🛡 You (Admin)' : '👤 ' . htmlspecialchars($ticket['customerName'] ?? 'Customer') ?>
                      </div>
                      <div style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                    </div>
                    <div class="small text-muted mt-1 <?= $isAdmin ? 'text-end' : 'text-start' ?>">
                      <?= date('M d, Y h:i A', strtotime($msg['createdAt'])) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-reply me-2 text-success"></i>Send Reply</h5>
            <form method="POST" action="ticketsView?id=<?= $ticketId ?>">
              <div class="mb-3">
                <textarea name="message" class="form-control" rows="4"
                          placeholder="Type your reply here…"
                          style="border-color:#d4e8da; resize:vertical;" required></textarea>
              </div>
              <div class="d-flex gap-2">
                <button type="submit" name="sendReply" class="btn btn-primary">
                  <i class="bi bi-send me-1"></i> Send Reply
                </button>
                <a href="tickets" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-1"></i> Back
                </a>
              </div>
            </form>
          </div>
        </div>
      <?php else: ?>
        <div class="alert mb-3" style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px;">
          <i class="bi bi-lock me-2 text-muted"></i>
          <span class="text-muted">This ticket is <strong>closed</strong>. Update the status to reply.</span>
        </div>
        <div class="text-end mb-3">
          <a href="tickets" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Tickets
          </a>
        </div>
      <?php endif; ?>

    </div>

    <div class="col-lg-4">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Update Status</h5>
          <form method="POST" action="ticketsView?id=<?= $ticketId ?>">
            <select name="status" class="form-select mb-2" style="border-color:#d4e8da;">
              <option value="open"        <?= $ticket['status']==='open'        ? 'selected':'' ?>>Open</option>
              <option value="in_progress" <?= $ticket['status']==='in_progress' ? 'selected':'' ?>>In Progress</option>
              <option value="resolved"    <?= $ticket['status']==='resolved'    ? 'selected':'' ?>>Resolved</option>
              <option value="closed"      <?= $ticket['status']==='closed'      ? 'selected':'' ?>>Closed</option>
            </select>
            <button type="submit" name="updateStatus" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-check-circle me-1"></i> Update Status
            </button>
          </form>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Ticket Details</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between px-0">
              <span class="text-muted small">Ticket #</span>
              <span class="fw-bold small"><?= htmlspecialchars($ticket['ticketNumber']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span class="text-muted small">Category</span>
              <span class="small"><?= ucfirst($ticket['category']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span class="text-muted small">Priority</span>
              <span class="badge <?= $priorityBadge ?>"><?= ucfirst($ticket['priority']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span class="text-muted small">Created</span>
              <span class="small"><?= date('M d, Y', strtotime($ticket['createdAt'])) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between px-0">
              <span class="text-muted small">Last Updated</span>
              <span class="small"><?= date('M d, Y', strtotime($ticket['updatedAt'])) ?></span>
            </li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Customer Info</h5>
          <?php if ($ticket['customerName']): ?>
            <p class="mb-1"><i class="bi bi-person me-2 text-muted"></i><?= htmlspecialchars($ticket['customerName']) ?></p>
            <p class="mb-1"><i class="bi bi-envelope me-2 text-muted"></i><?= htmlspecialchars($ticket['emailAddress'] ?? '—') ?></p>
            <p class="mb-3"><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($ticket['phoneNumber'] ?? '—') ?></p>
            <a href="customersView?id=<?= $ticket['customerId'] ?>" class="btn btn-sm btn-outline-primary w-100">
              <i class="bi bi-person-lines-fill me-1"></i> View Customer Profile
            </a>
          <?php else: ?>
            <p class="text-muted small">No customer linked to this ticket.</p>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include('./includes/footer.php'); ?>
