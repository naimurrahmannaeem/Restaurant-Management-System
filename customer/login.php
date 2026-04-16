<?php
// customer/login.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Login';
if (isCustomerLoggedIn()) redirect('/restaurant/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $r = $conn->query("SELECT * FROM Customer WHERE username='$username' OR email='$username' LIMIT 1");
    if ($r->num_rows > 0) {
        $u = $r->fetch_assoc();
        if (password_verify($password, $u['password'])) {
            $_SESSION['customer_id']   = $u['customer_id'];
            $_SESSION['customer_name'] = $u['full_name'];
            redirect('/restaurant/index.php');
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'No account found with that username.';
    }
}
?>
<?php include '../includes/header.php'; ?>
<div style="min-height:100vh;display:flex;align-items:center;padding:5rem 1.5rem">
  <div class="form-card" style="width:100%">
    <div style="text-align:center;margin-bottom:1.5rem;font-size:2.5rem">🔥</div>
    <h2 class="form-title">Welcome Back</h2>
    <p class="form-subtitle">Login to your BiteBurst account</p>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Username or Email</label>
        <input type="text" name="username" placeholder="your_username" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-full">Login →</button>
    </form>
    <div class="form-link">
      Don't have an account? <a href="register.php">Sign Up</a>
    </div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
