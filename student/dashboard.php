<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid      = $_SESSION['user_id'];
$active   = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE RequesterID=$uid AND Status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'];
$total    = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE RequesterID=$uid")->fetch_assoc()['c'];
$resolved = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE RequesterID=$uid AND Status='Resolved'")->fetch_assoc()['c'];
$recent   = $conn->query("SELECT t.*,d.DeptName FROM tickets t JOIN departments d ON t.DeptID=d.DeptID WHERE t.RequesterID=$uid ORDER BY t.CreatedDate DESC LIMIT 5");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard – Student Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/modal.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_student.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
    <div class="topbar-actions">
      <a href="new_ticket.php" class="btn btn-primary btn-sm">+ Submit New Request</a>
    </div>
  </div>
  <div class="page-body">
    <div class="stats-grid">
      <div class="stat-card stat-total"><div class="stat-label">Total Tickets</div><div class="stat-value"><?= $total ?></div></div>
      <div class="stat-card stat-open"><div class="stat-label">Active Tickets</div><div class="stat-value"><?= $active ?></div></div>
      <div class="stat-card stat-resolved"><div class="stat-label">Resolved</div><div class="stat-value"><?= $resolved ?></div></div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title">My Recent Tickets</span>
        <a href="my_tickets.php" class="btn btn-secondary btn-sm">View All</a>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Ticket No</th><th>Title</th><th>Department</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($t = $recent->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['TicketNo']) ?></strong></td>
            <td><?= htmlspecialchars(mb_strimwidth($t['Title'],0,40,'...')) ?></td>
            <td><?= htmlspecialchars($t['DeptName']) ?></td>
            <td><span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span></td>
            <td><span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span></td>
            <td><?= date('M d, Y', strtotime($t['CreatedDate'])) ?></td>
            <td><a href="ticket_view.php?id=<?= $t['TicketID'] ?>" class="btn btn-secondary btn-sm">View</a></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div></div>
<script src="../assets/modal.js"></script>
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