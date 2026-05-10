<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
if (isLoggedIn()) {
    if (isStaff()) { header('Location: admin/dashboard.php'); }
    else { header('Location: student/dashboard.php'); }
    exit();
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $conn->prepare("SELECT UserID,FullName,Email,Password,UserType,IsActive FROM users WHERE Email=?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && $user['IsActive'] && password_verify($password, $user['Password'])) {
            $_SESSION['user_id']    = $user['UserID'];
            $_SESSION['user_name']  = $user['FullName'];
            $_SESSION['user_email'] = $user['Email'];
            $_SESSION['user_type']  = $user['UserType'];
            if (in_array($user['UserType'], ['admin','staff'])) { header('Location: admin/dashboard.php'); }
            else { header('Location: student/dashboard.php'); }
            exit();
        } else { $error = 'Invalid email or password. Please try again.'; }
    } else { $error = 'Please enter both email and password.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>University Helpdesk – Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
<div class="login-wrapper">
  <div class="login-brand">
    <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
      <rect width="52" height="52" rx="14" fill="#7B1D1D"/>
      <text x="26" y="34" text-anchor="middle" font-family="DM Serif Display,serif" font-size="22" fill="#fff" font-weight="700">HD</text>
    </svg>
    <div class="brand-text">
      <h1>University Helpdesk</h1>
      <p>Centralized Campus Support Portal</p>
    </div>
  </div>
  <div class="login-card">
    <div class="login-left">
      <div>
        <h2 style="font-family:var(--font-display);font-size:2.2rem;color:var(--color-primary);line-height:1.1;">University<br>Service<br>Helpdesk</h2>
        <p class="login-sub">Helpdesk Portal</p>
      </div>
      <div class="login-info-box">
        <p><strong>Demo Credentials:</strong></p>
        <p>Admin: <code>admin@university.edu</code></p>
        <p>Staff: <code>staff@university.edu</code></p>
        <p>Student: <code>student@university.edu</code></p>
        <p>Password: <code>password</code></p>
      </div>
    </div>
    <div class="login-right">
      <p class="login-hint">For students or applicants with existing account, you can login here.</p>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form method="POST">
        <div class="form-group">
          <label for="email">Email Address:</label>
          <input type="email" id="email" name="email" placeholder="Email Address" required>
        </div>
        <div class="form-group">
          <label for="password">Password:</label>
          <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="login-actions">
          <button type="reset" class="btn btn-secondary">CLEAR</button>
          <button type="submit" class="btn btn-primary">LOGIN</button>
        </div>
      </form>
      <p class="forgot-link">Forgot Password? <a href="#">Click here</a></p>
      <p class="contact-info">For inquiries, email us at<br><a href="mailto:helpdesk@university.edu">helpdesk@university.edu</a></p>
    </div>
  </div>
</div>
</body>
</html>