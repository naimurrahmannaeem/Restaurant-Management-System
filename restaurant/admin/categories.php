<?php
// admin/categories.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Categories';
include 'includes/admin_header.php';

$msg = ''; $error = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $used = $conn->query("SELECT COUNT(*) AS c FROM MenuItem WHERE category_id=$id")->fetch_assoc()['c'];
    if ($used > 0) {
        $error = "Cannot delete: $used menu item(s) use this category.";
    } else {
        $conn->query("DELETE FROM Category WHERE category_id=$id");
        $msg = 'Category deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = clean($conn, $_POST['name']);
    $icon   = clean($conn, $_POST['icon']);
    $order  = (int)$_POST['display_order'];
    $eid    = (int)($_POST['edit_id'] ?? 0);
    if (empty($name)) { $error = 'Name is required.'; }
    elseif ($eid > 0) {
        $conn->query("UPDATE Category SET name='$name', icon='$icon', display_order=$order WHERE category_id=$eid");
        $msg = 'Category updated.';
    } else {
        $conn->query("INSERT INTO Category (name,icon,display_order) VALUES ('$name','$icon',$order)");
        $msg = 'Category added.';
    }
}

$edit_cat = null;
if (isset($_GET['edit'])) $edit_cat = $conn->query("SELECT * FROM Category WHERE category_id=".(int)$_GET['edit'])->fetch_assoc();

$cats = $conn->query("SELECT c.*, COUNT(mi.item_id) AS item_count FROM Category c
    LEFT JOIN MenuItem mi ON c.category_id=mi.category_id GROUP BY c.category_id ORDER BY c.display_order");
?>

<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">
  <div class="section-card">
    <h3 style="margin-bottom:1rem">All Categories</h3>
    <table class="data-table">
      <thead><tr><th>Icon</th><th>Name</th><th>Items</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php while($c=$cats->fetch_assoc()): ?>
          <tr>
            <td style="font-size:1.5rem"><?= $c['icon'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td><?= $c['item_count'] ?></td>
            <td><?= $c['display_order'] ?></td>
            <td>
              <a href="categories.php?edit=<?= $c['category_id'] ?>" class="btn-sm btn-edit">Edit</a>
              <a href="categories.php?delete=<?= $c['category_id'] ?>" class="btn-sm btn-delete" style="margin-left:.3rem"
                 onclick="return confirm('Delete category?')">Del</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="section-card">
    <h3 style="margin-bottom:1.2rem"><?= $edit_cat ? '✏️ Edit Category' : '➕ Add Category' ?></h3>
    <form method="POST">
      <?php if($edit_cat): ?><input type="hidden" name="edit_id" value="<?= $edit_cat['category_id'] ?>"><?php endif; ?>
      <div class="form-group">
        <label>Category Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($edit_cat['name']??'') ?>" placeholder="e.g. Salads" required>
      </div>
      <div class="form-group">
        <label>Emoji Icon</label>
        <input type="text" name="icon" value="<?= htmlspecialchars($edit_cat['icon']??'🍽️') ?>" placeholder="🍽️" style="font-size:1.2rem">
      </div>
      <div class="form-group">
        <label>Display Order</label>
        <input type="number" name="display_order" value="<?= $edit_cat['display_order']??0 ?>" min="0">
      </div>
      <button type="submit" class="btn-full"><?= $edit_cat ? 'Save Changes' : 'Add Category' ?></button>
      <?php if($edit_cat): ?>
        <a href="categories.php" style="display:block;text-align:center;margin-top:.8rem;color:var(--muted);font-size:.85rem">+ Add new instead</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
