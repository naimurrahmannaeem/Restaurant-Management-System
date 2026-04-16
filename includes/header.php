<?php
// includes/header.php
$cart_count = 0;
if (isCustomerLoggedIn()) {
    $cid = $_SESSION['customer_id'];
    $r = $conn->query("SELECT SUM(quantity) AS cnt FROM CartItem ci
                        JOIN Cart c ON ci.cart_id = c.cart_id
                        WHERE c.customer_id = $cid");
    $cart_count = $r->fetch_assoc()['cnt'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BiteBurst – <?= $page_title ?? 'Restaurant' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/restaurant/assets/css/style.css">
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="nav-inner">
    <a href="/restaurant/index.php" class="logo">
      <span class="logo-icon">🔥</span>
      <span class="logo-text">BiteBurst</span>
    </a>

    <ul class="nav-links">
      <li><a href="/restaurant/index.php">Home</a></li>
      <li><a href="/restaurant/customer/menu.php">Menu</a></li>
      <li><a href="/restaurant/index.php#about">About</a></li>
    </ul>

    <div class="nav-actions">
      <a href="/restaurant/customer/cart.php" class="cart-btn">
        🛒 Cart <?= $cart_count > 0 ? "<span class='badge'>$cart_count</span>" : '' ?>
      </a>
      <?php if (isCustomerLoggedIn()): ?>
        <a href="/restaurant/customer/orders.php" class="btn-nav">My Orders</a>
        <a href="/restaurant/customer/logout.php" class="btn-nav outline">Logout</a>
      <?php else: ?>
        <a href="/restaurant/customer/login.php"    class="btn-nav outline">Login</a>
        <a href="/restaurant/customer/register.php" class="btn-nav">Sign Up</a>
      <?php endif; ?>
    </div>

    <button class="hamburger" id="hamburger">☰</button>
  </div>
</nav>
