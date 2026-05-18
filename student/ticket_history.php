<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid = $_SESSION['user_id'];

$tickets = $conn->query("SELECT t.*,d.DeptName FROM tickets t JOIN departments d ON t.DeptID=d.DeptID WHERE t.RequesterID=$uid ORDER BY t.CreatedDate DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ticket History Log</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/modal.css">  
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_student.php'; ?>
<div class="main-content">
  <div class="topbar"><h1 class="page-title">Ticket History Log</h1></div>
  <div class="page-body">
    <div class="card">
      <div class="card-header"><span class="card-title">All My Tickets</span></div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Ticket No</th><th>Title</th><th>Department</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th><th>Last Update</th><th>Action</th></tr>
          </thead>
          <tbody>
          <?php while ($t = $tickets->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['TicketNo']) ?></strong></td>
            <td><?= htmlspecialchars(mb_strimwidth($t['Title'],0,40,'...')) ?></td>
            <td><?= htmlspecialchars($t['DeptName']) ?></td>
            <td><?= htmlspecialchars($t['Category']) ?></td>
            <td><span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span></td>
            <td><span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span></td>
            <td><?= date('M d, Y', strtotime($t['CreatedDate'])) ?></td>
            <td><?= date('M d, Y', strtotime($t['UpdatedAt'])) ?></td>
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