<?php
// admin/includes/admin_header.php
if (!isAdminLoggedIn()) redirect('/restaurant/admin/login.php');
$current = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin – <?= $page_title ?? 'Dashboard' ?> | BiteBurst</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/restaurant/assets/css/style.css">
</head>
<body>
<div class="admin-layout">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="admin-logo">🔥 BiteBurst</div>
    <nav class="admin-nav">
      <a href="dashboard.php"  class="<?= $current==='dashboard.php'  ?'active':'' ?>">📊 Dashboard</a>
      <a href="orders.php"     class="<?= $current==='orders.php'     ?'active':'' ?>">📦 Orders</a>
      <a href="menu_items.php" class="<?= $current==='menu_items.php' ?'active':'' ?>">🍔 Menu Items</a>
      <a href="categories.php" class="<?= $current==='categories.php' ?'active':'' ?>">📂 Categories</a>
      <a href="customers.php"  class="<?= $current==='customers.php'  ?'active':'' ?>">👥 Customers</a>
      <a href="reviews.php"    class="<?= $current==='reviews.php'    ?'active':'' ?>">⭐ Reviews</a>
      <a href="reports.php"    class="<?= $current==='reports.php'    ?'active':'' ?>">📈 Reports</a>
      <a href="admins.php"     class="<?= $current==='admins.php'     ?'active':'' ?>">🔑 Admins</a>
      <hr style="border-color:rgba(255,255,255,.07);margin:1rem 0">
      <a href="/restaurant/index.php" style="color:var(--muted)">🌐 View Website</a>
      <a href="logout.php"            style="color:var(--muted)">🚪 Logout</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">
    <div class="admin-header">
      <div>
        <h2 style="font-size:1.8rem"><?= $page_title ?? 'Dashboard' ?></h2>
      </div>
      <div style="font-size:.88rem;color:var(--muted)">
        👤 <?= htmlspecialchars($_SESSION['admin_name']) ?>
        <span style="margin-left:.5rem;background:rgba(255,87,34,.2);color:var(--fire);padding:.2rem .6rem;border-radius:50px;font-size:.75rem">
          <?= ucfirst($_SESSION['admin_role']) ?>
        </span>
      </div>
    </div>
