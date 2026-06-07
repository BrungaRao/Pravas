<?php
$page_title = 'Register';
require_once 'includes/config.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($conn, $_POST['full_name'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $phone = sanitize($conn, $_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$full_name || !$email || !$phone || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered. Please login.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed);
            if ($stmt->execute()) {
                $success = 'Account created successfully! Please login.';
            } else {
                $error = 'Registration failed. Try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Pravas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@600;700&family=Syne:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
  <style>
    .auth-container { min-height: 100vh; display: flex; align-items: center; background: var(--gradient-dark); padding: 2rem 0; }
    .auth-box { background: var(--gradient-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 2.5rem; max-width: 520px; width: 100%; margin: 0 auto; box-shadow: var(--shadow-lg); }
  </style>
</head>
<body>
<div style="position:fixed; top:1.5rem; left:1.5rem; z-index:100;">
  <a href="<?= SITE_URL ?>/index.php" style="background:var(--bg-card); border:1px solid var(--border); color:var(--text-secondary); padding:0.5rem 1rem; border-radius:50px; font-size:0.85rem; text-decoration:none; display:flex; align-items:center; gap:6px; transition:all 0.3s;">
    <i class="fas fa-arrow-left"></i> Back to Home
  </a>
</div>

<div class="auth-container">
  <div class="container">
    <div class="auth-box mx-auto">
      <h3 style="font-family:var(--font-display); font-weight:700; margin-bottom:0.3rem;">Create Account </h3>
      <p style="color:var(--text-muted); font-size:0.87rem; margin-bottom:2rem;">Start booking today</p>

      <?php if ($error): ?>
        <div class="alert-custom alert-danger-custom mb-3"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="alert-custom alert-success-custom mb-3"><i class="fas fa-check-circle"></i> <?= $success ?>
          <a href="login.php" style="color:var(--success); font-weight:700;"> Login Now →</a>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" 
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control" 
                   pattern="[0-9]{10}" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <div class="col-12">
            <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:0.85rem;">
              <i class="fas fa-user-plus"></i> Create Account
            </button>
          </div>
        </div>
      </form>

      <div class="divider" style="margin:1.5rem 0; position:relative;">
        <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--bg-card);padding:0 0.8rem;font-size:0.8rem;color:var(--text-muted);">OR</span>
      </div>
      <p style="text-align:center; font-size:0.87rem; color:var(--text-secondary);">
        Already have an account? <a href="login.php" style="color:var(--primary); font-weight:600;">Sign In</a>
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>