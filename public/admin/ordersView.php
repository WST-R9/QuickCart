<?php
include_once("../../app/middleware/admin.php");
include_once("../../app/config/config.php");
include_once("../../app/helpers/activityLog.php");
include_once("../../app/helpers/badges.php");
include_once("../../app/helpers/notifications.php");

if (!isset($_GET['id'])||empty($_GET['id'])) { header("Location: orders"); exit; }
$orderId = intval($_GET['id']);

if (isset($_POST['updateOrderStatus'])) {
  $newStatus = mysqli_real_escape_string($conn,$_POST['orderStatus']);
  $valid = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];
  if (!in_array($newStatus,$valid)) { $_SESSION['message']="Invalid status.";$_SESSION['code']="error";header("Location: ordersView?id=$orderId");exit; }
  mysqli_query($conn,"UPDATE orders SET status='$newStatus' WHERE orderId=$orderId");
  if ($newStatus==='confirmed') {
    $chk=mysqli_query($conn,"SELECT shippingId FROM shipping WHERE orderId=$orderId");
    if (mysqli_num_rows($chk)==0) {
      $ou=mysqli_fetch_assoc(mysqli_query($conn,"SELECT userId FROM orders WHERE orderId=$orderId"));
      $addr=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM addresses WHERE userId={$ou['userId']} AND isDefault=1 LIMIT 1"));
      if ($addr) {
        $rn=mysqli_real_escape_string($conn,$addr['recipientName']);$ph=mysqli_real_escape_string($conn,$addr['phoneNumber']);
        $st=mysqli_real_escape_string($conn,$addr['street']);$ba=mysqli_real_escape_string($conn,$addr['barangay']);
        $ci=mysqli_real_escape_string($conn,$addr['city']);$pr=mysqli_real_escape_string($conn,$addr['province']??'');
        $zi=mysqli_real_escape_string($conn,$addr['zipCode']??'');$ai=$addr['addressId'];
        mysqli_query($conn,"INSERT INTO shipping (orderId,addressId,status,recipientName,phoneNumber,street,barangay,city,province,zipCode) VALUES ($orderId,$ai,'preparing','$rn','$ph','$st','$ba','$ci','$pr','$zi')");
      }
    }
  }
  if ($newStatus==='shipped') mysqli_query($conn,"UPDATE shipping SET status='shipped',shippedAt=NOW() WHERE orderId=$orderId");
  if ($newStatus==='delivered') {
    mysqli_query($conn,"UPDATE shipping SET status='delivered',deliveredAt=NOW() WHERE orderId=$orderId");
    $pay=mysqli_fetch_assoc(mysqli_query($conn,"SELECT method,status FROM payments WHERE orderId=$orderId LIMIT 1"));
    if ($pay&&$pay['method']==='cod'&&$pay['status']==='pending') mysqli_query($conn,"UPDATE payments SET status='paid',paidAt=NOW() WHERE orderId=$orderId");
  }
  if ($newStatus==='cancelled') {
    $its=mysqli_query($conn,"SELECT productId,quantity FROM orderitems WHERE orderId=$orderId");
    while($it=mysqli_fetch_assoc($its)) if($it['productId']) mysqli_query($conn,"UPDATE products SET stock=stock+{$it['quantity']} WHERE productId={$it['productId']}");
    mysqli_query($conn,"UPDATE shipping SET status='cancelled' WHERE orderId=$orderId");
    mysqli_query($conn,"UPDATE payments SET status='cancelled' WHERE orderId=$orderId AND status='pending'");
  }
  $or=mysqli_fetch_assoc(mysqli_query($conn,"SELECT orderNumber,userId FROM orders WHERE orderId=$orderId"));
  $msgs=['confirmed'=>'Your order is confirmed!','processing'=>'Your order is being processed.','shipped'=>'Your order has been shipped!','delivered'=>'Your order has been delivered.','cancelled'=>'Your order has been cancelled.','refunded'=>'Your refund has been processed.'];
  if (isset($msgs[$newStatus])) createNotification($conn,$or['userId'],'customer','order_status',"Order ".ucfirst($newStatus),$msgs[$newStatus],$orderId,'order');
  $_SESSION['message']="Order updated to ".ucfirst($newStatus).".";$_SESSION['code']="success";
  logActivity($conn,$_SESSION['authUser']['userId']??0,'updated_order_status','orders',$orderId,$or['orderNumber'],"to $newStatus");
  header("Location: ordersView?id=$orderId");exit;
}

if (isset($_POST['updateShippingStatus'])) {
  $ns=mysqli_real_escape_string($conn,$_POST['shippingStatus']);
  $valid=['preparing','shipped','out_for_delivery','delivered','returned'];
  if (!in_array($ns,$valid)) { $_SESSION['message']="Invalid shipping status.";$_SESSION['code']="error";header("Location: ordersView?id=$orderId");exit; }
  if (in_array($ns,['shipped','out_for_delivery','delivered','returned'])) {
    $sc=mysqli_fetch_assoc(mysqli_query($conn,"SELECT courier,trackingNumber FROM shipping WHERE orderId=$orderId LIMIT 1"));
    if (empty($sc['courier'])||empty($sc['trackingNumber'])) {
      $_SESSION['message']="⚠️ Please save Courier and Tracking Number before updating shipping status.";
      $_SESSION['code']="error";header("Location: ordersView?id=$orderId");exit;
    }
  }
  $sa=$ns==='shipped'?",shippedAt=NOW()":"";
  $da=$ns==='delivered'?",deliveredAt=NOW()":"";
  mysqli_query($conn,"UPDATE shipping SET status='$ns' $sa $da WHERE orderId=$orderId");
  if ($ns==='shipped'||$ns==='out_for_delivery') mysqli_query($conn,"UPDATE orders SET status='shipped' WHERE orderId=$orderId");
  if ($ns==='delivered') {
    mysqli_query($conn,"UPDATE orders SET status='delivered' WHERE orderId=$orderId");
    $pay=mysqli_fetch_assoc(mysqli_query($conn,"SELECT method,status FROM payments WHERE orderId=$orderId LIMIT 1"));
    if ($pay&&$pay['method']==='cod'&&$pay['status']==='pending') mysqli_query($conn,"UPDATE payments SET status='paid',paidAt=NOW() WHERE orderId=$orderId");
  }
  if ($ns==='returned') {
    mysqli_query($conn,"UPDATE orders SET status='refunded' WHERE orderId=$orderId");
    mysqli_query($conn,"UPDATE refund_requests SET status='approved' WHERE orderId=$orderId AND status='pending'");
  }
  $or=mysqli_fetch_assoc(mysqli_query($conn,"SELECT orderNumber,userId FROM orders WHERE orderId=$orderId"));
  $smsgs=['shipped'=>'Your order is on its way!','out_for_delivery'=>'Your order is out for delivery!','delivered'=>'Your order has been delivered.','returned'=>'Your return has been processed.'];
  if (isset($smsgs[$ns])) createNotification($conn,$or['userId'],'customer','order_status',ucfirst(str_replace('_',' ',$ns)),$smsgs[$ns],$orderId,'order');
  $_SESSION['message']="Shipping updated to ".ucfirst(str_replace('_',' ',$ns)).".";$_SESSION['code']="success";
  logActivity($conn,$_SESSION['authUser']['userId']??0,'updated_shipping_status','orders',$orderId,$or['orderNumber'],"to $ns");
  header("Location: ordersView?id=$orderId");exit;
}

if (isset($_POST['updateShippingDetails'])) {
  $c=mysqli_real_escape_string($conn,trim($_POST['courier']));
  $t=mysqli_real_escape_string($conn,trim($_POST['trackingNumber']));
  if (empty($c)||empty($t)) { $_SESSION['message']="Courier and Tracking Number are required.";$_SESSION['code']="error";header("Location: ordersView?id=$orderId");exit; }
  mysqli_query($conn,"UPDATE shipping SET courier='$c',trackingNumber='$t' WHERE orderId=$orderId");
  $_SESSION['message']="Shipping details saved.";$_SESSION['code']="success";
  header("Location: ordersView?id=$orderId");exit;
}

if (isset($_POST['uploadProof'])) {
  if (!empty($_FILES['proofImage']['tmp_name'])) {
    $ext=strtolower(pathinfo($_FILES['proofImage']['name'],PATHINFO_EXTENSION));
    if (!in_array($ext,['jpg','jpeg','png','webp'])) { $_SESSION['message']="Invalid file type.";$_SESSION['code']="error";header("Location: ordersView?id=$orderId");exit; }
    $fn='pod_'.$orderId.'_'.time().'.'.$ext; $dir='../uploads/proof/';
    if (!is_dir($dir)) mkdir($dir,0755,true);
    if (move_uploaded_file($_FILES['proofImage']['tmp_name'],$dir.$fn)) {
      $sf=mysqli_real_escape_string($conn,$fn);
      mysqli_query($conn,"UPDATE shipping SET proofOfDelivery='$sf' WHERE orderId=$orderId");
      $_SESSION['message']="Proof uploaded.";$_SESSION['code']="success";
    } else { $_SESSION['message']="Upload failed.";$_SESSION['code']="error"; }
  } else { $_SESSION['message']="No file selected.";$_SESSION['code']="error"; }
  header("Location: ordersView?id=$orderId");exit;
}

include('./includes/header.php');include('./includes/topbar.php');include('./includes/sidebar.php');

$or=mysqli_query($conn,"SELECT o.*,CONCAT(u.firstName,' ',u.lastName) AS customerName,u.emailAddress,u.phoneNumber FROM orders o JOIN users u ON o.userId=u.userId WHERE o.orderId=$orderId");
if (mysqli_num_rows($or)==0) { echo "<script>window.location.href='orders';</script>";exit; }
$order=mysqli_fetch_assoc($or);
$payment=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM payments WHERE orderId=$orderId LIMIT 1"));
$shipping=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM shipping WHERE orderId=$orderId LIMIT 1"));
$itemsResult=mysqli_query($conn,"SELECT * FROM orderitems WHERE orderId=$orderId");
$refundReq=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM refund_requests WHERE orderId=$orderId ORDER BY createdAt DESC LIMIT 1"));
$isCancelled=in_array($order['status'],['cancelled','refunded']);
$orderLocked=in_array($order['status'],['cancelled','refunded','delivered']);
$shippingLocked=$shipping&&in_array($shipping['status'],['delivered','returned','cancelled']);
$formLocked=$orderLocked||$shippingLocked;
?>

<div class="pagetitle">
  <h1>Order Details</h1>
  <nav><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index">Home</a></li>
    <li class="breadcrumb-item"><a href="orders">Orders</a></li>
    <li class="breadcrumb-item active"><?=htmlspecialchars($order['orderNumber'])?></li>
  </ol></nav>
</div>

<section class="section dashboard">
<div class="row">
  <div class="col-lg-8">

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Order Information</h5>
        <div class="row g-3">
          <div class="col-md-6"><p class="mb-1 text-muted small">Order Number</p><p class="fw-semibold"><?=htmlspecialchars($order['orderNumber'])?></p></div>
          <div class="col-md-6"><p class="mb-1 text-muted small">Status</p><span class="badge <?=orderBadge($order['status'])?>"><?=ucfirst($order['status'])?></span></div>
          <div class="col-md-6"><p class="mb-1 text-muted small">Total</p><p class="fw-bold text-success">₱<?=number_format($order['totalAmount'],2)?></p></div>
          <div class="col-md-6"><p class="mb-1 text-muted small">Ordered At</p><p><?=date("M d, Y h:i A",strtotime($order['orderedAt']))?></p></div>
          <?php if(!empty($order['notes'])): ?><div class="col-12"><p class="mb-1 text-muted small">Notes</p><p><?=htmlspecialchars($order['notes'])?></p></div><?php endif; ?>
        </div>
        <?php if(!in_array($order['status'],['delivered','cancelled','refunded'])): ?>
          <hr>
          <form method="POST" class="d-flex align-items-center gap-2 flex-wrap">
            <label class="fw-semibold mb-0">Update Status:</label>
            <select name="orderStatus" class="form-select form-select-sm" style="width:auto;">
              <?php foreach(['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
                <option value="<?=$s?>" <?=$order['status']===$s?'selected':''?>><?=ucfirst($s)?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="updateOrderStatus" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Update</button>
          </form>
        <?php else: ?>
          <hr><p class="text-muted small mb-0"><i class="bi bi-lock me-1"></i>Order is <strong><?=ucfirst($order['status'])?></strong> — locked.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card recent-sales overflow-auto">
      <div class="card-body">
        <h5 class="card-title">Order Items</h5>
        <table class="table table-borderless">
          <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
          <tbody>
          <?php while($item=mysqli_fetch_assoc($itemsResult)): ?>
            <tr><td><?=htmlspecialchars($item['productName'])?></td><td><?=$item['quantity']?></td><td>₱<?=number_format($item['unitPrice'],2)?></td><td>₱<?=number_format($item['quantity']*$item['unitPrice'],2)?></td></tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if($refundReq): ?>
    <div class="card border-warning">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-cash-stack me-2 text-warning"></i>Refund / Return Request</h5>
        <div class="row g-2 mb-3">
          <div class="col-md-4"><span class="text-muted small">Status</span><br>
            <?php $rb=match($refundReq['status']){'approved'=>'bg-success','rejected'=>'bg-danger',default=>'bg-warning text-dark'}; ?>
            <span class="badge <?=$rb?>"><?=ucfirst($refundReq['status'])?></span></div>
          <div class="col-md-8"><span class="text-muted small">Reason</span><br><strong><?=htmlspecialchars($refundReq['reason'])?></strong></div>
          <?php if($refundReq['details']): ?><div class="col-12"><span class="text-muted small">Details</span><br><p class="mb-0 small"><?=nl2br(htmlspecialchars($refundReq['details']))?></p></div><?php endif; ?>
          <?php if($refundReq['imageProof']): ?><div class="col-12"><span class="text-muted small">Proof</span><br><img src="../uploads/refunds/<?=htmlspecialchars($refundReq['imageProof'])?>" class="img-fluid rounded border mt-1" style="max-height:140px;"></div><?php endif; ?>
        </div>
        <a href="refundsView?id=<?=$refundReq['refundId']?>" class="btn btn-sm btn-warning"><i class="bi bi-eye me-1"></i>View &amp; Process Refund</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if($shipping&&in_array($order['status'],['delivered','refunded'])): ?>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-camera me-1"></i>Proof of Delivery</h5>
        <?php if(!empty($shipping['proofOfDelivery'])): ?>
          <img src="../uploads/proof/<?=htmlspecialchars($shipping['proofOfDelivery'])?>" class="img-fluid rounded border mb-2" style="max-height:280px;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#proofModal">
          <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
            <input type="file" name="proofImage" class="form-control form-control-sm" accept="image/*" style="width:auto;" required>
            <button type="submit" name="uploadProof" class="btn btn-outline-primary btn-sm">Replace</button>
          </form>
          <div class="modal fade" id="proofModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><img src="../uploads/proof/<?=htmlspecialchars($shipping['proofOfDelivery'])?>" class="img-fluid rounded"></div></div></div></div>
        <?php else: ?>
          <form method="POST" enctype="multipart/form-data" class="d-flex gap-2">
            <input type="file" name="proofImage" class="form-control form-control-sm" accept="image/*" style="width:auto;" required>
            <button type="submit" name="uploadProof" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload Proof</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Customer</h5>
        <p class="mb-1"><strong>Name:</strong> <?=htmlspecialchars($order['customerName'])?></p>
        <p class="mb-1"><strong>Email:</strong> <?=htmlspecialchars($order['emailAddress'])?></p>
        <p class="mb-3"><strong>Phone:</strong> <?=htmlspecialchars($order['phoneNumber'])?></p>
        <a href="customersView?id=<?=$order['userId']?>" class="btn btn-sm btn-primary"><i class="bi bi-person me-1"></i>View Customer</a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Payment</h5>
        <?php if($isCancelled): ?>
          <div class="alert alert-<?=$order['status']==='refunded'?'secondary':'warning'?> py-2 px-3 small mb-2">Order is <strong><?=ucfirst($order['status'])?></strong>.</div>
        <?php endif; ?>
        <?php if($payment): ?>
          <p class="mb-1"><strong>Method:</strong> <?=strtoupper(str_replace('_',' ',$payment['method']))?></p>
          <p class="mb-1"><strong>Status:</strong> <span class="badge <?=paymentBadge($payment['status'])?>"><?=ucfirst($payment['status'])?></span></p>
          <p class="mb-0"><strong>Amount:</strong> ₱<?=number_format($payment['amount'],2)?></p>
        <?php else: ?><p class="text-muted small mb-0">No payment record.</p><?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Shipping</h5>
        <?php if($shipping): ?>
          <?php if($isCancelled): ?><div class="alert alert-<?=$order['status']==='refunded'?'secondary':'warning'?> py-2 px-3 small mb-2">Shipment not completed.</div><?php endif; ?>
          <p class="mb-1"><strong>Status:</strong> <span class="badge <?=shippingBadge($shipping['status'])?>"><?=ucfirst(str_replace('_',' ',$shipping['status']))?></span></p>
          <p class="mb-1"><strong>Courier:</strong> <?=$shipping['courier']?htmlspecialchars($shipping['courier']):'<span class="text-danger small">Not set</span>'?></p>
          <p class="mb-2"><strong>Tracking #:</strong> <?=$shipping['trackingNumber']?htmlspecialchars($shipping['trackingNumber']):'<span class="text-danger small">Not set</span>'?></p>
          <hr>
          <p class="mb-1 small"><strong>Recipient:</strong> <?=htmlspecialchars($shipping['recipientName'])?></p>
          <p class="mb-2 small"><?=htmlspecialchars($shipping['street'].', '.$shipping['barangay'].', '.$shipping['city'])?></p>
          <?php if(!$formLocked): ?>
            <hr>
            <div class="alert alert-warning py-2 px-2 small mb-2"><i class="bi bi-exclamation-triangle me-1"></i><strong>Required:</strong> Save courier &amp; tracking before updating status.</div>
            <form method="POST" class="mb-3">
              <div class="mb-2">
                <select name="courier" class="form-select form-select-sm" required>
                  <option value="">— Select Courier —</option>
                  <?php foreach(['J&T Express','LBC','Ninja Van','GoGoExpress','Grab Express','Lalamove','In-House Delivery'] as $c): ?>
                    <option value="<?=$c?>" <?=($shipping['courier']??'')===$c?'selected':''?>><?=$c?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="input-group input-group-sm mb-2">
                <input type="text" name="trackingNumber" id="trackingInput" class="form-control" placeholder="Tracking number" value="<?=htmlspecialchars($shipping['trackingNumber']??'')?>" required>
                <button type="button" class="btn btn-outline-secondary" onclick="generateTracking()"><i class="bi bi-arrow-clockwise"></i></button>
              </div>
              <button type="submit" name="updateShippingDetails" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Save Details</button>
            </form>
            <form method="POST" class="d-flex align-items-center gap-2 flex-wrap">
              <label class="fw-semibold mb-0 small">Shipping Status:</label>
              <select name="shippingStatus" class="form-select form-select-sm" style="width:auto;">
                <?php foreach(['preparing','shipped','out_for_delivery','delivered','returned'] as $ss): ?>
                  <option value="<?=$ss?>" <?=$shipping['status']===$ss?'selected':''?>><?=ucfirst(str_replace('_',' ',$ss))?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="updateShippingStatus" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Update</button>
            </form>
          <?php else: ?>
            <p class="text-muted small mb-0"><i class="bi bi-lock me-1"></i>Shipping controls locked.</p>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No shipping record yet. Confirm the order first.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-grid"><a href="orders" class="btn btn-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a></div>
  </div>
</div>
</section>

<script>
function generateTracking() {
  const c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',d=new Date();
  let r='';for(let i=0;i<8;i++)r+=c.charAt(Math.floor(Math.random()*c.length));
  const dt=d.getFullYear()+(''+(d.getMonth()+1)).padStart(2,'0')+(''+(d.getDate())).padStart(2,'0');
  document.getElementById('trackingInput').value='TRK-'+dt+'-'+r;
}
</script>
<?php include('./includes/footer.php'); ?>
