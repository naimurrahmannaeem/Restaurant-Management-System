<?php
// customer/checkout.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Checkout';
if (!isCustomerLoggedIn()) redirect('/restaurant/customer/login.php');

$cid = $_SESSION['customer_id'];

// Get customer
$cu = $conn->query("SELECT * FROM Customer WHERE customer_id=$cid")->fetch_assoc();

// Get cart
$cr = $conn->query("SELECT cart_id FROM Cart WHERE customer_id=$cid");
if ($cr->num_rows === 0) redirect('cart.php');
$cart_id = $cr->fetch_assoc()['cart_id'];

$items_res = $conn->query("
    SELECT ci.*, mi.name, mi.price
    FROM CartItem ci
    JOIN MenuItem mi ON ci.item_id = mi.item_id
    WHERE ci.cart_id = $cart_id
");
$cart_items = [];
$subtotal   = 0;
while ($row = $items_res->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['quantity'];
}
if (empty($cart_items)) redirect('cart.php');

$delivery = $subtotal >= 500 ? 0 : 60;
$total    = $subtotal + $delivery;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address  = clean($conn, $_POST['delivery_address'] ?? '');
    $type     = clean($conn, $_POST['order_type'] ?? 'delivery');
    $method   = clean($conn, $_POST['payment_method'] ?? 'cash');
    $notes    = clean($conn, $_POST['special_notes'] ?? '');

    if (empty($address)) {
        $error = 'Please enter a delivery address.';
    } else {
        // Create order
        $conn->query("
            INSERT INTO `Order` (customer_id, restaurant_id, delivery_address, status, order_type, total_amount, special_notes)
            VALUES ($cid, 1, '$address', 'pending', '$type', $total, '$notes')
        ");
        $order_id = $conn->insert_id;

        // Order items
        foreach ($cart_items as $ci) {
            $iid = $ci['item_id'];
            $qty = $ci['quantity'];
            $up  = $ci['price'];
            $conn->query("INSERT INTO OrderItem (order_id, item_id, quantity, unit_price) VALUES ($order_id, $iid, $qty, $up)");
        }

        // Payment record
        $conn->query("
            INSERT INTO Payment (order_id, amount, payment_method, payment_status)
            VALUES ($order_id, $total, '$method', 'pending')
        ");

        // Clear cart
        $conn->query("DELETE FROM CartItem WHERE cart_id=$cart_id");

        redirect("order_success.php?id=$order_id");
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="page-wrap" style="max-width:860px">
  <h2 style="margin-bottom:1.5rem">Check<span class="text-fire">out</span></h2>

  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:2rem">
    <form method="POST">
      <div class="section-card">
        <h3 style="margin-bottom:1.2rem">📍 Delivery Details</h3>
        <div class="form-group">
          <label>Delivery Address *</label>
          <textarea name="delivery_address" rows="3" placeholder="Enter your full delivery address"><?= htmlspecialchars($cu['address'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Order Type</label>
          <select name="order_type">
            <option value="delivery">🚚 Delivery</option>
            <option value="takeaway">🏃 Takeaway</option>
            <option value="dine_in">🍽️ Dine In</option>
          </select>
        </div>
        <div class="form-group">
          <label>Special Notes</label>
          <textarea name="special_notes" rows="2" placeholder="Allergy info, special requests…"></textarea>
        </div>
      </div>

      <div class="section-card">
        <h3 style="margin-bottom:1.2rem">💳 Payment Method</h3>
        <?php foreach(['cash'=>'💵 Cash on Delivery','card'=>'💳 Credit / Debit Card','mobile_banking'=>'📱 Mobile Banking (bKash / Nagad)'] as $v=>$l): ?>
          <label style="display:flex;align-items:center;gap:.8rem;padding:.7rem;border-radius:8px;border:1px solid rgba(255,255,255,.08);margin-bottom:.6rem;cursor:pointer">
            <input type="radio" name="payment_method" value="<?= $v ?>" <?= $v==='cash'?'checked':'' ?> style="accent-color:var(--fire)">
            <?= $l ?>
          </label>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn-full">Place Order 🔥</button>
    </form>

    <!-- Order summary -->
    <div class="order-summary" style="align-self:start">
      <h3 style="margin-bottom:1rem">Your Order</h3>
      <?php foreach($cart_items as $ci): ?>
        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:.5rem">
          <span><?= htmlspecialchars($ci['name']) ?> × <?= $ci['quantity'] ?></span>
          <span style="color:var(--gold)">৳<?= number_format($ci['price']*$ci['quantity'],0) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="summary-row" style="margin-top:.8rem"><span>Subtotal</span><span>৳<?= number_format($subtotal,0) ?></span></div>
      <div class="summary-row"><span>Delivery</span><span><?= $delivery?'৳'.$delivery:'FREE' ?></span></div>
      <div class="summary-row total"><span>Total</span><span>৳<?= number_format($total,0) ?></span></div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
