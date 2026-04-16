<?php
// index.php  –  Homepage
require_once __DIR__ . '/config/db.php';
$page_title = 'Home';

// Fetch featured items
$featured = $conn->query("
    SELECT mi.*, c.name AS cat_name
    FROM MenuItem mi
    JOIN Category c ON mi.category_id = c.category_id
    WHERE mi.is_featured = 1 AND mi.available = 1
    LIMIT 8
");

// Fetch categories
$cats = $conn->query("SELECT * FROM Category ORDER BY display_order");
?>
<?php include 'includes/header.php'; ?>

<!-- ── HERO ────────────────────────────────────────────── -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-tag">🔥 Now Open – Order Online</div>
    <h1>Taste the<br><span>Fire</span>.</h1>
    <p>Flame-grilled burgers, crispy fried chicken, wood-fired pizza & more — delivered hot to your door or ready for pickup.</p>
    <div class="hero-btns">
      <a href="customer/menu.php" class="btn-primary">Order Now 🍔</a>
      <a href="#featured" class="btn-secondary">See Menu</a>
    </div>
    <div class="hero-stats">
      <div>
        <div class="stat-num">21+</div>
        <div class="stat-label">Menu Items</div>
      </div>
      <div>
        <div class="stat-num">7</div>
        <div class="stat-label">Categories</div>
      </div>
      <div>
        <div class="stat-num">4.8★</div>
        <div class="stat-label">Rating</div>
      </div>
    </div>
  </div>
  <div class="hero-float">🔥</div>
</section>

<!-- ── CATEGORIES ───────────────────────────────────────── -->
<section class="categories">
  <div class="container">
    <div class="cat-scroll">
      <?php $cats->data_seek(0); while($c = $cats->fetch_assoc()): ?>
        <a href="customer/menu.php?cat=<?= $c['category_id'] ?>" class="cat-pill">
          <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
        </a>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- ── FEATURED ITEMS ───────────────────────────────────── -->
<section class="featured-section" id="featured">
  <div class="container">
    <div class="section-title">
      <h2>Chef's <span>Picks</span></h2>
      <p style="color:var(--muted);margin-top:.5rem">Our most loved dishes</p>
    </div>
    <div class="menu-grid">
      <?php while($item = $featured->fetch_assoc()): ?>
        <div class="menu-card" data-cat="<?= $item['category_id'] ?>">
          <div class="menu-card-img" style="<?= !empty($item['image_url']) ? 'padding:0;overflow:hidden;' : '' ?>">
            <?php if (!empty($item['image_url'])): ?>
              <img src="<?= htmlspecialchars($item['image_url']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   loading="lazy"
                   style="width:100%;height:100%;object-fit:cover;display:block"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <span style="display:none;font-size:3rem"><?php $emojis=['🍔','🍕','🍗','🍝','🍟','🥤','🍰']; echo $emojis[($item['category_id']-1)%7]; ?></span>
            <?php else: ?>
              <?php $emojis=['🍔','🍕','🍗','🍝','🍟','🥤','🍰']; echo $emojis[($item['category_id']-1)%7]; ?>
            <?php endif; ?>
            <span class="badge-veg <?= $item['is_non_veg'] ? 'badge-nonveg' : '' ?>">
              <?= $item['is_non_veg'] ? 'Non-Veg' : 'Veg' ?>
            </span>
            <span class="badge-featured">⭐ Featured</span>
          </div>
          <div class="menu-card-body">
            <div class="menu-card-name"><?= htmlspecialchars($item['name']) ?></div>
            <div class="menu-card-desc"><?= htmlspecialchars($item['description'] ?? '') ?></div>
            <div class="menu-card-footer">
              <div class="price">৳<?= number_format($item['price'], 0) ?><span> BDT</span></div>
              <button class="add-btn" data-id="<?= $item['item_id'] ?>" title="Add to cart">+</button>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <div style="text-align:center;margin-top:2.5rem">
      <a href="customer/menu.php" class="btn-primary">View Full Menu →</a>
    </div>
  </div>
</section>

<!-- ── ABOUT ────────────────────────────────────────────── -->
<section id="about" style="padding:5rem 0;background:var(--dark-2)">
  <div class="container" style="max-width:760px;text-align:center">
    <h2>About <span style="color:var(--fire)">BiteBurst</span></h2>
    <p style="color:var(--muted);margin-top:1rem;font-size:1.05rem;line-height:1.8">
      We started with one mission: serve restaurant-quality food with speed and consistency.
      Every patty is smash-cooked to order. Every pizza hand-stretched. Every wing tossed fresh.
      No shortcuts. Just fire.
    </p>
    <div style="display:flex;justify-content:center;gap:3rem;margin-top:2.5rem;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-size:2.5rem">⏱️</div>
        <div style="font-family:'Bebas Neue';font-size:1.6rem;color:var(--fire);margin-top:.4rem">30 Min</div>
        <div style="color:var(--muted);font-size:.85rem">Avg. Delivery</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:2.5rem">🌿</div>
        <div style="font-family:'Bebas Neue';font-size:1.6rem;color:var(--fire);margin-top:.4rem">Fresh</div>
        <div style="color:var(--muted);font-size:.85rem">Ingredients Daily</div>
      </div>
      <div style="text-align:center">
        <div style="font-size:2.5rem">📦</div>
        <div style="font-family:'Bebas Neue';font-size:1.6rem;color:var(--fire);margin-top:.4rem">Free</div>
        <div style="color:var(--muted);font-size:.85rem">Delivery on ৳500+</div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
