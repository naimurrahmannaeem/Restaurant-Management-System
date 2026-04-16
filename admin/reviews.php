<?php
// admin/reviews.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Reviews';
include 'includes/admin_header.php';

if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM Review WHERE review_id=".(int)$_GET['delete']);
    echo '<div class="alert alert-success">Review deleted.</div>';
}

$reviews = $conn->query("
    SELECT r.*, c.full_name, c.username, mi.name AS item_name
    FROM Review r
    JOIN Customer c  ON r.customer_id = c.customer_id
    JOIN MenuItem mi ON r.item_id     = mi.item_id
    ORDER BY r.created_at DESC
");
?>

<div class="section-card" style="overflow-x:auto">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <h3>All Reviews (<?= $reviews->num_rows ?>)</h3>
    <?php
    $avg = $conn->query("SELECT AVG(rating) AS a FROM Review")->fetch_assoc()['a'] ?? 0;
    echo "<span style='color:var(--gold);font-size:1.1rem'>⭐ " . number_format($avg,1) . " avg</span>";
    ?>
  </div>
  <table class="data-table">
    <thead><tr>
      <th>Customer</th><th>Item</th><th>Rating</th><th>Comment</th><th>Date</th><th></th>
    </tr></thead>
    <tbody>
      <?php if($reviews->num_rows===0): ?>
        <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem">No reviews yet.</td></tr>
      <?php endif; ?>
      <?php while($r=$reviews->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($r['full_name']) ?> <span style="color:var(--muted);font-size:.78rem">@<?= $r['username'] ?></span></td>
          <td><?= htmlspecialchars($r['item_name']) ?></td>
          <td>
            <span style="color:var(--gold)">
              <?= str_repeat('⭐',$r['rating']) ?>
            </span>
            <span style="color:var(--muted);font-size:.78rem">(<?= $r['rating'] ?>/5)</span>
          </td>
          <td style="max-width:260px;font-size:.85rem"><?= htmlspecialchars($r['comment']??'—') ?></td>
          <td style="color:var(--muted);font-size:.8rem"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
          <td>
            <a href="reviews.php?delete=<?= $r['review_id'] ?>" class="btn-sm btn-delete"
               onclick="return confirm('Delete this review?')">Del</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<?php include 'includes/admin_footer.php'; ?>
