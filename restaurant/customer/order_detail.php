<?php
// customer/order_detail.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Order Detail';
if (!isCustomerLoggedIn()) redirect('/restaurant/customer/login.php');

$order_id = (int)($_GET['id'] ?? 0);
$cid = $_SESSION['customer_id'];

$order = $conn->query("SELECT o.*, p.payment_method, p.payment_status
    FROM `Order` o LEFT JOIN Payment p ON o.order_id=p.order_id
    WHERE o.order_id=$order_id AND o.customer_id=$cid")->fetch_assoc();
if (!$order) { echo "<p style='padding:2rem'>Order not found.</p>"; exit; }

// Fetch the phone number for the restaurant handling this order.
$restaurant = $conn->query("
    SELECT name, phone
    FROM Restaurant
    WHERE restaurant_id=" . (int)$order['restaurant_id'] . "
    LIMIT 1
")->fetch_assoc();
$restaurant_name = $restaurant['name'] ?? 'the restaurant';
$restaurant_phone = trim((string)($restaurant['phone'] ?? ''));

$order_items = $conn->query("
    SELECT oi.*, mi.name, mi.description, c.category_id
    FROM OrderItem oi
    JOIN MenuItem mi ON oi.item_id=mi.item_id
    JOIN Category c ON mi.category_id=c.category_id
    WHERE oi.order_id=$order_id
");

// Handle review submit
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review'])) {
    $iid    = (int)$_POST['item_id'];
    $rating = min(5,max(1,(int)$_POST['rating']));
    $comment = clean($conn, $_POST['comment']);
    // check no duplicate
    $dup = $conn->query("SELECT review_id FROM Review WHERE customer_id=$cid AND item_id=$iid AND order_id=$order_id");
    if ($dup->num_rows === 0) {
        $conn->query("INSERT INTO Review (customer_id,item_id,order_id,rating,comment) VALUES ($cid,$iid,$order_id,$rating,'$comment')");
        $msg = 'Review submitted! Thank you 🌟';
    } else {
        $msg = 'You already reviewed this item.';
    }
}
?>
<?php include '../includes/header.php'; ?>
<div class="page-wrap" style="max-width:860px">
  <a href="orders.php" style="color:var(--muted);font-size:.9rem">← Back to Orders</a>
  <h2 style="margin:1rem 0">Order <span class="text-fire">#<?= $order_id ?></span></h2>

  <?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 300px;gap:2rem">
    <div>
      <div class="section-card">
        <h3 style="margin-bottom:1rem">Items</h3>
        <?php
        $emojis=['','🍔','🍕','🍗','🍝','🍟','🥤','🍰'];
        $order_items->data_seek(0);
        while($oi=$order_items->fetch_assoc()):
          $reviewed = $conn->query("SELECT review_id FROM Review WHERE customer_id=$cid AND item_id={$oi['item_id']} AND order_id=$order_id")->num_rows > 0;
        ?>
          <div style="display:flex;gap:1rem;align-items:flex-start;padding:.8rem 0;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="font-size:2rem"><?= $emojis[$oi['category_id']] ?? '🍽️' ?></div>
            <div style="flex:1">
              <div style="font-weight:600"><?= htmlspecialchars($oi['name']) ?></div>
              <div style="font-size:.83rem;color:var(--muted)"><?= $oi['quantity'] ?> × ৳<?= number_format($oi['unit_price'],0) ?></div>
            </div>
            <div style="font-family:'Bebas Neue';font-size:1.2rem;color:var(--gold)">
              ৳<?= number_format($oi['unit_price']*$oi['quantity'],0) ?>
            </div>
          </div>
          <?php if ($order['status']==='delivered' && !$reviewed): ?>
            <details style="margin:.5rem 0 1rem;background:var(--dark-3);border-radius:8px;padding:.8rem">
              <summary style="cursor:pointer;font-size:.88rem;color:var(--fire)">⭐ Leave a review for <?= htmlspecialchars($oi['name']) ?></summary>
              <form method="POST" style="margin-top:.8rem">
                <input type="hidden" name="item_id" value="<?= $oi['item_id'] ?>">
                <div style="margin-bottom:.6rem">
                  <label style="font-size:.83rem;color:var(--muted)">Rating</label>
                  <select name="rating" style="margin-left:.5rem;background:var(--dark-2);border:1px solid rgba(255,255,255,.1);color:var(--cream);padding:.3rem .6rem;border-radius:6px">
                    <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                    <option value="4">⭐⭐⭐⭐ Good</option>
                    <option value="3">⭐⭐⭐ Average</option>
                    <option value="2">⭐⭐ Poor</option>
                    <option value="1">⭐ Terrible</option>
                  </select>
                </div>
                <textarea name="comment" rows="2" placeholder="Your comment…" style="width:100%;background:var(--dark-2);border:1px solid rgba(255,255,255,.1);border-radius:6px;color:var(--cream);padding:.5rem;font-family:'DM Sans'"></textarea>
                <button type="submit" name="submit_review" class="btn-sm btn-fire" style="margin-top:.5rem">Submit</button>
              </form>
            </details>
          <?php endif; ?>
        <?php endwhile; ?>
      </div>
    </div>

    <div class="order-summary">
      <h3 style="margin-bottom:1rem">Summary</h3>
      <div class="summary-row"><span>Order Type</span><span><?= ucfirst(str_replace('_',' ',$order['order_type'])) ?></span></div>
      <div class="summary-row"><span>Date</span><span><?= date('d M Y', strtotime($order['order_date'])) ?></span></div>
      <div class="summary-row"><span>Payment</span><span><?= ucfirst(str_replace('_',' ',$order['payment_method']??'cash')) ?></span></div>
      <div class="summary-row"><span>Pay Status</span><span><?= ucfirst($order['payment_status']??'pending') ?></span></div>
      <div class="summary-row total"><span>Total</span><span>৳<?= number_format($order['total_amount'],0) ?></span></div>
      <div style="margin-top:1rem;text-align:center">
        <span class="status-badge status-<?= $order['status'] ?>" style="font-size:.9rem;padding:.4rem 1.2rem">
          <?= str_replace('_',' ',$order['status']) ?>
        </span>
      </div>
      <div style="margin-top:.8rem;font-size:.8rem;color:var(--muted)">📍 <?= htmlspecialchars($order['delivery_address']) ?></div>

      <?php if($order['status'] === 'pending'): ?>
      <button type="button"
              aria-expanded="false"
              aria-controls="cancelInfoBox"
              onclick="const box=document.getElementById('cancelInfoBox'); const isHidden=box.hasAttribute('hidden'); box.toggleAttribute('hidden'); this.setAttribute('aria-expanded', isHidden ? 'true' : 'false');"
              style="margin-top:1.2rem;width:100%;padding:.65rem;border-radius:8px;
                     background:rgba(244,67,54,.1);border:1px solid rgba(244,67,54,.3);
                     color:#ff8a80;font-family:'DM Sans';font-size:.88rem;cursor:pointer;
                     transition:all .2s"
              onmouseover="this.style.background='rgba(244,67,54,.2)'"
              onmouseout="this.style.background='rgba(244,67,54,.1)'">
        ✕ Want to Cancel This Order?
      </button>
      <div id="cancelInfoBox" hidden
           style="margin-top:.85rem;padding:.9rem 1rem;border-radius:12px;
                  background:rgba(255,152,0,.08);border:1px solid rgba(255,152,0,.18);
                  color:var(--cream);line-height:1.6;font-size:.85rem">
        Need to cancel? Please call
        <?php if ($restaurant_phone !== ''): ?>
          <a href="tel:<?= htmlspecialchars($restaurant_phone, ENT_QUOTES, 'UTF-8') ?>"
             style="color:#ffb74d;text-decoration:none;font-weight:700">
            <?= htmlspecialchars($restaurant_name, ENT_QUOTES, 'UTF-8') ?> at <?= htmlspecialchars($restaurant_phone, ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php else: ?>
          <strong style="color:#ffb74d"><?= htmlspecialchars($restaurant_name, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php endif; ?>
        within <strong style="color:#ff8a80">5 minutes</strong> of placing your order. Once preparation begins, cancellation may no longer be possible.
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if($order['status'] === 'pending'): ?>
<!-- ── Cancel Info Modal ── -->
<div id="cancelModal" style="
    display:none;position:fixed;inset:0;z-index:9999;
    background:rgba(0,0,0,.75);backdrop-filter:blur(6px);
    align-items:center;justify-content:center;padding:1rem"
     onclick="if(event.target===this)this.classList.remove('show')">
  <div style="
      background:var(--dark-2,#141414);border:1px solid rgba(255,255,255,.1);
      border-radius:20px;max-width:440px;width:100%;padding:2rem;
      box-shadow:0 24px 60px rgba(0,0,0,.6);
      animation:slideUp .3s ease">

    <!-- Icon -->
    <div style="text-align:center;margin-bottom:1.2rem">
      <div style="display:inline-flex;width:64px;height:64px;border-radius:50%;
                  background:rgba(255,152,0,.15);border:2px solid rgba(255,152,0,.3);
                  align-items:center;justify-content:center;font-size:1.8rem">📞</div>
    </div>

    <!-- Title -->
    <h3 style="text-align:center;margin-bottom:.5rem;font-size:1.2rem">Want to Cancel?</h3>
    <p style="text-align:center;color:var(--muted,#888);font-size:.88rem;margin-bottom:1.5rem;line-height:1.6">
      Orders cannot be cancelled online. If you placed the order by mistake,
      please <strong style="color:#ffb74d">call us immediately</strong> — within
      <strong style="color:#ff8a80">5 minutes</strong> of ordering.
    </p>

    <!-- Urgency bar -->
    <div style="background:rgba(255,152,0,.1);border:1px solid rgba(255,152,0,.25);
                border-radius:10px;padding:.8rem 1rem;margin-bottom:1.4rem;display:flex;align-items:center;gap:.8rem">
      <span style="font-size:1.3rem">⏱️</span>
      <span style="font-size:.83rem;color:#ffb74d;line-height:1.5">
        <strong>Act fast!</strong> Once the kitchen starts preparing your order,
        cancellation will not be possible.
      </span>
    </div>

    <!-- Phone -->
    <a href="tel:<?= htmlspecialchars($restaurant['phone']) ?>"
       style="display:flex;align-items:center;justify-content:center;gap:.8rem;
              background:var(--fire,#ff6b35);color:#fff;text-decoration:none;
              padding:1rem;border-radius:12px;font-size:1rem;font-weight:600;
              transition:opacity .2s;margin-bottom:.8rem"
       onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
      📞 Call <?= htmlspecialchars($restaurant['name']) ?>
      <span style="font-size:.9rem;opacity:.9"><?= htmlspecialchars($restaurant['phone']) ?></span>
    </a>

    <!-- Close -->
    <button onclick="document.getElementById('cancelModal').classList.remove('show')"
            style="width:100%;padding:.7rem;border-radius:10px;
                   background:var(--dark-3,#1e1e1e);border:1px solid rgba(255,255,255,.1);
                   color:var(--muted,#888);font-family:'DM Sans';font-size:.88rem;cursor:pointer">
      Close
    </button>
  </div>
</div>

<style>
#cancelModal.show { display:flex; }
@keyframes slideUp {
  from { opacity:0;transform:translateY(30px); }
  to   { opacity:1;transform:translateY(0); }
}
</style>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>
