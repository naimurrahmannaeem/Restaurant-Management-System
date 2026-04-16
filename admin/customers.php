<?php
// admin/customers.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Customers';
include 'includes/admin_header.php';

$msg   = '';
$error = '';
$runQuery = function ($sql) use ($conn) {
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception($conn->error);
    }
    return $result;
};

// ── DELETE CUSTOMER ──────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del_id = (int)($_GET['delete'] ?? 0);
    $started_transaction = false;
    try {
        $order_stats = $runQuery("
            SELECT COUNT(*) AS total_orders,
                   COALESCE(SUM(CASE WHEN status != 'delivered' THEN 1 ELSE 0 END), 0) AS non_delivered_orders
            FROM `Order`
            WHERE customer_id=$del_id
        ")->fetch_assoc();

        $total_orders = (int)($order_stats['total_orders'] ?? 0);
        $non_delivered_orders = (int)($order_stats['non_delivered_orders'] ?? 0);

        if ($non_delivered_orders > 0) {
            $error = 'Cannot delete this customer until all orders are marked delivered.';
        } elseif (false) {
        $error = 'Cannot delete this customer — they have existing orders in the system.';
        } else {
            $conn->begin_transaction();
            $started_transaction = true;

            if ($total_orders > 0) {
                $runQuery("DELETE FROM `Order` WHERE customer_id=$del_id");
            }

            $runQuery("DELETE FROM Customer WHERE customer_id=$del_id");
            if ($conn->affected_rows < 1) {
                throw new Exception('Customer not found.');
            }

            $conn->commit();
            $msg = 'Customer deleted successfully.';
        }
    } catch (Throwable $e) {
        if ($started_transaction) {
            $conn->rollback();
        }
        $error = 'Unable to delete this customer right now. ' . $e->getMessage();
    }
}

$search = clean($conn, $_GET['q'] ?? '');
$where  = $search ? "WHERE c.username LIKE '%$search%' OR c.full_name LIKE '%$search%' OR c.email LIKE '%$search%'" : '';

$customers = $conn->query("
    SELECT c.*,
           COALESCE(os.order_count, 0) AS order_count,
           COALESCE(os.total_spent, 0) AS total_spent,
           COALESCE(os.non_delivered_orders, 0) AS non_delivered_orders
    FROM Customer c
    LEFT JOIN (
        SELECT customer_id,
               COUNT(*) AS order_count,
               SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) AS total_spent,
               SUM(CASE WHEN status != 'delivered' THEN 1 ELSE 0 END) AS non_delivered_orders
        FROM `Order`
        GROUP BY customer_id
    ) os ON c.customer_id = os.customer_id
    $where
    ORDER BY c.created_at DESC
");
?>
<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div style="display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem">
  <form method="GET" style="display:flex;gap:.6rem;flex:1;max-width:400px">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
           placeholder="Search by name, username, email…"
           style="flex:1;background:var(--dark-2);border:1px solid rgba(255,255,255,.1);color:var(--cream);padding:.55rem 1rem;border-radius:8px;font-family:'DM Sans'">
    <button type="submit" class="btn-sm btn-fire" style="padding:.55rem 1rem">Search</button>
    <?php if($search): ?><a href="customers.php" class="btn-sm" style="background:var(--dark-3);color:var(--muted);padding:.55rem .8rem">✕</a><?php endif; ?>
  </form>
  <div style="color:var(--muted);font-size:.88rem"><?= $customers->num_rows ?> customer(s)</div>
</div>

<div class="section-card" style="overflow-x:auto">
  <table class="data-table">
    <thead><tr>
      <th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th>
      <th>Orders</th><th>Total Spent</th><th>Joined</th><th>Action</th>
    </tr></thead>
    <tbody>
      <?php if($customers->num_rows===0): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">No customers found.</td></tr>
      <?php endif; ?>
      <?php while($c=$customers->fetch_assoc()): ?>
        <?php $can_delete = (int)$c['non_delivered_orders'] === 0; ?>
        <tr>
          <td><?= $c['customer_id'] ?></td>
          <td>
            <div style="width:32px;height:32px;border-radius:50%;background:var(--fire);display:inline-flex;align-items:center;justify-content:center;font-weight:600;font-size:.8rem;margin-right:.5rem">
              <?= strtoupper(substr($c['full_name'],0,1)) ?>
            </div>
            <?= htmlspecialchars($c['full_name']) ?>
          </td>
          <td style="color:var(--muted)">@<?= htmlspecialchars($c['username']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= htmlspecialchars($c['phone']??'—') ?></td>
          <td><span style="background:rgba(255,87,34,.15);color:var(--fire);padding:.2rem .6rem;border-radius:50px;font-size:.8rem"><?= $c['order_count'] ?></span></td>
          <td style="color:var(--gold)">৳<?= number_format($c['total_spent']??0,0) ?></td>
          <td style="color:var(--muted);font-size:.82rem"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
          <td>
            <a href="customers.php?delete=<?= $c['customer_id'] ?>"
               class="btn-sm btn-delete"
               onclick="return confirm('Delete <?= addslashes(htmlspecialchars($c['full_name'], ENT_QUOTES, 'UTF-8')) ?>? This will also remove any delivered order history for this customer. This cannot be undone.')"
               title="<?= !$can_delete ? 'Has non-delivered orders - cannot delete' : ((int)$c['order_count'] > 0 ? 'Delete customer and delivered order history' : 'Delete customer') ?>"
               title="<?= $c['order_count'] > 0 ? 'Has orders — cannot delete' : 'Delete customer' ?>"
               <?= !$can_delete ? 'style="opacity:.4;pointer-events:none"' : '' ?>>
              Del
            </a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/admin_footer.php'; ?>
