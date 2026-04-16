<?php
// admin/menu_items.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Menu Items';
include 'includes/admin_header.php';

$msg = ''; $error = '';

// ── Upload helper ────────────────────────────────────────────
function handleImageUpload($fileKey, $oldImageUrl = '') {
    if (empty($_FILES[$fileKey]['name'])) return $oldImageUrl; // no new file chosen

    $file     = $_FILES[$fileKey];
    $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
    $maxSize  = 3 * 1024 * 1024; // 3 MB

    if (!in_array($file['type'], $allowed))   return ['error' => 'Only JPG, PNG, WebP or GIF images are allowed.'];
    if ($file['size'] > $maxSize)             return ['error' => 'Image must be under 3 MB.'];
    if ($file['error'] !== UPLOAD_ERR_OK)     return ['error' => 'Upload error code: '.$file['error']];

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'item_' . time() . '_' . rand(100,999) . '.' . strtolower($ext);
    $uploadDir= __DIR__ . '/../assets/uploads/menu/';
    $destPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) return ['error' => 'Failed to save the uploaded file.'];

    // Delete old uploaded image if it was from our uploads folder
    if ($oldImageUrl && strpos($oldImageUrl, 'assets/uploads/menu/') !== false) {
        $oldPath = __DIR__ . '/../' . $oldImageUrl;
        if (file_exists($oldPath)) @unlink($oldPath);
    }

    return 'assets/uploads/menu/' . $filename;
}

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Remove uploaded image if any
    $row = $conn->query("SELECT image_url FROM MenuItem WHERE item_id=$id")->fetch_assoc();
    if ($row && strpos($row['image_url'], 'assets/uploads/menu/') !== false) {
        $oldPath = __DIR__ . '/../' . $row['image_url'];
        if (file_exists($oldPath)) @unlink($oldPath);
    }
    $conn->query("DELETE FROM MenuItem WHERE item_id=$id");
    $msg = 'Item deleted.';
}

// ── TOGGLE AVAILABILITY ──────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if (isset($_GET['set_available'])) {
        $set_val = (int)$_GET['set_available'];
        $conn->query("UPDATE MenuItem SET available = $set_val WHERE item_id=$id");
    } else {
        $conn->query("UPDATE MenuItem SET available = NOT available WHERE item_id=$id");
    }
    $msg = 'Availability toggled.';
}

// ── ADD / EDIT ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = clean($conn, $_POST['name']);
    $description  = clean($conn, $_POST['description']);
    $price        = (float)$_POST['price'];
    $category_id  = (int)$_POST['category_id'];
    $ingredients  = clean($conn, $_POST['ingredients']);
    $is_non_veg   = isset($_POST['is_non_veg'])  ? 1 : 0;
    $is_spicy     = isset($_POST['is_spicy'])     ? 1 : 0;
    $is_featured  = isset($_POST['is_featured'])  ? 1 : 0;
    $available    = isset($_POST['available'])    ? 1 : 0;
    $edit_id      = (int)($_POST['edit_id'] ?? 0);

    if (empty($name) || $price <= 0 || $category_id === 0) {
        $error = 'Name, category and a valid price are required.';
    } else {
        // Handle image
        $oldImageUrl = clean($conn, $_POST['current_image'] ?? '');
        $imageResult = handleImageUpload('item_image', $oldImageUrl);

        if (is_array($imageResult)) {
            $error = $imageResult['error'];
        } else {
            $image_url = $conn->real_escape_string($imageResult);

            if ($edit_id > 0) {
                $conn->query("UPDATE MenuItem SET
                    name='$name', description='$description', price=$price,
                    category_id=$category_id, ingredients='$ingredients',
                    is_non_veg=$is_non_veg, is_spicy=$is_spicy,
                    is_featured=$is_featured, available=$available,
                    image_url='$image_url'
                    WHERE item_id=$edit_id");
                $msg = "\"$name\" updated successfully.";
            } else {
                $conn->query("INSERT INTO MenuItem
                    (restaurant_id,category_id,name,description,price,ingredients,
                     is_non_veg,is_spicy,is_featured,available,image_url)
                    VALUES (1,$category_id,'$name','$description',$price,'$ingredients',
                    $is_non_veg,$is_spicy,$is_featured,$available,'$image_url')");
                $msg = "\"$name\" added to menu!";
            }
        }
    }
}

// ── Edit prefill ─────────────────────────────────────────────
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_item = $conn->query("SELECT * FROM MenuItem WHERE item_id=".(int)$_GET['edit'])->fetch_assoc();
}

$categories = $conn->query("SELECT * FROM Category ORDER BY display_order");
$items = $conn->query("
    SELECT mi.*, c.name AS cat_name
    FROM MenuItem mi JOIN Category c ON mi.category_id=c.category_id
    ORDER BY c.display_order, mi.name
");
?>

<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<style>
/* ── Image upload area ── */
.img-upload-box {
    border: 2px dashed var(--dark-3, #333);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color .25s, background .25s;
    position: relative;
    background: var(--dark-2, #1a1a1a);
}
.img-upload-box:hover { border-color: var(--fire, #ff6b35); background: rgba(255,107,53,.05); }
.img-upload-box input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.img-preview {
    width: 100%; max-height: 160px; object-fit: cover;
    border-radius: 8px; margin-bottom: .6rem; display: none;
}
.img-preview.visible { display: block; }
.img-placeholder { font-size: 2.5rem; margin-bottom: .3rem; }
.img-upload-label { font-size: .82rem; color: var(--muted, #888); }
.img-upload-label strong { color: var(--fire, #ff6b35); }
.img-badge {
    display: inline-block; font-size: .7rem; padding: .15rem .5rem;
    border-radius: 50px; background: rgba(76,175,80,.2); color: #81c784;
    margin-top: .4rem;
}
.img-remove-btn {
    font-size: .75rem; color: #ff8a80; cursor: pointer; margin-top: .3rem;
    background: none; border: none; text-decoration: underline;
}
.item-thumb {
    width: 44px; height: 44px; object-fit: cover;
    border-radius: 8px; vertical-align: middle; margin-right: .4rem;
}
.no-img-thumb {
    width: 44px; height: 44px; border-radius: 8px;
    background: var(--dark-3,#222); display: inline-flex;
    align-items: center; justify-content: center;
    font-size: 1.2rem; vertical-align: middle; margin-right: .4rem;
}
</style>

<div style="display:grid;grid-template-columns:1fr 400px;gap:1.5rem;align-items:start">

  <!-- ── Items table ── -->
  <div class="section-card" style="overflow-x:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <h3>All Menu Items (<?= $items->num_rows ?>)</h3>
    </div>
    <table class="data-table">
      <thead><tr>
        <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Tags</th><th>Available</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php while($item = $items->fetch_assoc()): ?>
          <tr>
            <td>
              <?php if (!empty($item['image_url'])): ?>
                <img src="<?= '../' . htmlspecialchars($item['image_url']) ?>"
                     class="item-thumb" alt="<?= htmlspecialchars($item['name']) ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'">
                <span class="no-img-thumb" style="display:none">🍽️</span>
              <?php else: ?>
                <span class="no-img-thumb">🍽️</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="font-weight:500"><?= htmlspecialchars($item['name']) ?></div>
              <div style="font-size:.75rem;color:var(--muted)"><?= htmlspecialchars(substr($item['description']??'',0,50)) ?>…</div>
            </td>
            <td><?= htmlspecialchars($item['cat_name']) ?></td>
            <td style="color:var(--gold)">৳<?= number_format($item['price'],0) ?></td>
            <td>
              <?php if($item['is_non_veg']): ?><span style="font-size:.7rem;background:rgba(244,67,54,.2);color:#ff8a80;padding:.15rem .5rem;border-radius:50px">Non-Veg</span><?php endif; ?>
              <?php if($item['is_spicy']): ?><span style="font-size:.7rem;margin-left:.2rem">🌶️</span><?php endif; ?>
              <?php if($item['is_featured']): ?><span style="font-size:.7rem;margin-left:.2rem">⭐</span><?php endif; ?>
            </td>
            <td>
              <a href="menu_items.php?toggle=<?= $item['item_id'] ?>&set_available=<?= $item['available'] ? 0 : 1 ?><?= isset($_GET['edit']) ? '&edit='.(int)$_GET['edit'] : '' ?>"
                 style="display:inline-block;width:42px;height:22px;border-radius:11px;
                        background:<?= $item['available']?'var(--success)':'var(--dark-3)' ?>;
                        position:relative;transition:background .2s;cursor:pointer"
                 title="Toggle availability">
                <span style="position:absolute;top:2px;<?= $item['available']?'right:2px':'left:2px' ?>;
                             width:18px;height:18px;border-radius:50%;background:#fff;transition:all .2s"></span>
              </a>
            </td>
            <td>
              <a href="menu_items.php?edit=<?= $item['item_id'] ?>" class="btn-sm btn-edit">Edit</a>
              <a href="menu_items.php?delete=<?= $item['item_id'] ?>"
                 class="btn-sm btn-delete" style="margin-left:.3rem"
                 onclick="return confirm('Delete this item?')">Del</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Add / Edit form ── -->
  <div class="section-card">
    <h3 style="margin-bottom:1.2rem"><?= $edit_item ? '✏️ Edit Item' : '➕ Add New Item' ?></h3>
    <form method="POST" enctype="multipart/form-data" id="itemForm">
      <?php if($edit_item): ?>
        <input type="hidden" name="edit_id" value="<?= $edit_item['item_id'] ?>">
        <input type="hidden" name="current_image" value="<?= htmlspecialchars($edit_item['image_url']??'') ?>">
      <?php endif; ?>

      <!-- ── Image Upload ── -->
      <div class="form-group">
        <label>Item Image <span style="color:var(--muted);font-size:.8rem">(JPG/PNG/WebP, max 3 MB)</span></label>

        <?php $currentImg = $edit_item['image_url'] ?? ''; ?>
        <div class="img-upload-box" id="uploadBox" onclick="document.getElementById('item_image').click()">
          <input type="file" name="item_image" id="item_image" accept="image/*"
                 style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%"
                 onchange="previewImage(this)">

          <?php if (!empty($currentImg)): ?>
            <img src="<?= '../' . htmlspecialchars($currentImg) ?>"
                 class="img-preview visible" id="imgPreview"
                 onerror="this.style.display='none'">
          <?php else: ?>
            <img src="#" class="img-preview" id="imgPreview">
          <?php endif; ?>

          <div id="imgPlaceholder" <?= !empty($currentImg) ? 'style="display:none"' : '' ?>>
            <div class="img-placeholder">📷</div>
            <div class="img-upload-label">Click to upload image<br><strong>or drag & drop here</strong></div>
          </div>

          <?php if (!empty($currentImg)): ?>
            <div class="img-badge" id="imgBadge">✅ Image uploaded</div>
          <?php else: ?>
            <div class="img-badge" id="imgBadge" style="display:none">✅ Image ready</div>
          <?php endif; ?>
        </div>

        <?php if (!empty($currentImg)): ?>
          <button type="button" class="img-remove-btn" onclick="removeImage()">✕ Remove image</button>
          <input type="hidden" name="remove_image" id="removeImageFlag" value="0">
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label>Item Name *</label>
        <input type="text" name="name" value="<?= htmlspecialchars($edit_item['name']??'') ?>" placeholder="e.g. Classic Smash Burger" required>
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select name="category_id" required>
          <option value="">— Select —</option>
          <?php $categories->data_seek(0); while($c=$categories->fetch_assoc()): ?>
            <option value="<?= $c['category_id'] ?>"
              <?= ($edit_item['category_id']??0)==$c['category_id']?'selected':'' ?>>
              <?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Price (৳) *</label>
        <input type="number" name="price" step="0.01" min="1" value="<?= $edit_item['price']??'' ?>" placeholder="320" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Short description…"><?= htmlspecialchars($edit_item['description']??'') ?></textarea>
      </div>
      <div class="form-group">
        <label>Ingredients</label>
        <input type="text" name="ingredients" value="<?= htmlspecialchars($edit_item['ingredients']??'') ?>" placeholder="Beef, cheddar, lettuce…">
      </div>

      <!-- Checkboxes -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1.2rem">
        <?php
        $checks = [
            'is_non_veg' => 'Non-Vegetarian',
            'is_spicy'   => 'Spicy 🌶️',
            'is_featured'=> 'Featured ⭐',
            'available'  => 'Available',
        ];
        foreach($checks as $field => $label):
          $checked = $edit_item ? ($edit_item[$field] ? 'checked' : '') : ($field==='available'?'checked':'');
        ?>
          <label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;cursor:pointer">
            <input type="checkbox" name="<?= $field ?>" <?= $checked ?> style="accent-color:var(--fire)">
            <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn-full"><?= $edit_item ? 'Save Changes' : 'Add Item' ?></button>
      <?php if($edit_item): ?>
        <a href="menu_items.php" style="display:block;text-align:center;margin-top:.8rem;color:var(--muted);font-size:.85rem">+ Add new item instead</a>
      <?php endif; ?>
    </form>
  </div>

</div>

<script>
function previewImage(input) {
    const preview   = document.getElementById('imgPreview');
    const placeholder = document.getElementById('imgPlaceholder');
    const badge     = document.getElementById('imgBadge');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.classList.add('visible');
            placeholder.style.display = 'none';
            badge.style.display = 'inline-block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage() {
    const preview     = document.getElementById('imgPreview');
    const placeholder = document.getElementById('imgPlaceholder');
    const badge       = document.getElementById('imgBadge');
    const flag        = document.getElementById('removeImageFlag');
    const fileInput   = document.getElementById('item_image');

    preview.src = '#';
    preview.classList.remove('visible');
    placeholder.style.display = '';
    badge.style.display = 'none';
    if (flag)  flag.value = '1';
    if (fileInput) fileInput.value = '';

    // Clear the hidden current_image so server won't keep old one
    const currentImg = document.querySelector('input[name="current_image"]');
    if (currentImg) currentImg.value = '';
}

// Drag & drop support
const box = document.getElementById('uploadBox');
if (box) {
    box.addEventListener('dragover', e => { e.preventDefault(); box.style.borderColor='var(--fire,#ff6b35)'; });
    box.addEventListener('dragleave',  () => { box.style.borderColor=''; });
    box.addEventListener('drop', e => {
        e.preventDefault();
        box.style.borderColor = '';
        const fileInput = document.getElementById('item_image');
        if (e.dataTransfer.files.length) {
            // Create a new DataTransfer to set files on the input
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            fileInput.files = dt.files;
            previewImage(fileInput);
        }
    });
}
</script>

<?php include 'includes/admin_footer.php'; ?>
