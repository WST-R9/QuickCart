<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");

$userId = $_SESSION['authUser']['userId'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submitRating'])) {
    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    $message  = trim($_POST['message'] ?? '');

    $validCategories = ['order', 'payment', 'shipping', 'product', 'account', 'other'];
    $validPriorities = ['low', 'medium', 'high'];

    if (!$subject || !$category || !$message) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please fill in all required fields.'];
        header("Location: rateOrder");
        exit;
    }

    if (!in_array($category, $validCategories) || !in_array($priority, $validPriorities)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid category or priority.'];
        header("Location: rateOrder");
        exit;
    }

    // Generate ticket number: TKT-YYYYMMDD-XXXXX
    $reviewId = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    $conn->begin_transaction();

    try {
        // Insert ticket
        $tStmt = $conn->prepare("
            INSERT INTO support_tickets (ticketNumber, userId, subject, category, priority, status)
            VALUES (?, ?, ?, ?, ?, 'open')
        ");
        $tStmt->bind_param("sisss", $reviewId, $userId, $subject, $category, $priority);
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

        $conn->commit();

        $_SESSION['flash'] = ['type' => 'success', 'message' => "Review <strong>$reviewId</strong> submitted! We'll get back to you soon."];
        header("Location: orderView?id=$reviewId");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to create ticket: ' . $e->getMessage()];
        header("Location: rateOrder");
        exit;
    }
}
include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1>Product Reviews</h1>
  <nav>
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index">Home</a></li>
      <li class="breadcrumb-item"><a href="rateOrder">Product Reviews</a></li>
      <li class="breadcrumb-item active">New Review</li>
    </ol>
  </nav>
</div>

<section class="section">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-star me-2 text-success"></i>Write Your Review</h5>
          <p class="text-muted small mb-4">Share your experience with this product and help other customers make informed decisions.</p>

          <form method="POST" action="rateOrder">

          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>