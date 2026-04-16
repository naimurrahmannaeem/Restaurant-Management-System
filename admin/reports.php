<?php
// admin/reports.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Reports';
include 'includes/admin_header.php';

// Date range filter
$from = clean($conn, $_GET['from'] ?? date('Y-m-01'));
$to   = clean($conn, $_GET['to']   ?? date('Y-m-d'));

// Revenue summary
$summary = $conn->query("
    SELECT
        COUNT(*) AS total_orders,
        SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) AS revenue,
        SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END) AS delivered_rev,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
    FROM `Order`
    WHERE DATE(order_date) BETWEEN '$from' AND '$to'
")->fetch_assoc();

// Revenue by day (last 7 days in range)
$daily = $conn->query("
    SELECT DATE(order_date) AS day, COUNT(*) AS orders, SUM(total_amount) AS revenue
    FROM `Order`
    WHERE status != 'cancelled' AND DATE(order_date) BETWEEN '$from' AND '$to'
    GROUP BY DATE(order_date)
    ORDER BY day DESC
    LIMIT 14
");

// Revenue by category
$by_cat = $conn->query("
    SELECT cat.name, SUM(oi.quantity) AS sold, SUM(oi.quantity*oi.unit_price) AS revenue
    FROM OrderItem oi
    JOIN MenuItem mi  ON oi.item_id=mi.item_id
    JOIN Category cat ON mi.category_id=cat.category_id
    JOIN `Order` o    ON oi.order_id=o.order_id
    WHERE o.status != 'cancelled' AND DATE(o.order_date) BETWEEN '$from' AND '$to'
    GROUP BY cat.category_id
    ORDER BY revenue DESC
");

// Payment method breakdown
$payments = $conn->query("
    SELECT p.payment_method, COUNT(*) AS cnt, SUM(p.amount) AS total
    FROM Payment p
    JOIN `Order` o ON p.order_id=o.order_id
    WHERE o.status != 'cancelled' AND DATE(o.order_date) BETWEEN '$from' AND '$to'
    GROUP BY p.payment_method
");

// Order type breakdown
$types = $conn->query("
    SELECT order_type, COUNT(*) AS cnt, SUM(total_amount) AS total
    FROM `Order`
    WHERE status != 'cancelled' AND DATE(order_date) BETWEEN '$from' AND '$to'
    GROUP BY order_type
");
?>

<!-- Date filter -->
<form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem">
  <div style="display:flex;align-items:center;gap:.5rem">
    <label style="font-size:.85rem;color:var(--muted)">From</label>
    <input type="date" name="from" value="<?= $from ?>"
           style="background:var(--dark-2);border:1px solid rgba(255,255,255,.1);color:var(--cream);padding:.45rem .7rem;border-radius:8px">
  </div>
  <div style="display:flex;align-items:center;gap:.5rem">
    <label style="font-size:.85rem;color:var(--muted)">To</label>
    <input type="date" name="to" value="<?= $to ?>"
           style="background:var(--dark-2);border:1px solid rgba(255,255,255,.1);color:var(--cream);padding:.45rem .7rem;border-radius:8px">
  </div>
  <button type="submit" class="btn-sm btn-fire" style="padding:.5rem 1.2rem">Apply Filter</button>
  <a href="reports.php" class="btn-sm" style="background:var(--dark-3);color:var(--muted);padding:.5rem .8rem">Reset</a>
</form>

<!-- Summary cards -->
<div class="stats-grid" style="margin-bottom:1.5rem">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?= $summary['total_orders'] ?></div>
    <div class="stat-lbl">Total Orders</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-val">৳<?= number_format($summary['revenue']??0,0) ?></div>
    <div class="stat-lbl">Revenue</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">✅</div>
    <div class="stat-val">৳<?= number_format($summary['delivered_rev']??0,0) ?></div>
    <div class="stat-lbl">Delivered Rev.</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❌</div>
    <div class="stat-val" style="color:var(--danger)"><?= $summary['cancelled'] ?></div>
    <div class="stat-lbl">Cancelled</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem">

  <!-- Daily breakdown -->
  <div class="section-card">
    <h3 style="margin-bottom:1rem">Daily Revenue Breakdown</h3>
    <?php if ($daily->num_rows === 0): ?>
      <p style="color:var(--muted)">No data for this period.</p>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Date</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php while($d=$daily->fetch_assoc()): ?>
            <tr>
              <td><?= date('d M Y (D)', strtotime($d['day'])) ?></td>
              <td><?= $d['orders'] ?></td>
              <td style="color:var(--gold)">৳<?= number_format($d['revenue'],0) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div>
    <!-- By Category -->
    <div class="section-card" style="margin-bottom:1.5rem">
      <h3 style="margin-bottom:1rem">Revenue by Category</h3>
      <?php while($c=$by_cat->fetch_assoc()): ?>
        <div style="margin-bottom:.8rem">
          <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:.3rem">
            <span><?= htmlspecialchars($c['name']) ?></span>
            <span style="color:var(--gold)">৳<?= number_format($c['revenue'],0) ?></span>
          </div>
          <?php
          $max_rev = $conn->query("SELECT MAX(rev) AS m FROM (SELECT SUM(oi.quantity*oi.unit_price) AS rev FROM OrderItem oi JOIN MenuItem mi ON oi.item_id=mi.item_id GROUP BY mi.category_id) t")->fetch_assoc()['m'] ?? 1;
          $pct = $max_rev > 0 ? round(($c['revenue']/$max_rev)*100) : 0;
          ?>
          <div style="background:var(--dark-3);border-radius:50px;height:6px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:var(--fire);border-radius:50px;transition:width .5s"></div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- Payment Methods -->
    <div class="section-card">
      <h3 style="margin-bottom:1rem">Payment Methods</h3>
      <?php while($p=$payments->fetch_assoc()): ?>
        <div class="summary-row">
          <span style="text-transform:capitalize"><?= str_replace('_',' ',$p['payment_method']) ?></span>
          <span><span style="color:var(--muted);font-size:.8rem"><?= $p['cnt'] ?> orders · </span><span style="color:var(--gold)">৳<?= number_format($p['total'],0) ?></span></span>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

</div>

<?php include 'includes/admin_footer.php'; ?>
