<?php
// customer/register.php
require_once __DIR__ . '/../config/db.php';
$page_title = 'Register';
if (isCustomerLoggedIn()) redirect('/restaurant/index.php');

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean($conn, $_POST['full_name'] ?? '');
    $username  = clean($conn, $_POST['username']  ?? '');
    $email     = clean($conn, $_POST['email']     ?? '');
    $phone     = clean($conn, $_POST['phone']     ?? '');
    $address   = clean($conn, $_POST['address']   ?? '');
    $password  = $_POST['password']  ?? '';
    $confirm   = $_POST['confirm']   ?? '';

    if (empty($full_name)||empty($username)||empty($email)||empty($password)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $dup = $conn->query("SELECT customer_id FROM Customer WHERE username='$username' OR email='$email'");
        if ($dup->num_rows > 0) {
            $error = 'Username or email already taken.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $conn->query("INSERT INTO Customer (username,password,full_name,email,phone,address)
                          VALUES ('$username','$hashed','$full_name','$email','$phone','$address')");
            $new_id = $conn->insert_id;
            $_SESSION['customer_id']   = $new_id;
            $_SESSION['customer_name'] = $full_name;
            redirect('/restaurant/index.php');
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<div style="min-height:100vh;display:flex;align-items:center;padding:5rem 1.5rem">
  <div class="form-card" style="width:100%;max-width:520px">
    <div style="text-align:center;margin-bottom:1.5rem;font-size:2.5rem">🍔</div>
    <h2 class="form-title">Create Account</h2>
    <p class="form-subtitle">Join BiteBurst and start ordering</p>
    <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label>Full Name *</label>
          <input type="text" name="full_name" placeholder="Naimur Rahman Naim" required>
        </div>
        <div class="form-group">
          <label>Username *</label>
          <input type="text" name="username" placeholder="Naim" required>
        </div>
      </div>
      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" placeholder="naim@example.com" required>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" placeholder="+880...">
      </div>
      <div class="form-group">
        <label>Default Address</label>
        <textarea name="address" rows="2" placeholder="Your delivery address"></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" placeholder="min 6 chars" required>
        </div>
        <div class="form-group">
          <label>Confirm Password *</label>
          <input type="password" name="confirm" placeholder="repeat" required>
        </div>
      </div>
      <button type="submit" class="btn-full">Create Account 🔥</button>
    </form>
    <div class="form-link">Already have an account? <a href="login.php">Login</a></div>
  </div>
</div>
<?php include '../includes/footer.php'; ?>
