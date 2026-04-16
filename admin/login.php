<?php
// admin/login.php
require_once __DIR__ . '/../config/db.php';
if (isAdminLoggedIn()) redirect('/restaurant/admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $r = $conn->query("SELECT * FROM Admin WHERE username='$username' LIMIT 1");
    if ($r->num_rows > 0) {
        $a = $r->fetch_assoc();
        if (password_verify($password, $a['password'])) {
            $_SESSION['admin_id']   = $a['admin_id'];
            $_SESSION['admin_name'] = $a['full_name'];
            $_SESSION['admin_role'] = $a['role'];
            redirect('/restaurant/admin/dashboard.php');
        } else {
            $error = 'Incorrect password.';
        }
    } else {
        $error = 'Admin account not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login – BiteBurst</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/restaurant/assets/css/style.css">
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;background:var(--dark)">
  <div class="form-card" style="width:100%;max-width:400px">
    <div style="text-align:center;margin-bottom:1.5rem">
      <div style="font-size:2.5rem">🔐</div>
      <h2 style="margin-top:.5rem">Admin Panel</h2>
      <p style="color:var(--muted);font-size:.88rem">BiteBurst Restaurant</p>
    </div>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="admin" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-full">Login to Dashboard</button>
    </form>
    <div style="text-align:center;margin-top:1.2rem">
      <a href="/restaurant/index.php" style="color:var(--muted);font-size:.85rem">← Back to Website</a>
    </div>
  </div>
</div>
</body>
</html>
