<?php
// customer/cart.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Cart';

if (!isCustomerLoggedIn()) redirect('/restaurant/customer/login.php');

$cid = $_SESSION['customer_id'];
$cr  = $conn->query("SELECT cart_id FROM Cart WHERE customer_id=$cid");

$cart_items = [];
$subtotal   = 0;
$cart_id    = 0;

if ($cr->num_rows > 0) {
    $cart_id = $cr->fetch_assoc()['cart_id'];
    $res = $conn->query("
        SELECT ci.*, mi.name, mi.price, mi.description, c.name AS cat_name, c.category_id
        FROM CartItem ci
        JOIN MenuItem mi ON ci.item_id = mi.item_id
        JOIN Category c  ON mi.category_id = c.category_id
        WHERE ci.cart_id = $cart_id
    ");
    while ($row = $res->fetch_assoc()) {
        $cart_items[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
}
$delivery = $subtotal >= 500 ? 0 : 60;
$total    = $subtotal + $delivery;
?>
<?php include '../includes/header.php'; ?>

<div class="page-wrap">
  <h2 style="margin-bottom:1.5rem">Your <span class="text-fire">Cart</span></h2>

  <?php if (empty($cart_items)): ?>
    <div style="text-align:center;padding:5rem 0">
      <div style="font-size:5rem">🛒</div>
      <h3 style="margin:1rem 0 .5rem">Your cart is empty</h3>
      <p style="color:var(--muted);margin-bottom:1.5rem">Add some delicious items from our menu!</p>
      <a href="menu.php" class="btn-primary">Browse Menu</a>
    </div>
  <?php else: ?>
    <div class="cart-grid">
      <div class="cart-items-col">
        <?php
        $emojis = ['','🍔','🍕','🍗','🍝','🍟','🥤','🍰'];
        foreach ($cart_items as $item):
        ?>
          <div class="cart-item">
            <div class="cart-item-emoji"><?= $emojis[$item['category_id']] ?? '🍽️' ?></div>
            <div class="cart-item-info">
              <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
              <div class="cart-item-price">৳<?= number_format($item['price'], 0) ?> each</div>
            </div>
            <div class="qty-ctrl">
              <button class="qty-btn" data-action="decrease" data-id="<?= $item['item_id'] ?>">−</button>
              <span class="qty-num"><?= $item['quantity'] ?></span>
              <button class="qty-btn" data-action="increase" data-id="<?= $item['item_id'] ?>">+</button>
            </div>
            <div style="min-width:80px;text-align:right;font-family:'Bebas Neue';font-size:1.2rem;color:var(--gold)">
              ৳<?= number_format($item['price'] * $item['quantity'], 0) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="order-summary">
        <h3 style="margin-bottom:1.2rem">Order Summary</h3>
        <div class="summary-row">
          <span>Subtotal</span>
          <span id="subtotal">৳<?= number_format($subtotal, 0) ?></span>
        </div>
        <div class="summary-row">
          <span>Delivery</span>
          <span><?= $delivery == 0 ? '<span style="color:var(--success)">FREE</span>' : '৳'.$delivery ?></span>
        </div>
        <?php if ($delivery > 0): ?>
          <div style="font-size:.78rem;color:var(--muted);margin-top:-.4rem;margin-bottom:.4rem">
            Add ৳<?= number_format(500 - $subtotal, 0) ?> more for free delivery
          </div>
        <?php endif; ?>
        <div class="summary-row total">
          <span>Total</span>
          <span id="total">৳<?= number_format($total, 0) ?></span>
        </div>
        <a href="checkout.php" class="btn-place-order" style="display:block;text-align:center">
          Proceed to Checkout →
        </a>
        <a href="menu.php" style="display:block;text-align:center;margin-top:1rem;font-size:.88rem;color:var(--muted)">
          + Add more items
        </a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
