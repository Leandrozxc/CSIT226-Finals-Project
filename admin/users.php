<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

// Add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $type  = $_POST['usertype'] ?? 'student';
    $dept  = intval($_POST['dept_id'] ?? 0) ?: null;
    if ($name && $email && $pass) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (FullName,Email,Password,UserType,DeptID) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssssi', $name, $email, $hash, $type, $dept);
        if ($stmt->execute()) {
            setFlash('success', 'User Added', "\"$name\" has been created successfully.");
        } else {
            setFlash('error', 'Failed', 'Email already exists or an error occurred.');
        }
    } else {
        setFlash('error', 'Validation Error', 'Full name, email, and password are all required.');
    }
    header("Location: users.php"); exit();
}

// Toggle active — POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $tid = intval($_POST['user_id'] ?? 0);
    if ($tid !== (int)$_SESSION['user_id']) {
        $conn->query("UPDATE users SET IsActive = NOT IsActive WHERE UserID=$tid");
        $u = $conn->query("SELECT FullName, IsActive FROM users WHERE UserID=$tid")->fetch_assoc();
        $newStatus = $u['IsActive'] ? 'activated' : 'deactivated';
        setFlash('success', 'Status Updated', "\"" . $u['FullName'] . "\" has been $newStatus.");
    }
    header("Location: users.php"); exit();
}

// Delete — POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $did = intval($_POST['user_id'] ?? 0);
    if ($did !== (int)$_SESSION['user_id']) {
        $u = $conn->query("SELECT FullName FROM users WHERE UserID=$did")->fetch_assoc();
        $conn->query("DELETE FROM users WHERE UserID=$did");
        setFlash('success', 'User Deleted', "\"" . ($u['FullName'] ?? 'User') . "\" has been permanently deleted.");
    }
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
<link rel="stylesheet" href="../assets/modal.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Manage Users</h1>
    <button id="openAddUserModal" class="btn btn-primary btn-sm">+ Add User</button>
  </div>
  <div class="page-body">
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
                <button type="button"
                  class="btn btn-sm toggle-user-btn"
                  data-id="<?= $u['UserID'] ?>"
                  data-name="<?= htmlspecialchars($u['FullName'], ENT_QUOTES) ?>"
                  data-active="<?= $u['IsActive'] ? '1' : '0' ?>"
                  style="background:<?= $u['IsActive']?'var(--color-warning-bg)':'var(--color-success-bg)' ?>;color:<?= $u['IsActive']?'var(--color-warning)':'var(--color-success)' ?>;">
                  <?= $u['IsActive'] ? 'Deactivate' : 'Activate' ?>
                </button>
                <?php if ($u['UserID'] != $_SESSION['user_id']): ?>
                <button type="button"
                  class="btn btn-danger btn-sm delete-user-btn"
                  data-id="<?= $u['UserID'] ?>"
                  data-name="<?= htmlspecialchars($u['FullName'], ENT_QUOTES) ?>">
                  Delete
                </button>
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

<!-- Add User Modal -->
<div id="addUserModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--color-surface);border-radius:var(--radius-xl);padding:var(--space-8);width:100%;max-width:480px;box-shadow:var(--shadow-lg);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h2 style="font-size:var(--text-lg);">Add New User</h2>
      <button id="closeAddUserModal" style="font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
    </div>
    <form id="addUserForm" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group"><label>Full Name *</label><input type="text" id="u_name" name="fullname" required></div>
      <div class="form-group"><label>Email *</label><input type="email" id="u_email" name="email" required></div>
      <div class="form-group"><label>Password *</label><input type="password" id="u_pass" name="password" required></div>
      <div class="form-group">
        <label>Role *</label>
        <select id="u_role" name="usertype">
          <option value="student">Student</option>
          <option value="faculty">Faculty</option>
          <option value="employee">Employee</option>
          <option value="staff">Staff</option>
          <option value="admin">Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Department</label>
        <select id="u_dept" name="dept_id">
          <option value="">None</option>
          <?php $depts->data_seek(0); while($d=$depts->fetch_assoc()): ?>
          <option value="<?= $d['DeptID'] ?>"><?= htmlspecialchars($d['DeptName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" id="cancelAddUserBtn" class="btn btn-secondary">Cancel</button>
        <button type="button" id="confirmAddUserBtn" class="btn btn-primary">Add User</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden action forms -->
<form id="toggleUserForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="toggle">
  <input type="hidden" name="user_id" id="toggleUserId">
</form>
<form id="deleteUserForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="user_id" id="deleteUserId">
</form>

<script src="../assets/modal.js"></script>
<script>
// ── Add User Modal ──
const addUserModal = document.getElementById('addUserModal');
document.getElementById('openAddUserModal').addEventListener('click', () => {
    addUserModal.style.display = 'flex';
});
document.getElementById('closeAddUserModal').addEventListener('click', () => {
    addUserModal.style.display = 'none';
});
document.getElementById('cancelAddUserBtn').addEventListener('click', () => {
    addUserModal.style.display = 'none';
});

document.getElementById('confirmAddUserBtn').addEventListener('click', async () => {
    const name  = document.getElementById('u_name').value.trim();
    const email = document.getElementById('u_email').value.trim();
    const pass  = document.getElementById('u_pass').value;
    const role  = document.getElementById('u_role').value;
    const dept  = document.getElementById('u_dept').options[document.getElementById('u_dept').selectedIndex].text;
    if (!name || !email || !pass) {
        await Modal.alert({ type: 'error', title: 'Validation Error', message: 'Full name, email, and password are required.' });
        return;
    }
    addUserModal.style.display = 'none';
    const rows = [
        { label: 'Full Name', value: name },
        { label: 'Email', value: email },
        { label: 'Role', value: role.charAt(0).toUpperCase() + role.slice(1) },
        { label: 'Department', value: dept !== 'None' ? dept : '—' }
    ];
    const confirmed = await Modal.review({
        title: 'Confirm New User',
        message: 'Please review the user details before saving.',
        rows,
        confirmText: 'Create User'
    });
    if (confirmed) {
        Modal.loading(document.getElementById('confirmAddUserBtn'), 'Saving...');
        document.getElementById('addUserForm').submit();
    } else {
        addUserModal.style.display = 'flex';
    }
});

// ── Toggle User Status ──
document.querySelectorAll('.toggle-user-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const name     = btn.dataset.name;
        const isActive = btn.dataset.active === '1';
        const action   = isActive ? 'deactivate' : 'activate';
        const confirmed = await Modal.confirm({
            type: isActive ? 'delete' : 'confirm',
            title: `${isActive ? 'Deactivate' : 'Activate'} User`,
            message: `Are you sure you want to ${action} "<strong>${name}</strong>"?`,
            confirmText: isActive ? 'Yes, Deactivate' : 'Yes, Activate'
        });
        if (confirmed) {
            document.getElementById('toggleUserId').value = btn.dataset.id;
            Modal.loading(btn, 'Updating...');
            document.getElementById('toggleUserForm').submit();
        }
    });
});

// ── Delete User ──
document.querySelectorAll('.delete-user-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const confirmed = await Modal.deleteConfirm({
            recordName: btn.dataset.name,
            requireTyping: false
        });
        if (confirmed) {
            document.getElementById('deleteUserId').value = btn.dataset.id;
            Modal.loading(btn, 'Deleting...');
            document.getElementById('deleteUserForm').submit();
        }
    });
});
</script>
<?php if (isset($_SESSION['flash'])): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    Modal.alert({
        type: '<?= htmlspecialchars($_SESSION['flash']['type']) ?>',
        title: '<?= htmlspecialchars($_SESSION['flash']['title']) ?>',
        message: '<?= htmlspecialchars($_SESSION['flash']['message']) ?>'
    });
});
</script>
<?php unset($_SESSION['flash']); endif; ?>
</body></html>
