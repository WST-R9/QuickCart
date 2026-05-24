<?php
include_once("../../app/middleware/user.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/notifications.php");

$userId  = $_SESSION['authUser']['userId'] ?? 0;
$orderId = (int) ($_GET['id'] ?? 0);

// Fetch order - must be delivered and belong to user
$stmt = $conn->prepare("
    SELECT o.orderId, o.orderNumber, o.totalAmount, o.status,
           p.method AS paymentMethod
    FROM orders o
    LEFT JOIN payments p ON o.orderId = p.orderId
    WHERE o.orderId = ? AND o.userId = ? AND o.status = 'delivered'
    LIMIT 1
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    $_SESSION['flash'] = ['type'=>'danger','message'=>'Order not found or not eligible for refund.'];
    header("Location: orders"); exit;
}

// Check if already submitted
$existing = $conn->prepare("SELECT refundId, status FROM refund_requests WHERE orderId = ? AND userId = ? LIMIT 1");
$existing->bind_param("ii", $orderId, $userId);
$existing->execute();
$existingReq = $existing->get_result()->fetch_assoc();
$existing->close();

// Handle submission — BEFORE HTML
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submitRefund'])) {
    $reason       = trim($_POST['reason'] ?? '');
    $details      = trim($_POST['details'] ?? '');
    $refundMethod = trim($_POST['refundMethod'] ?? 'original');

    $validReasons  = ['wrong_item','damaged','not_received','changed_mind','other'];
    $validMethods  = ['original','store_credit'];

    if (!$reason || !in_array($reason, $validReasons)) {
        $_SESSION['flash'] = ['type'=>'danger','message'=>'Please select a valid reason.'];
        header("Location: refundOrder?id=$orderId"); exit;
    }

    $imageProof = null;
    if (!empty($_FILES['imageProof']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['imageProof']['name'], PATHINFO_EXTENSION));
        if (in_array($ext,['jpg','jpeg','png','webp']) && $_FILES['imageProof']['size'] <= 5*1024*1024) {
            $dir = '../uploads/refunds/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $imageProof = 'refund_'.$userId.'_'.time().'.'.$ext;
            move_uploaded_file($_FILES['imageProof']['tmp_name'], $dir.$imageProof);
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO refund_requests (orderId, userId, reason, details, imageProof, refundMethod, status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iissss", $orderId, $userId, $reason, $details, $imageProof, $refundMethod);
    $stmt->execute();
    $refundId = $conn->insert_id;
    $stmt->close();

    // Notify admin
    createNotification($conn, null, 'admin', 'refund_submitted',
        'New Refund Request', "A refund request was submitted for order {$order['orderNumber']}.",
        $orderId, 'order');

    $_SESSION['flash'] = ['type'=>'success','message'=>'Your refund/return request has been submitted. We will review it shortly.'];
    header("Location: orderView?id=$orderId"); exit;
}

$onlinePayments = ['gcash','maya','credit_card','bank_transfer'];
$isOnline = in_array($order['paymentMethod'], $onlinePayments);

include('includes/header.php');
include('includes/sidebar.php');
include('includes/topbar.php');
?>

<div class="pagetitle">
  <h1><?=$isOnline ? 'Request Refund' : 'Request Return'?></h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item"><a href="orders">My Orders</a></li>
    <li class="breadcrumb-item"><a href="orderView?id=<?=$orderId?>">Order Details</a></li>
    <li class="breadcrumb-item active"><?=$isOnline?'Request Refund':'Request Return'?></li>
  </ol></nav>
</div>

<section class="section">
<div class="row justify-content-center">
  <div class="col-lg-7">

    <?php if ($existingReq): ?>
      <div class="card text-center">
        <div class="card-body py-5">
          <i class="bi bi-check-circle-fill text-success" style="font-size:48px;"></i>
          <h5 class="fw-bold mt-3 mb-2">Request Already Submitted</h5>
          <p class="text-muted mb-1">You have already submitted a <?=$isOnline?'refund':'return'?> request for this order.</p>
          <?php $rb=match($existingReq['status']){'approved'=>'bg-success','rejected'=>'bg-danger',default=>'bg-warning text-dark'}; ?>
          <p class="mb-4">Status: <span class="badge <?=$rb?>"><?=ucfirst($existingReq['status'])?></span></p>
          <a href="orderView?id=<?=$orderId?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Order
          </a>
        </div>
      </div>
    <?php else: ?>

      <!-- Order Summary -->
      <div class="card mb-3" style="background:#f0faf3;border:1px solid #d4e8da;">
        <div class="card-body py-3 d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-bold"><?=htmlspecialchars($order['orderNumber'])?></div>
            <div class="text-muted small">Total: ₱<?=number_format($order['totalAmount'],2)?></div>
          </div>
          <span class="badge bg-success">Delivered</span>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title">
            <i class="bi <?=$isOnline?'bi-cash-stack':'bi-box-arrow-left'?> me-2 text-warning"></i>
            <?=$isOnline?'Refund Request Form':'Return Request Form'?>
          </h5>
          <p class="text-muted small mb-4">
            <?php if ($isOnline): ?>
              Fill out this form to request a refund. Our team will review your request within 1-3 business days.
            <?php else: ?>
              Fill out this form to request a product return. Please prepare the item for pickup or drop-off.
            <?php endif; ?>
          </p>

          <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
              <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
              <select name="reason" class="form-select" style="border-color:#d4e8da;" required>
                <option value="" disabled selected>Select a reason</option>
                <option value="wrong_item">Wrong item received</option>
                <option value="damaged">Item was damaged or defective</option>
                <option value="not_received">Item not received</option>
                <option value="changed_mind">Changed my mind</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Details</label>
              <textarea name="details" class="form-control" rows="4"
                        placeholder="Describe your issue in detail…"
                        style="border-color:#d4e8da; resize:vertical;"></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Proof Image <span class="text-muted small">(optional, max 5MB)</span></label>
              <input type="file" name="imageProof" class="form-control"
                     accept="image/jpeg,image/png,image/webp" style="border-color:#d4e8da;">
              <div class="form-text">Attach a photo of the damaged/wrong item if applicable.</div>
            </div>

            <?php if ($isOnline): ?>
              <div class="mb-4">
                <label class="form-label fw-semibold">Refund Method</label>
                <div class="d-flex gap-3 flex-wrap">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="refundMethod" id="rm_original" value="original" checked>
                    <label class="form-check-label" for="rm_original">Return to original payment method</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="refundMethod" id="rm_credit" value="store_credit">
                    <label class="form-check-label" for="rm_credit">Store credit</label>
                  </div>
                </div>
              </div>
            <?php else: ?>
              <input type="hidden" name="refundMethod" value="original">
            <?php endif; ?>

            <div class="d-flex gap-2">
              <button type="submit" name="submitRefund" class="btn btn-warning fw-semibold px-4">
                <i class="bi bi-send me-1"></i>Submit Request
              </button>
              <a href="orderView?id=<?=$orderId?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

          </form>
        </div>
      </div>

    <?php endif; ?>
  </div>
</div>
</section>

<?php include('includes/footer.php'); ?>
