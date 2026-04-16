<?php
// admin/orders.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Orders';
include 'includes/admin_header.php';

$msg   = '';
$error = '';

// ── UPDATE STATUS ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $oid    = (int)$_POST['order_id'];
    $status = clean($conn, $_POST['status']);
    $conn->query("UPDATE `Order` SET status='$status' WHERE order_id=$oid");
    if ($status === 'delivered') {
        $conn->query("UPDATE Payment SET payment_status='paid' WHERE order_id=$oid AND payment_method='cash'");
    }
    if ($status === 'cancelled') {
        $conn->query("UPDATE Payment SET payment_status='refunded' WHERE order_id=$oid");
    }
    $msg = 'Order #' . $oid . ' updated to ' . ucfirst(str_replace('_',' ',$status)) . '.';
}

// ── CANCEL ORDER ─────────────────────────────────────────────
if (isset($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $conn->query("UPDATE `Order` SET status='cancelled' WHERE order_id=$oid");
    $conn->query("UPDATE Payment SET payment_status='refunded' WHERE order_id=$oid");
    $msg = 'Order #' . $oid . ' has been cancelled and payment marked as refunded.';
}

// ── DELETE ORDER ─────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $oid = (int)$_GET['delete'];
    $conn->query("DELETE FROM `Order` WHERE order_id=$oid");
    $msg = 'Order #' . $oid . ' has been permanently deleted.';
}

// ── EDIT ORDER ITEMS (Bill Update) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_items'])) {
    $oid        = (int)$_POST['order_id'];
    $quantities = $_POST['qty'] ?? [];   // array: order_item_id => qty

    foreach ($quantities as $oiid => $qty) {
        $oiid = (int)$oiid;
        $qty  = (int)$qty;
        if ($qty <= 0) {
            $conn->query("DELETE FROM OrderItem WHERE order_item_id=$oiid AND order_id=$oid");
        } else {
            $conn->query("UPDATE OrderItem SET quantity=$qty WHERE order_item_id=$oiid AND order_id=$oid");
        }
    }

    // Recalculate total
    $new_total = $conn->query("SELECT SUM(quantity * unit_price) AS t FROM OrderItem WHERE order_id=$oid")->fetch_assoc()['t'] ?? 0;
    // Add delivery fee logic: free above 500
    $delivery  = $new_total >= 500 ? 0 : 60;
    $new_total += $delivery;
    $conn->query("UPDATE `Order` SET total_amount=$new_total WHERE order_id=$oid");
    $conn->query("UPDATE Payment SET amount=$new_total WHERE order_id=$oid");
    $msg = 'Order #' . $oid . ' items updated. New total: ৳' . number_format($new_total, 0);
}

// ── FILTER & FETCH ───────────────────────────────────────────
$filter = clean($conn, $_GET['status'] ?? 'all');
$where  = $filter !== 'all' ? "WHERE o.status='$filter'" : '';

$orders = $conn->query("
    SELECT o.*, c.full_name, c.phone AS cphone, p.payment_method, p.payment_status
    FROM `Order` o
    JOIN Customer c ON o.customer_id = c.customer_id
    LEFT JOIN Payment p ON o.order_id = p.order_id
    $where
    ORDER BY o.order_date DESC
");

$statuses = ['all','pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
?>

<?php if($msg): ?><div class="alert alert-success" style="margin-bottom:1rem"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger" style="margin-bottom:1rem"><?= $error ?></div><?php endif; ?>

<!-- Filter tabs -->
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem">
  <?php foreach($statuses as $s): ?>
    <a href="orders.php?status=<?= $s ?>"
       style="padding:.35rem .9rem;border-radius:50px;font-size:.82rem;font-weight:500;
              background:<?= $filter===$s?'var(--fire)':'var(--dark-3)' ?>;
              color:<?= $filter===$s?'#fff':'var(--muted)' ?>;
              border:1px solid <?= $filter===$s?'var(--fire)':'rgba(255,255,255,.08)' ?>;
              transition:all .2s">
      <?= ucfirst(str_replace('_',' ',$s)) ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="section-card" style="overflow-x:auto">
  <table class="data-table">
    <thead><tr>
      <th>#</th><th>Date</th><th>Customer</th><th>Phone</th>
      <th>Items</th><th>Total</th><th>Type</th><th>Payment</th><th>Status</th><th>Update</th><th>Actions</th>
    </tr></thead>
    <tbody>
      <?php if ($orders->num_rows === 0): ?>
        <tr><td colspan="11" style="text-align:center;color:var(--muted);padding:2rem">No orders found.</td></tr>
      <?php endif; ?>
      <?php while($o = $orders->fetch_assoc()): ?>
        <?php
        $ic = $conn->query("SELECT SUM(quantity) AS q FROM OrderItem WHERE order_id={$o['order_id']}")->fetch_assoc()['q'] ?? 0;
        ?>
        <tr>
          <td>#<?= $o['order_id'] ?></td>
          <td><?= date('d M, h:i A', strtotime($o['order_date'])) ?></td>
          <td><?= htmlspecialchars($o['full_name']) ?></td>
          <td><?= htmlspecialchars($o['cphone'] ?? '—') ?></td>
          <td><?= $ic ?> items</td>
          <td style="color:var(--gold)">৳<?= number_format($o['total_amount'],0) ?></td>
          <td><?= ucfirst(str_replace('_',' ',$o['order_type'])) ?></td>
          <td>
            <span style="font-size:.78rem"><?= ucfirst(str_replace('_',' ',$o['payment_method']??'cash')) ?></span><br>
            <span class="status-badge status-<?= $o['payment_status']??'pending' ?>" style="font-size:.7rem"><?= $o['payment_status']??'pending' ?></span>
          </td>
          <td><span class="status-badge status-<?= $o['status'] ?>"><?= str_replace('_',' ',$o['status']) ?></span></td>
          <td>
            <form method="POST" style="display:flex;gap:.4rem;align-items:center">
              <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
              <select name="status" style="background:var(--dark-3);border:1px solid rgba(255,255,255,.1);color:var(--cream);padding:.3rem .5rem;border-radius:6px;font-size:.8rem">
                <?php foreach(['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'] as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="update_status" class="btn-sm btn-fire">✓</button>
            </form>
          </td>
          <td>
            <?php if($o['status'] !== 'cancelled' && $o['status'] !== 'delivered'): ?>
              <a href="orders.php?cancel=<?= $o['order_id'] ?>"
                 class="btn-sm" style="background:rgba(255,152,0,.15);color:#ffb74d;margin-bottom:.3rem;display:block"
                 onclick="return confirm('Cancel order #<?= $o['order_id'] ?>?')">Cancel</a>
            <?php endif; ?>
            <a href="orders.php?delete=<?= $o['order_id'] ?>"
               class="btn-sm btn-delete"
               onclick="return confirm('Permanently DELETE order #<?= $o['order_id'] ?>? This cannot be undone.')">Delete</a>
            <?php if($o['status'] !== 'cancelled' && $o['status'] !== 'delivered'): ?>
              <a href="#edit-<?= $o['order_id'] ?>" class="btn-sm btn-edit" style="margin-top:.3rem;display:block"
                 onclick="toggleEdit(<?= $o['order_id'] ?>);return false;">Edit Items</a>
            <?php endif; ?>
          </td>
        </tr>
        <!-- Edit Items subrow -->
        <tr id="edit-<?= $o['order_id'] ?>" style="display:none;background:rgba(255,107,53,.05)">
          <td colspan="11" style="padding:.8rem 1.2rem">
            <form method="POST">
              <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
              <strong style="font-size:.85rem;display:block;margin-bottom:.6rem">✏️ Edit Order Items</strong>
              <?php
              $edit_items = $conn->query("SELECT oi.order_item_id, oi.quantity, oi.unit_price, mi.name FROM OrderItem oi JOIN MenuItem mi ON oi.item_id=mi.item_id WHERE oi.order_id={$o['order_id']}");
              while ($ei = $edit_items->fetch_assoc()):
              ?>
                <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.4rem;font-size:.85rem">
                  <span style="min-width:180px"><?= htmlspecialchars($ei['name']) ?></span>
                  <span style="color:var(--muted)">৳<?= number_format($ei['unit_price'],0) ?> each</span>
                  <label style="color:var(--muted);font-size:.8rem">Qty:</label>
                  <input type="number" name="qty[<?= $ei['order_item_id'] ?>]" value="<?= $ei['quantity'] ?>" min="0"
                         style="width:60px;background:var(--dark-2);border:1px solid rgba(255,255,255,.15);color:var(--cream);padding:.25rem .4rem;border-radius:6px;text-align:center">
                  <span style="color:var(--muted);font-size:.75rem">(set to 0 to remove)</span>
                </div>
              <?php endwhile; ?>
              <button type="submit" name="edit_items" class="btn-sm btn-fire" style="margin-top:.6rem">Save Changes</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>


<script>
function toggleEdit(orderId) {
    const row = document.getElementById('edit-' + orderId);
    if (row) row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>

<?php include 'includes/admin_footer.php'; ?>
