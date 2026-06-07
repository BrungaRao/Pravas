<?php
$page_title = 'Login';
require_once 'includes/config.php';

if (isLoggedIn()) redirect(SITE_URL . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['flash_message'] = 'Welcome back, ' . $user['full_name'] . '!';
            $_SESSION['flash_type'] = 'success';
            if ($user['role'] == 'admin') {
    header("Location: admin/dashboard.php");
    exit;
} else {
    header("Location: index.php");
    exit;
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
  <title>Login | Pravas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Clash+Display:wght@600;700&family=Syne:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link href="<?= SITE_URL ?>/css/style.css" rel="stylesheet">
  <style>
    .auth-container { min-height: 100vh; display: flex; align-items: center; background: var(--gradient-dark); }
    .auth-box { background: var(--gradient-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 2.5rem; max-width: 460px; width: 100%; margin: 0 auto; box-shadow: var(--shadow-lg); }
    .auth-left { background: linear-gradient(135deg, rgba(255,107,53,0.15), rgba(0,212,255,0.05)); border: 1px solid rgba(255,107,53,0.2); border-radius: var(--radius); padding: 2.5rem; display: flex; flex-direction: column; justify-content: center; }
  </style>
</head>
<body>

<!-- BACK TO HOME -->
<div style="position:fixed; top:1.5rem; left:1.5rem; z-index:100;">
  <a href="<?= SITE_URL ?>/index.php" style="
    background: var(--bg-card); border: 1px solid var(--border);
    color: var(--text-secondary); padding: 0.5rem 1rem; border-radius: 50px;
    font-size: 0.85rem; text-decoration: none; display: flex; align-items: center; gap: 6px;
    transition: all 0.3s;">
    <i class="fas fa-arrow-left"></i> Back to Home
  </a>
</div>

<div class="auth-container">
  <div class="container">
    <div class="row g-4 align-items-center justify-content-center">

      <!-- LOGIN FORM -->
      <div class="col-lg-5 col-md-8">
        <div class="auth-box">
          <h3 style="font-family:var(--font-display); font-weight:700; margin-bottom:0.3rem;">Welcome Back</h3>
          <p style="color:var(--text-muted); font-size:0.87rem; margin-bottom:2rem;">Sign in to your account to continue</p>

          <?php if ($error): ?>
            <div class="alert-custom alert-danger-custom mb-3">
              <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Email Address</label>
              <div style="position:relative;">
                <input type="email" name="email" class="form-control ps-4"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required style="padding-left:2.5rem!important;">
                <i class="fas fa-envelope" style="position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.85rem;"></i>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label">Password</label>
              <div style="position:relative;">
                <input type="password" name="password" id="password" class="form-control" 
                       required style="padding-left:2.5rem!important;">
                <i class="fas fa-lock" style="position:absolute;left:0.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:0.85rem;"></i>
                <button type="button" onclick="togglePassword()" style="position:absolute;right:0.9rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
                  <i class="fas fa-eye" id="eye-icon"></i>
                </button>
              </div>
            </div>
            <button type="submit" class="btn-primary-custom w-100 justify-content-center" style="padding:0.85rem;">
              <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
          </form>

          <div class="divider" style="margin:1.5rem 0; position:relative;">
            <span style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--bg-card);padding:0 0.8rem;font-size:0.8rem;color:var(--text-muted);">OR</span>
          </div>

          <p style="text-align:center; font-size:0.87rem; color:var(--text-secondary);">
            Don't have an account?
            <a href="register.php" style="color:var(--primary); font-weight:600;">Create Account</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword() {
  const pw = document.getElementById('password');
  const icon = document.getElementById('eye-icon');
  if (pw.type === 'password') {
    pw.type = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    pw.type = 'password';
    icon.className = 'fas fa-eye';
  }
}
</script>
</body>
</html>