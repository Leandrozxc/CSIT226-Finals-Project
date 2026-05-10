<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();
$msg = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $name  = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $type  = $_POST['usertype'] ?? 'student';
    $dept  = intval($_POST['dept_id'] ?? 0) ?: null;
    if ($name && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (FullName,Email,Password,UserType,DeptID) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssssi', $name,$email,$hash,$type,$dept);
        $stmt->execute() ? $msg = "User added successfully!" : $error = "Email already exists.";
    }
}
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    $conn->query("UPDATE users SET IsActive = NOT IsActive WHERE UserID=$tid AND UserID != {$_SESSION['user_id']}");
    header("Location: users.php"); exit();
}
if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE UserID=$did AND UserID != {$_SESSION['user_id']}");
    header("Location: users.php"); exit();
}

$users = $conn->query("SELECT u.*,d.DeptName FROM users u LEFT JOIN departments d ON u.DeptID=d.DeptID ORDER BY u.UserType,u.FullName");
$depts = $conn->query("SELECT DeptID,DeptName FROM departments WHERE IsActive=1");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Manage Users</h1>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary btn-sm">+ Add User</button>
  </div>
  <div class="page-body">
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
          <tbody>
          <?php while ($u = $users->fetch_assoc()): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:var(--space-2);">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--color-primary-light);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;">
                  <?= strtoupper(substr($u['FullName'],0,1)) ?>
                </div>
                <?= htmlspecialchars($u['FullName']) ?>
              </div>
            </td>
            <td><?= htmlspecialchars($u['Email']) ?></td>
            <td><span class="badge badge-<?= $u['UserType'] ?>"><?= ucfirst($u['UserType']) ?></span></td>
            <td><?= $u['DeptName'] ?: '—' ?></td>
            <td>
              <span class="badge" style="background:<?= $u['IsActive']?'#dcfce7':'#fee2e2' ?>;color:<?= $u['IsActive']?'#166534':'#991b1b' ?>">
                <?= $u['IsActive'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td><?= date('M d, Y', strtotime($u['CreatedAt'])) ?></td>
            <td>
              <div style="display:flex;gap:var(--space-1);">
                <a href="users.php?toggle=<?= $u['UserID'] ?>"
                   class="btn btn-sm"
                   style="background:<?= $u['IsActive']?'var(--color-warning-bg)':'var(--color-success-bg)' ?>;color:<?= $u['IsActive']?'var(--color-warning)':'var(--color-success)' ?>;"
                   onclick="return confirm('Toggle this user?')">
                  <?= $u['IsActive'] ? 'Deactivate' : 'Activate' ?>
                </a>
                <?php if ($u['UserID'] != $_SESSION['user_id']): ?>
                <a href="users.php?delete=<?= $u['UserID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user permanently?')">Delete</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div></div>

<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--color-surface);border-radius:var(--radius-xl);padding:var(--space-8);width:100%;max-width:480px;box-shadow:var(--shadow-lg);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h2 style="font-size:var(--text-lg);">Add New User</h2>
      <button onclick="document.getElementById('addModal').style.display='none'" style="font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Full Name *</label><input type="text" name="fullname" required></div>
      <div class="form-group"><label>Email *</label><input type="email" name="email" required></div>
      <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
      <div class="form-group">
        <label>Role *</label>
        <select name="usertype">
          <option value="student">Student</option>
          <option value="faculty">Faculty</option>
          <option value="employee">Employee</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Department</label>
        <select name="dept_id">
          <option value="">None</option>
          <?php $depts->data_seek(0); while($d=$depts->fetch_assoc()): ?>
          <option value="<?= $d['DeptID'] ?>"><?= htmlspecialchars($d['DeptName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary">Add User</button>
      </div>
    </form>
  </div>
</div>
</body></html>