<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitTicket'])) {
  $subject = trim($_POST['subject'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $priority = trim($_POST['priority'] ?? 'medium');
  $message = trim($_POST['message'] ?? '');

  $validCategories = ['order', 'payment', 'shipping', 'product', 'account', 'other'];
  $validPriorities = ['low', 'medium', 'high'];

  if (!$subject || !$category || !$message) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please fill in all required fields.'];
    header("Location: ticketCreate");
    exit;
  }

  if (!in_array($category, $validCategories) || !in_array($priority, $validPriorities)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid category or priority.'];
    header("Location: ticketCreate");
    exit;
  }

  // Generate ticket number: TKT-YYYYMMDD-XXXXX
  $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

  $conn->begin_transaction();

  try {
    // Insert ticket
    $tStmt = $conn->prepare("
            INSERT INTO support_tickets (ticketNumber, userId, subject, category, priority, status)
            VALUES (?, ?, ?, ?, ?, 'open')
        ");
    $tStmt->bind_param("sisss", $ticketNumber, $userId, $subject, $category, $priority);
    $tStmt->execute();
    $ticketId = $conn->insert_id;
    $tStmt->close();

    // Insert first message
    $mStmt = $conn->prepare("
            INSERT INTO ticket_messages (ticketId, senderRole, message)
            VALUES (?, 'customer', ?)
        ");
    $mStmt->bind_param("is", $ticketId, $message);
    $mStmt->execute();
    $mStmt->close();

    // Notify admin of new ticket
    include_once("../../app/helpers/notifications.php");
    $customerName = trim(($_SESSION['authUser']['firstName'] ?? '') . ' ' . ($_SESSION['authUser']['lastName'] ?? ''));
    notifyAdminNewTicket($conn, $ticketId, $ticketNumber, $customerName, $subject);

    $conn->commit();

    $_SESSION['flash'] = ['type' => 'success', 'message' => "Ticket <strong>$ticketNumber</strong> submitted! We'll get back to you soon."];
    header("Location: ticketView?id=$ticketId");
    exit;

  } catch (Exception $e) {
    $conn->rollback();
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to create ticket: ' . $e->getMessage()];
    header("Location: ticketCreate");
    exit;
  }
}
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>New Support Ticket</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="tickets">Support Tickets</a></li>
      <li class="breadcrumb-item active">New Ticket</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-headset me-2 text-success"></i>Submit a Support Request</h5>
          <p class="text-muted small mb-4">Describe your issue and our support team will respond as soon as possible.
          </p>

          <form method="POST" action="ticketCreate">

            <!-- Subject -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" maxlength="255"
                placeholder="Brief description of your issue" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>"
                style="border-color:#d4e8da;" required>
            </div>

            <!-- Category & Priority side by side -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select name="category" class="form-select" style="border-color:#d4e8da;" required>
                  <option value="" disabled <?= empty($_POST['category']) ? 'selected' : '' ?>>Select a category</option>
                  <?php
                  $cats = [
                    'order' => 'Order',
                    'payment' => 'Payment',
                    'shipping' => 'Shipping',
                    'product' => 'Product',
                    'account' => 'Account',
                    'other' => 'Other'
                  ];
                  foreach ($cats as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($_POST['category'] ?? '') === $val ? 'selected' : '' ?>>
                      <?= $label ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Priority</label>
                <select name="priority" class="form-select" style="border-color:#d4e8da;">
                  <?php
                  $pris = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];
                  foreach ($pris as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($_POST['priority'] ?? 'medium') === $val ? 'selected' : '' ?>>
                      <?= $label ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <!-- Message -->
            <div class="mb-4">
              <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
              <textarea name="message" class="form-control" rows="6"
                placeholder="Please describe your issue in detail. Include order numbers, product names, or any relevant information…"
                style="border-color:#d4e8da; resize:vertical;"
                required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
              <div class="form-text">Be as specific as possible so we can resolve your issue faster.</div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2">
              <button type="submit" name="submitTicket" class="btn btn-primary">
                <i class="bi bi-send me-1"></i> Submit Ticket
              </button>
              <a href="tickets" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
              </a>
            </div>

          </form>
        </div>
      </div>

      <!-- Help tips -->
      <div class="card mt-3">
        <div class="card-body py-3">
          <h6 class="mb-2"><i class="bi bi-lightbulb text-warning me-1"></i> Tips for faster resolution</h6>
          <ul class="text-muted small mb-0" style="padding-left:1.2rem;">
            <li>Include your <strong>order number</strong> if your issue is order-related.</li>
            <li>Mention the <strong>product name</strong> for product issues.</li>
            <li>For payment problems, include the <strong>reference number</strong>.</li>
            <li>Attach screenshots or photos if needed (you can add them in follow-up messages).</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>