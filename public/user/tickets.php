<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');

$userId   = $_SESSION['authUser']['userId'] ?? 0;
$fullName = $_SESSION['authUser']['fullName'] ?? 'User';
$success  = '';
$error    = '';

// ----------------------------------------
// HANDLE NEW TICKET SUBMISSION
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitTicket'])) {
    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $orderId  = (int)($_POST['orderId'] ?? 0) ?: null;

    if ($subject === '' || $message === '' || $category === '') {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO tickets (userId, orderId, subject, category, message, status, createdAt)
             VALUES (?, ?, ?, ?, ?, 'open', NOW())"
        );
        $stmt->bind_param("iisss", $userId, $orderId, $subject, $category, $message);
        if ($stmt->execute()) {
            $success = 'Your ticket has been submitted. Our team will get back to you soon.';
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

// ----------------------------------------
// FETCH USER'S TICKETS
// ----------------------------------------
$stmt = $conn->prepare(
    "SELECT t.ticketId, t.subject, t.category, t.status, t.createdAt,
            o.orderNumber
     FROM tickets t
     LEFT JOIN orders o ON t.orderId = o.orderId
     WHERE t.userId = ?
     ORDER BY t.createdAt DESC
     LIMIT 20"
);
$stmt->bind_param("i", $userId);
$stmt->execute();
$ticketsResult = $stmt->get_result();
$stmt->close();

// Fetch user's recent orders for the form dropdown
$stmt2 = $conn->prepare(
    "SELECT orderId, orderNumber FROM orders WHERE userId = ? ORDER BY orderedAt DESC LIMIT 20"
);
$stmt2->bind_param("i", $userId);
$stmt2->execute();
$userOrders = $stmt2->get_result();
$stmt2->close();

$ticketStatusBadge = [
    'open'        => 'bg-primary',
    'in_progress' => 'bg-info text-dark',
    'resolved'    => 'bg-success',
    'closed'      => 'bg-secondary',
];
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
  <div class="row">

    <!-- ========= LEFT: New Ticket Form ========= -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi bi-plus-circle me-1"></i> Open a New Ticket
          </h5>

          <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center gap-2">
              <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($success) ?>
            </div>
          <?php endif; ?>
          <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
              <select name="category" class="form-select" id="ticketCategory" required>
                <option value="">Select a category…</option>
                <option value="Order Issue">Order Issue</option>
                <option value="Payment">Payment</option>
                <option value="Delivery">Delivery</option>
                <option value="Product Quality">Product Quality</option>
                <option value="Return / Refund">Return / Refund</option>
                <option value="Account">Account</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Related Order <span class="text-muted small">(optional)</span></label>
              <select name="orderId" class="form-select" id="ticketOrderId">
                <option value="">No specific order</option>
                <?php while ($ord = $userOrders->fetch_assoc()): ?>
                  <option value="<?= (int)$ord['orderId'] ?>">
                    <?= htmlspecialchars($ord['orderNumber']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" id="ticketSubject" class="form-control"
                     placeholder="Brief description of your issue" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
              <textarea name="message" id="ticketMessage" class="form-control" rows="5"
                        placeholder="Describe your issue in detail…" required></textarea>
            </div>
            <button type="button" id="submitTicketBtn" class="btn btn-primary w-100">
              <i class="bi bi-send me-1"></i> Submit Ticket
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ========= RIGHT: Ticket History ========= -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">My Tickets</h5>

          <?php if ($ticketsResult->num_rows === 0): ?>
            <div class="empty-state">
              <i class="bi bi-headset"></i>
              <h5>No tickets yet</h5>
              <p>You haven't opened any support tickets. We're here to help!</p>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-borderless align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Category</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1; while ($ticket = $ticketsResult->fetch_assoc()):
                    $s     = $ticket['status'];
                    $badge = $ticketStatusBadge[$s] ?? 'bg-secondary';
                  ?>
                    <tr>
                      <td class="text-muted small"><?= $i++ ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($ticket['subject']) ?></td>
                      <td class="small"><?= htmlspecialchars($ticket['category']) ?></td>
                      <td class="small text-muted">
                        <?= $ticket['orderNumber'] ? htmlspecialchars($ticket['orderNumber']) : '—' ?>
                      </td>
                      <td>
                        <span class="badge <?= $badge ?>">
                          <?= ucfirst(str_replace('_', ' ', $s)) ?>
                        </span>
                      </td>
                      <td class="text-muted small">
                        <?= date("M d, Y", strtotime($ticket['createdAt'])) ?>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
document.getElementById('submitTicketBtn').addEventListener('click', function () {
    const category = document.getElementById('ticketCategory').value;
    const subject  = document.getElementById('ticketSubject').value.trim();
    const message  = document.getElementById('ticketMessage').value.trim();
    const orderId  = document.getElementById('ticketOrderId').value;

    if (!category || !subject || !message) {
        alert('Please fill in all required fields.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    const fields = { category, subject, message, orderId, submitTicket: '1' };
    for (const [key, val] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = key;
        input.value = val;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
});
</script>

<?php include('includes/footer.php'); ?>