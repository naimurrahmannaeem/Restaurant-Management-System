<?php
// customer/orders.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'My Orders';
if (!isCustomerLoggedIn()) redirect('/restaurant/customer/login.php');

$cid = $_SESSION['customer_id'];

$orders = $conn->query("
    SELECT o.*, p.payment_method, p.payment_status
    FROM `Order` o
    LEFT JOIN Payment p ON o.order_id = p.order_id
    WHERE o.customer_id=$cid
    ORDER BY o.order_date DESC
");
?>
<?php include '../includes/header.php'; ?>
<div class="page-wrap">
  <h2 style="margin-bottom:1.5rem">My <span class="text-fire">Orders</span></h2>

  <?php if ($orders->num_rows === 0): ?>
    <div style="text-align:center;padding:4rem 0">
      <div style="font-size:4rem">📋</div>
      <h3 style="margin:1rem 0 .5rem">No orders yet</h3>
      <p style="color:var(--muted);margin-bottom:1.5rem">Your order history will appear here.</p>
      <a href="menu.php" class="btn-primary">Start Ordering</a>
    </div>
  <?php else: ?>
    <div style="overflow-x:auto">
      <table class="orders-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total</th>
            <th>Type</th>
            <th>Payment</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php while($o = $orders->fetch_assoc()): ?>
            <?php
            $items_r = $conn->query("SELECT COUNT(*) AS cnt, SUM(quantity) AS qty FROM OrderItem WHERE order_id={$o['order_id']}");
            $ic = $items_r->fetch_assoc();
            ?>
            <tr>
              <td>#<?= $o['order_id'] ?></td>
              <td><?= date('d M Y, h:i A', strtotime($o['order_date'])) ?></td>
              <td><?= $ic['qty'] ?> items</td>
              <td style="color:var(--gold)">৳<?= number_format($o['total_amount'],0) ?></td>
              <td><?= ucfirst(str_replace('_',' ',$o['order_type'])) ?></td>
              <td><?= ucfirst(str_replace('_',' ',$o['payment_method'] ?? 'cash')) ?></td>
              <td><span class="status-badge status-<?= $o['status'] ?>"><?= str_replace('_',' ',$o['status']) ?></span></td>
              <td><a href="order_detail.php?id=<?= $o['order_id'] ?>" class="btn-sm btn-edit">View</a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>

