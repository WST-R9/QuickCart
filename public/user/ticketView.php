<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId   = $_SESSION['authUser']['userId'] ?? 0;
$ticketId = (int) ($_GET['id'] ?? 0);

if (!$ticketId) {
    header("Location: tickets");
    exit;
}

// Fetch ticket — must belong to this user
$stmt = $conn->prepare("
    SELECT * FROM support_tickets
    WHERE ticketId = ? AND userId = ?
");
$stmt->bind_param("ii", $ticketId, $userId);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Ticket not found.'];
    header("Location: tickets");
    exit;
}

// Handle user reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sendReply'])) {
    $message = trim($_POST['message'] ?? '');

    if ($ticket['status'] === 'closed') {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'This ticket is closed. You cannot reply.'];
        header("Location: ticketView?id=$ticketId");
        exit;
    }

    if (!$message) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Message cannot be empty.'];
        header("Location: ticketView?id=$ticketId");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO ticket_messages (ticketId, senderRole, message) VALUES (?, 'customer', ?)");
    $stmt->bind_param("is", $ticketId, $message);
    $stmt->execute();
    $stmt->close();

    // Re-open ticket if it was resolved
    if ($ticket['status'] === 'resolved') {
        $conn->query("UPDATE support_tickets SET status='open', updatedAt=NOW() WHERE ticketId=$ticketId");
    } else {
        $conn->query("UPDATE support_tickets SET updatedAt=NOW() WHERE ticketId=$ticketId");
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Your reply has been sent.'];
    header("Location: ticketView?id=$ticketId");
    exit;
}

// Fetch all messages
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
    'low'    => 'bg-secondary',
    default  => 'bg-secondary',
};

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
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

    <!-- LEFT — Conversation -->
    <div class="col-lg-8">

      <!-- Conversation Thread -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi bi-chat-dots me-2 text-success"></i>Conversation
          </h5>

          <?php if (empty($messages)): ?>
            <p class="text-muted">No messages yet.</p>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($messages as $msg):
                $isAdmin = $msg['senderRole'] === 'admin';
              ?>
                <div class="d-flex <?= $isAdmin ? 'justify-content-start' : 'justify-content-end' ?>">
                  <div style="max-width:80%;">
                    <div class="px-3 py-2 rounded-3 <?= $isAdmin
                        ? 'bg-light border'
                        : 'text-white'
                      ?>"
                      style="<?= !$isAdmin ? 'background:#005d21;' : '' ?>">
                      <div class="small fw-bold mb-1 <?= $isAdmin ? 'text-success' : 'text-white-50' ?>">
                        <?= $isAdmin ? '🛡 Support Team' : '👤 You' ?>
                      </div>
                      <div style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                    </div>
                    <div class="small text-muted mt-1 <?= $isAdmin ? 'text-start' : 'text-end' ?>">
                      <?= date('M d, Y h:i A', strtotime($msg['createdAt'])) ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reply Form -->
      <?php if ($ticket['status'] !== 'closed'): ?>
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-reply me-2 text-success"></i>Send a Reply</h5>
            <form method="POST" action="ticketView?id=<?= $ticketId ?>">
              <div class="mb-3">
                <textarea name="message" class="form-control" rows="4"
                          placeholder="Type your message here…"
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
        <div class="alert" style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:8px;">
          <i class="bi bi-lock me-2 text-muted"></i>
          <span class="text-muted">This ticket is <strong>closed</strong>. If you need further help,
            <a href="ticketCreate" style="color:#005d21;">create a new ticket</a>.
          </span>
        </div>
      <?php endif; ?>

    </div><!-- End Left -->

    <!-- RIGHT — Ticket Info -->
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Ticket Details</h5>
          <ul class="list-group list-group-flush">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Ticket #</span>
              <span class="fw-bold small"><?= htmlspecialchars($ticket['ticketNumber']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Status</span>
              <span class="badge <?= $statusBadge ?>"><?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Priority</span>
              <span class="badge <?= $priorityBadge ?>"><?= ucfirst($ticket['priority']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Category</span>
              <span class="small fw-semibold"><?= ucfirst($ticket['category']) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Created</span>
              <span class="small"><?= date('M d, Y', strtotime($ticket['createdAt'])) ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <span class="text-muted small">Last Updated</span>
              <span class="small"><?= date('M d, Y', strtotime($ticket['updatedAt'])) ?></span>
            </li>
            <li class="list-group-item px-0">
              <span class="text-muted small d-block mb-1">Subject</span>
              <span class="fw-semibold"><?= htmlspecialchars($ticket['subject']) ?></span>
            </li>
          </ul>

          <div class="d-grid gap-2 mt-3">
            <a href="tickets" class="btn btn-outline-primary btn-sm">
              <i class="bi bi-arrow-left me-1"></i> All Tickets
            </a>
            <?php if ($ticket['status'] !== 'closed'): ?>
              <a href="ticketCreate" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-plus me-1"></i> New Ticket
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div><!-- End Right -->

  </div>
</section>

<?php include('includes/footer.php'); ?>
