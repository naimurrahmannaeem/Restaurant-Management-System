<?php
// customer/order_success.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Order Placed!';
if (!isCustomerLoggedIn()) redirect('/restaurant/customer/login.php');

$order_id = (int)($_GET['id'] ?? 0);
$cid = $_SESSION['customer_id'];

$order = $conn->query("SELECT o.*, p.payment_method FROM `Order` o
    LEFT JOIN Payment p ON o.order_id = p.order_id
    WHERE o.order_id=$order_id AND o.customer_id=$cid")->fetch_assoc();

if (!$order) redirect('orders.php');
?>
<?php include '../includes/header.php'; ?>
<div class="page-wrap" style="text-align:center;max-width:600px">
  <div style="font-size:5rem;margin-bottom:1rem;animation:fadeUp .5s ease">✅</div>
  <h2 style="margin-bottom:.5rem">Order <span class="text-fire">Placed!</span></h2>
  <p style="color:var(--muted);margin-bottom:2rem">
    Your order #<?= $order_id ?> has been received. We'll start preparing it right away!
  </p>
  <div class="section-card" style="text-align:left;max-width:400px;margin:0 auto 2rem">
    <div class="summary-row"><span>Order ID</span><span>#<?= $order_id ?></span></div>
    <div class="summary-row"><span>Status</span><span class="status-badge status-pending"><?= $order['status'] ?></span></div>
    <div class="summary-row"><span>Total</span><span style="color:var(--gold)">৳<?= number_format($order['total_amount'],0) ?></span></div>
    <div class="summary-row"><span>Payment</span><span><?= ucfirst(str_replace('_',' ',$order['payment_method'])) ?></span></div>
  </div>
  <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
    <a href="orders.php" class="btn-primary">Track Order</a>
    <a href="menu.php" class="btn-secondary">Order More</a>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
