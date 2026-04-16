<?php
// customer/menu.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Menu';

$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$where = $cat_filter ? "WHERE mi.available=1 AND mi.category_id=$cat_filter" : "WHERE mi.available=1";

$items = $conn->query("
    SELECT mi.*, c.name AS cat_name, c.category_id
    FROM MenuItem mi
    JOIN Category c ON mi.category_id = c.category_id
    $where
    ORDER BY c.display_order, mi.is_featured DESC, mi.name
");

$cats = $conn->query("SELECT * FROM Category ORDER BY display_order");
?>
<?php include '../includes/header.php'; ?>

<div style="padding-top:80px;background:var(--dark-2);border-bottom:1px solid rgba(255,255,255,.06)">
  <div class="container" style="padding-top:2rem;padding-bottom:1rem">
    <h2 style="margin-bottom:.3rem">Our <span class="text-fire">Menu</span></h2>
    <p style="color:var(--muted)">Pick your favourites and add to cart</p>
  </div>

  <!-- Category filter pills -->
  <div class="container" style="padding-bottom:1rem">
    <div class="cat-scroll">
      <a href="menu.php" class="cat-pill <?= $cat_filter===0?'active':'' ?>" data-cat="all">🍽️ All</a>
      <?php while($c = $cats->fetch_assoc()): ?>
        <a href="menu.php?cat=<?= $c['category_id'] ?>"
           class="cat-pill <?= $cat_filter===$c['category_id']?'active':'' ?>"
           data-cat="<?= $c['category_id'] ?>">
          <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
        </a>
      <?php endwhile; ?>
    </div>
  </div>
</div>

<div class="page-wrap" style="padding-top:2rem">
  <?php if ($items->num_rows === 0): ?>
    <p style="text-align:center;color:var(--muted);padding:3rem">No items found in this category.</p>
  <?php else: ?>
    <div class="menu-grid">
      <?php while($item = $items->fetch_assoc()): ?>
        <div class="menu-card" data-cat="<?= $item['category_id'] ?>">
          <div class="menu-card-img" style="<?= !empty($item['image_url']) ? 'padding:0;overflow:hidden;' : '' ?>">
            <?php
            $emojis = ['','🍔','🍕','🍗','🍝','🍟','🥤','🍰'];
            $fallbackEmoji = $emojis[$item['category_id']] ?? '🍽️';
            if (!empty($item['image_url'])): ?>
              <img src="../<?= htmlspecialchars($item['image_url']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy"
                   style="width:100%;height:100%;object-fit:cover;display:block"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <span style="display:none;font-size:3rem"><?= $fallbackEmoji ?></span>
            <?php else: ?>
              <?= $fallbackEmoji ?>
            <?php endif; ?>
            <span class="badge-veg <?= $item['is_non_veg'] ? 'badge-nonveg' : '' ?>">
              <?= $item['is_non_veg'] ? 'Non-Veg' : 'Veg' ?>
            </span>
            <?php if($item['is_featured']): ?><span class="badge-featured">⭐ Hit</span><?php endif; ?>
            <?php if($item['is_spicy']): ?>
              <span style="position:absolute;bottom:8px;right:8px;font-size:1.1rem" title="Spicy">🌶️</span>
            <?php endif; ?>
          </div>
          <div class="menu-card-body">
            <div style="font-size:.75rem;color:var(--muted);margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.05em">
              <?= htmlspecialchars($item['cat_name']) ?>
            </div>
            <div class="menu-card-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="menu-card-desc"><?= htmlspecialchars($item['description'] ?? '') ?></div>
            <?php if($item['ingredients']): ?>
              <div style="font-size:.78rem;color:var(--muted);margin-bottom:.8rem">
                🥬 <?= htmlspecialchars($item['ingredients']) ?>
              </div>
            <?php endif; ?>
            <div class="menu-card-footer">
              <div class="price">৳<?= number_format($item['price'], 0) ?></div>
              <button class="add-btn" data-id="<?= $item['item_id'] ?>" title="Add to cart">+</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
