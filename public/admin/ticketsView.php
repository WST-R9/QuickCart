<?php
include_once("../../app/middleware/admin.php");
include('./includes/header.php');
include('./includes/topbar.php');
include('./includes/sidebar.php');
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");

if (!isset($_GET['id'])) {
    header("Location: tickets.php");
    exit;
}

$ticketId = intval($_GET['id']);

// Ticket info
$ticketQuery = "SELECT 
    t.*,
    CONCAT(u.firstName,' ',u.lastName) AS customerName,
    u.emailAddress,
    u.phoneNumber
FROM support_tickets t
JOIN users u ON t.userId = u.userId
WHERE t.ticketId = $ticketId";

$ticketResult = mysqli_query($conn, $ticketQuery);

if (mysqli_num_rows($ticketResult) == 0) {
    echo "<script>alert('Ticket not found!'); window.location.href='tickets.php';</script>";
    exit;
}

$ticket = mysqli_fetch_assoc($ticketResult);

// Handle reply
if (isset($_POST['sendReply'])) {
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $insertMsg = "INSERT INTO ticket_messages (ticketId, senderRole, message)
                  VALUES ($ticketId, 'admin', '$message')";
    mysqli_query($conn, $insertMsg);

    // Update ticket status automatically to in_progress
    mysqli_query($conn, "UPDATE support_tickets SET status='in_progress' WHERE ticketId=$ticketId");

    echo "<script>alert('Reply sent!'); window.location.href='ticket-view.php?id=$ticketId';</script>";
    exit;
}

// Update status manually
if (isset($_POST['updateStatus'])) {
    $newStatus = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE support_tickets SET status='$newStatus' WHERE ticketId=$ticketId");

    echo "<script>alert('Ticket status updated!'); window.location.href='ticket-view.php?id=$ticketId';</script>";
    logActivity($conn, $_SESSION['user_id'], 'replied_ticket', 'tickets', $ticketId, $ticket['ticketNumber']);
    exit;
}

// Fetch messages
$messagesQuery = "SELECT * FROM ticket_messages 
                  WHERE ticketId = $ticketId
                  ORDER BY createdAt ASC";
$messagesResult = mysqli_query($conn, $messagesQuery);
?>

<div class="pagetitle">
  <h1>Ticket Details</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="tickets.php">Customer Support</a></li>
      <li class="breadcrumb-item active">View Ticket</li>
    </ol>
  </nav>
</div>

<section class="section dashboard">
  <div class="row">

    <!-- LEFT SIDE -->
    <div class="col-lg-8">

      <!-- Ticket Info -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Ticket Information</h5>

          <p><strong>Ticket #:</strong> <?= htmlspecialchars($ticket['ticketNumber']) ?></p>
          <p><strong>Subject:</strong> <?= htmlspecialchars($ticket['subject']) ?></p>
          <p><strong>Category:</strong> <?= ucfirst($ticket['category']) ?></p>
          <p><strong>Priority:</strong> <?= ucfirst($ticket['priority']) ?></p>
          <p><strong>Status:</strong> <?= ucfirst(str_replace("_"," ",$ticket['status'])) ?></p>
          <p><strong>Created:</strong> <?= date("M d, Y h:i A", strtotime($ticket['createdAt'])) ?></p>

          <form method="POST" class="mt-3">
            <label class="form-label"><strong>Update Status</strong></label>
            <select name="status" class="form-select mb-2">
              <option value="open" <?= ($ticket['status']=="open")?"selected":"" ?>>Open</option>
              <option value="in_progress" <?= ($ticket['status']=="in_progress")?"selected":"" ?>>In Progress</option>
              <option value="resolved" <?= ($ticket['status']=="resolved")?"selected":"" ?>>Resolved</option>
              <option value="closed" <?= ($ticket['status']=="closed")?"selected":"" ?>>Closed</option>
            </select>

            <button type="submit" name="updateStatus" class="btn btn-primary btn-sm">
              <i class="bi bi-check-circle"></i> Update
            </button>
          </form>
        </div>
      </div>

      <!-- Messages -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Conversation</h5>

          <?php if (mysqli_num_rows($messagesResult) == 0): ?>
            <p class="text-muted">No messages yet.</p>
          <?php else: ?>
            <?php while ($msg = mysqli_fetch_assoc($messagesResult)): ?>
              <div class="border rounded p-2 mb-2">
                <strong><?= strtoupper($msg['senderRole']) ?>:</strong>
                <p class="mb-1"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                <small class="text-muted"><?= date("M d, Y h:i A", strtotime($msg['createdAt'])) ?></small>
              </div>
            <?php endwhile; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reply Form -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Send Reply</h5>

          <form method="POST">
            <div class="mb-3">
              <textarea name="message" class="form-control" rows="4" required></textarea>
            </div>

            <button type="submit" name="sendReply" class="btn btn-success">
              <i class="bi bi-send"></i> Send Reply
            </button>
          </form>
        </div>
      </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="col-lg-4">

      <!-- Customer Info -->
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Customer Info</h5>

          <p><strong>Name:</strong> <?= htmlspecialchars($ticket['customerName']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($ticket['emailAddress']) ?></p>
          <p><strong>Phone:</strong> <?= htmlspecialchars($ticket['phoneNumber']) ?></p>

          <a href="customer-view.php?id=<?= $ticket['userId'] ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-person"></i> View Customer
          </a>
        </div>
      </div>

      <div class="d-grid">
        <a href="tickets.php" class="btn btn-secondary">
          <i class="bi bi-arrow-left"></i> Back to Tickets
        </a>
      </div>

    </div>

  </div>
</section>

<?php include('./includes/footer.php'); ?>