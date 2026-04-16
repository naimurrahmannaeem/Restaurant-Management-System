<?php
// admin/dashboard.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Dashboard';
include 'includes/admin_header.php';

// Stats
$total_orders   = $conn->query("SELECT COUNT(*) AS c FROM `Order`")->fetch_assoc()['c'];
$today_orders   = $conn->query("SELECT COUNT(*) AS c FROM `Order` WHERE DATE(order_date)=CURDATE()")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT SUM(total_amount) AS s FROM `Order` WHERE status != 'cancelled'")->fetch_assoc()['s'] ?? 0;
$today_revenue  = $conn->query("SELECT SUM(total_amount) AS s FROM `Order` WHERE DATE(order_date)=CURDATE() AND status != 'cancelled'")->fetch_assoc()['s'] ?? 0;
$total_customers= $conn->query("SELECT COUNT(*) AS c FROM Customer")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) AS c FROM `Order` WHERE status='pending'")->fetch_assoc()['c'];
$menu_count     = $conn->query("SELECT COUNT(*) AS c FROM MenuItem WHERE available=1")->fetch_assoc()['c'];
$avg_rating     = $conn->query("SELECT AVG(rating) AS a FROM Review")->fetch_assoc()['a'] ?? 0;

// Recent orders
$recent = $conn->query("
    SELECT o.*, c.full_name, c.username
    FROM `Order` o JOIN Customer c ON o.customer_id=c.customer_id
    ORDER BY o.order_date DESC LIMIT 8
");

// Top items
$top_items = $conn->query("
    SELECT mi.name, SUM(oi.quantity) AS sold, SUM(oi.quantity*oi.unit_price) AS revenue
    FROM OrderItem oi JOIN MenuItem mi ON oi.item_id=mi.item_id
    GROUP BY oi.item_id ORDER BY sold DESC LIMIT 5
");

// Orders by status
$status_data = $conn->query("SELECT status, COUNT(*) AS cnt FROM `Order` GROUP BY status");
?>

<!-- Stats cards -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?= $total_orders ?></div>
    <div class="stat-lbl">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🗓️</div>
    <div class="stat-val"><?= $today_orders ?></div>
    <div class="stat-lbl">Today's Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-val">৳<?= number_format($total_revenue, 0) ?></div>
    <div class="stat-lbl">Total Revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📅</div>
    <div class="stat-val">৳<?= number_format($today_revenue, 0) ?></div>
    <div class="stat-lbl">Today's Revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">👥</div>
    <div class="stat-val"><?= $total_customers ?></div>
    <div class="stat-lbl">Customers</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⏳</div>
    <div class="stat-val" style="color:var(--gold)"><?= $pending_orders ?></div>
    <div class="stat-lbl">Pending Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🍔</div>
    <div class="stat-val"><?= $menu_count ?></div>
    <div class="stat-lbl">Active Items</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">⭐</div>
    <div class="stat-val"><?= number_format($avg_rating, 1) ?></div>
    <div class="stat-lbl">Avg Rating</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">

  <!-- Recent Orders -->
  <div class="section-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <h3>Recent Orders</h3>
      <a href="orders.php" class="btn-sm btn-fire">View All</a>
    </div>
    <div style="overflow-x:auto">
      <table class="data-table">
        <thead><tr>
          <th>#</th><th>Customer</th><th>Amount</th><th>Type</th><th>Status</th><th>Action</th>
        </tr></thead>
        <tbody>
          <?php while($o = $recent->fetch_assoc()): ?>
            <tr>
              <td>#<?= $o['order_id'] ?></td>
              <td><?= htmlspecialchars($o['full_name']) ?></td>
              <td style="color:var(--gold)">৳<?= number_format($o['total_amount'],0) ?></td>
              <td><?= ucfirst(str_replace('_',' ',$o['order_type'])) ?></td>
              <td><span class="status-badge status-<?= $o['status'] ?>"><?= str_replace('_',' ',$o['status']) ?></span></td>
              <td><a href="orders.php?edit=<?= $o['order_id'] ?>" class="btn-sm btn-edit">Update</a></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Items -->
  <div class="section-card">
    <h3 style="margin-bottom:1rem">🏆 Top Selling Items</h3>
    <?php while($t = $top_items->fetch_assoc()): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid rgba(255,255,255,.05)">
        <div>
          <div style="font-size:.9rem;font-weight:500"><?= htmlspecialchars($t['name']) ?></div>
          <div style="font-size:.75rem;color:var(--muted)"><?= $t['sold'] ?> sold</div>
        </div>
        <div style="color:var(--gold);font-size:.9rem;font-weight:600">৳<?= number_format($t['revenue'],0) ?></div>
      </div>
    <?php endwhile; ?>
    <?php if ($top_items->num_rows === 0): ?>
      <p style="color:var(--muted);font-size:.88rem">No sales data yet.</p>
    <?php endif; ?>
  </div>

</div>

<?php include 'includes/admin_footer.php'; ?>
