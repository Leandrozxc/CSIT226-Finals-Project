<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAdmin();
$msg = $error = '';

// Add department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $name  = trim($_POST['deptname'] ?? '');
    $email = trim($_POST['deptemail'] ?? '');
    if ($name) {
        $stmt = $conn->prepare("INSERT INTO departments (DeptName,DeptEmail) VALUES (?,?)");
        $stmt->bind_param('ss', $name, $email);
        $stmt->execute() ? $msg = "Department added!" : $error = "Failed to add department.";
    } else { $error = "Department name is required."; }
}

// Toggle active
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    $conn->query("UPDATE departments SET IsActive = NOT IsActive WHERE DeptID=$tid");
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
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Manage Departments</h1>
    <button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary btn-sm">+ Add Department</button>
  </div>
  <div class="page-body">
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

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
              <a href="departments.php?toggle=<?= $d['DeptID'] ?>"
                 class="btn btn-sm"
                 style="background:<?= $d['IsActive']?'var(--color-warning-bg)':'var(--color-success-bg)' ?>;color:<?= $d['IsActive']?'var(--color-warning)':'var(--color-success)' ?>;"
                 onclick="return confirm('Toggle this department status?')">
                <?= $d['IsActive'] ? 'Deactivate' : 'Activate' ?>
              </a>
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
<div id="addModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center;">
  <div style="background:var(--color-surface);border-radius:var(--radius-xl);padding:var(--space-8);width:100%;max-width:440px;box-shadow:var(--shadow-lg);">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h2 style="font-size:var(--text-lg);">Add New Department</h2>
      <button onclick="document.getElementById('addModal').style.display='none'" style="font-size:1.5rem;background:none;border:none;cursor:pointer;">×</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="form-group">
        <label>Department Name *</label>
        <input type="text" name="deptname" placeholder="e.g., IT Department" required>
      </div>
      <div class="form-group">
        <label>Department Email</label>
        <input type="email" name="deptemail" placeholder="dept@university.edu">
      </div>
      <div class="form-actions">
        <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>
</body></html>