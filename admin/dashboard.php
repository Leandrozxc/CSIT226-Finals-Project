<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();

// Add department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name  = trim($_POST['deptname'] ?? '');
    $email = trim($_POST['deptemail'] ?? '');
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO departments (DeptName,DeptEmail) VALUES (?,?)");
        $stmt->bind_param('ss', $name, $email);
        if ($stmt->execute()) {
            setFlash('success', 'Department Added', "\"$name\" has been created successfully.");
        } else {
            setFlash('error', 'Failed', 'Could not add department. Please try again.');
        }
    } else {
        setFlash('error', 'Validation Error', 'Department name is required.');
    }
    header("Location: departments.php"); exit();
}

// Toggle active — now handled via POST for modal confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $tid = intval($_POST['dept_id'] ?? 0);
    $conn->query("UPDATE departments SET IsActive = NOT IsActive WHERE DeptID=$tid");
    $d = $conn->query("SELECT DeptName, IsActive FROM departments WHERE DeptID=$tid")->fetch_assoc();
    $newStatus = $d['IsActive'] ? 'activated' : 'deactivated';
    setFlash('success', 'Status Updated', "\"" . $d['DeptName'] . "\" has been $newStatus.");
    header("Location: departments.php"); exit();
}

$depts = $conn->query("SELECT d.*,COUNT(t.TicketID) as ticket_count FROM departments d LEFT JOIN tickets t ON d.DeptID=t.DeptID GROUP BY d.DeptID ORDER BY d.DeptName");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Departments</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/modal.css">
</head>
<body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Manage Departments</h1>
    <button id="openAddModal" class="btn btn-primary btn-sm">+ Add Department</button>
  </div>
  <div class="page-body">
    <div class="card">
      <div class="card-header"><span class="card-title">All Departments</span></div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Department Name</th><th>Email</th><th>Total Tickets</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
          <?php while ($d = $depts->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($d['DeptName']) ?></strong></td>
            <td><?= $d['DeptEmail'] ? htmlspecialchars($d['DeptEmail']) : '<span style="color:var(--color-text-faint)">—</span>' ?></td>
            <td><?= $d['ticket_count'] ?></td>
            <td>
              <span class="badge" style="background:<?= $d['IsActive']?'#dcfce7':'#fee2e2' ?>;color:<?= $d['IsActive']?'#166534':'#991b1b' ?>">
                <?= $d['IsActive'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td>
              <button type="button"
                class="btn btn-sm toggle-dept-btn"
                data-id="<?= $d['DeptID'] ?>"
                data-name="<?= htmlspecialchars($d['DeptName'], ENT_QUOTES) ?>"
                data-active="<?= $d['IsActive'] ? '1' : '0' ?>"
                style="background:<?= $d['IsActive']?'var(--color-warning-bg)':'var(--color-success-bg)' ?>;color:<?= $d['IsActive']?'var(--color-warning)':'var(--color-success)' ?>;">
                <?= $d['IsActive'] ? 'Deactivate' : 'Activate' ?>
              </button>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div></div>

<!-- Add Department Modal -->
<div id="addDeptModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--color-surface);border-radius:var(--radius-xl);padding:var(--space-8);width:100%;max-width:440px;box-shadow:var(--shadow-lg);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h2 style="font-size:var(--text-lg);">Add New Department</h2>
      <button id="closeAddModal" style="font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
    </div>
    <form id="addDeptForm" method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" id="deptNameInput" name="deptname" placeholder="e.g., IT Department" required>
      </div>
      <div class="form-group">
        <label>Department Email</label>
        <input type="email" id="deptEmailInput" name="deptemail" placeholder="dept@university.edu">
      </div>
      <div class="form-actions">
        <button type="button" id="cancelAddBtn" class="btn btn-secondary">Cancel</button>
        <button type="button" id="confirmAddBtn" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>

<!-- Hidden toggle form -->
<form id="toggleDeptForm" method="POST" style="display:none;">
  <input type="hidden" name="action" value="toggle">
  <input type="hidden" name="dept_id" id="toggleDeptId">
</form>

<script src="../assets/modal.js"></script>
<script>
// ── Add Department Modal ──
const addModal = document.getElementById('addDeptModal');
document.getElementById('openAddModal').addEventListener('click', () => {
    addModal.style.display = 'flex';
});
document.getElementById('closeAddModal').addEventListener('click', () => {
    addModal.style.display = 'none';
});
document.getElementById('cancelAddBtn').addEventListener('click', () => {
    addModal.style.display = 'none';
});

document.getElementById('confirmAddBtn').addEventListener('click', async () => {
    const name  = document.getElementById('deptNameInput').value.trim();
    const email = document.getElementById('deptEmailInput').value.trim();
    if (!name) {
        await Modal.alert({ type: 'error', title: 'Validation Error', message: 'Department name is required.' });
        return;
    }
    addModal.style.display = 'none';
    const rows = [{ label: 'Department Name', value: name }];
    if (email) rows.push({ label: 'Email', value: email });
    const confirmed = await Modal.review({
        title: 'Confirm New Department',
        message: 'Please review the department details before saving.',
        rows,
        confirmText: 'Save Department'
    });
    if (confirmed) {
        const btn = document.getElementById('confirmAddBtn');
        Modal.loading(btn, 'Saving...');
        document.getElementById('addDeptForm').submit();
    } else {
        addModal.style.display = 'flex';
    }
});

// ── Toggle Department Status ──
document.querySelectorAll('.toggle-dept-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const name    = btn.dataset.name;
        const isActive = btn.dataset.active === '1';
        const action  = isActive ? 'deactivate' : 'activate';
        const confirmed = await Modal.confirm({
            type: isActive ? 'delete' : 'confirm',
            title: `${isActive ? 'Deactivate' : 'Activate'} Department`,
            message: `Are you sure you want to ${action} "<strong>${name}</strong>"?`,
            confirmText: isActive ? 'Yes, Deactivate' : 'Yes, Activate'
        });
        if (confirmed) {
            document.getElementById('toggleDeptId').value = btn.dataset.id;
            Modal.loading(btn, 'Updating...');
            document.getElementById('toggleDeptForm').submit();
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
