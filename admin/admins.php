<?php
// admin/admins.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Manage Admins';
include 'includes/admin_header.php';

// Only superadmin can access
if ($_SESSION['admin_role'] !== 'superadmin') {
    echo '<div class="alert alert-danger">Access denied. Superadmin only.</div>';
    include 'includes/admin_footer.php';
    exit;
}

$msg = ''; $error = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['admin_id']) { $error = "You cannot delete yourself."; }
    else { $conn->query("DELETE FROM Admin WHERE admin_id=$id"); $msg = 'Admin removed.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = clean($conn, $_POST['username']);
    $full_name = clean($conn, $_POST['full_name']);
    $email     = clean($conn, $_POST['email']);
    $role      = clean($conn, $_POST['role']);
    $password  = $_POST['password'] ?? '';
    $eid       = (int)($_POST['edit_id'] ?? 0);

    if ($eid > 0) {
        $hashed = !empty($password) ? "password='".password_hash($password, PASSWORD_BCRYPT)."'," : '';
        $conn->query("UPDATE Admin SET $hashed username='$username', full_name='$full_name', email='$email', role='$role' WHERE admin_id=$eid");
        $msg = 'Admin updated.';
    } else {
        if (empty($password)) { $error = 'Password required for new admin.'; }
        else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $conn->query("INSERT INTO Admin (username,password,full_name,email,role) VALUES ('$username','$hashed','$full_name','$email','$role')");
            $msg = 'New admin added.';
        }
    }
}

$edit_a = null;
if (isset($_GET['edit'])) $edit_a = $conn->query("SELECT * FROM Admin WHERE admin_id=".(int)$_GET['edit'])->fetch_assoc();
$admins = $conn->query("SELECT * FROM Admin ORDER BY created_at DESC");
?>

<?php if($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start">
  <div class="section-card">
    <h3 style="margin-bottom:1rem">Admin Accounts</h3>
    <table class="data-table">
      <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody>
        <?php while($a=$admins->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($a['full_name']) ?></td>
            <td>@<?= htmlspecialchars($a['username']) ?></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td>
              <span style="padding:.2rem .7rem;border-radius:50px;font-size:.75rem;
                background:<?= $a['role']==='superadmin'?'rgba(255,87,34,.2)':($a['role']==='manager'?'rgba(255,193,7,.15)':'rgba(255,255,255,.08)') ?>;
                color:<?= $a['role']==='superadmin'?'var(--fire)':($a['role']==='manager'?'var(--gold)':'var(--muted)') ?>">
                <?= ucfirst($a['role']) ?>
              </span>
            </td>
            <td>
              <a href="admins.php?edit=<?= $a['admin_id'] ?>" class="btn-sm btn-edit">Edit</a>
              <?php if($a['admin_id'] != $_SESSION['admin_id']): ?>
                <a href="admins.php?delete=<?= $a['admin_id'] ?>" class="btn-sm btn-delete" style="margin-left:.3rem"
                   onclick="return confirm('Remove this admin?')">Del</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="section-card">
    <h3 style="margin-bottom:1.2rem"><?= $edit_a ? '✏️ Edit Admin' : '➕ Add Admin' ?></h3>
    <form method="POST">
      <?php if($edit_a): ?><input type="hidden" name="edit_id" value="<?= $edit_a['admin_id'] ?>"><?php endif; ?>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($edit_a['full_name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($edit_a['username']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($edit_a['email']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <?php foreach(['superadmin','manager','staff'] as $r): ?>
            <option value="<?= $r ?>" <?= ($edit_a['role']??'staff')===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Password <?= $edit_a?'(leave blank to keep)':'' ?></label>
        <input type="password" name="password" placeholder="min 6 chars" <?= $edit_a?'':'required' ?>>
      </div>
      <button type="submit" class="btn-full"><?= $edit_a?'Save Changes':'Add Admin' ?></button>
      <?php if($edit_a): ?>
        <a href="admins.php" style="display:block;text-align:center;margin-top:.8rem;color:var(--muted);font-size:.85rem">+ Add new instead</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include 'includes/admin_footer.php'; ?>
