<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireStaffOrAdmin();

$stats = [];
foreach (['Open','Assigned','In Progress','On Hold','Resolved','Closed'] as $s) {
    $r = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='$s'");
    $stats[$s] = $r->fetch_assoc()['c'];
}
$total  = array_sum($stats);
$urgent = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Priority='Urgent' AND Status NOT IN ('Resolved','Closed')")->fetch_assoc()['c'];
$recent = $conn->query("SELECT t.*,d.DeptName,u.FullName as RequesterName FROM tickets t JOIN departments d ON t.DeptID=d.DeptID JOIN users u ON t.RequesterID=u.UserID ORDER BY t.CreatedDate DESC LIMIT 8");
$dept_stats = $conn->query("SELECT d.DeptName, COUNT(t.TicketID) as total, SUM(CASE WHEN t.Status NOT IN ('Resolved','Closed') THEN 1 ELSE 0 END) as open_count FROM departments d LEFT JOIN tickets t ON d.DeptID=t.DeptID WHERE d.IsActive=1 GROUP BY d.DeptID ORDER BY total DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Admin Dashboard</h1>
    <div class="topbar-actions">
      <span style="font-size:var(--text-sm);color:var(--color-text-muted);"><?= date('F d, Y') ?></span>
    </div>
  </div>
  <div class="page-body">
    <div class="stats-grid">
      <div class="stat-card stat-total"><div class="stat-label">Total Tickets</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">All time</div></div>
      <div class="stat-card stat-open"><div class="stat-label">Open</div><div class="stat-value"><?= $stats['Open'] ?></div><div class="stat-sub">Awaiting action</div></div>
      <div class="stat-card stat-progress"><div class="stat-label">In Progress</div><div class="stat-value"><?= $stats['In Progress'] ?></div><div class="stat-sub">Being handled</div></div>
      <div class="stat-card stat-resolved"><div class="stat-label">Resolved</div><div class="stat-value"><?= $stats['Resolved'] ?></div><div class="stat-sub">Completed</div></div>
      <div class="stat-card stat-closed"><div class="stat-label">Closed</div><div class="stat-value"><?= $stats['Closed'] ?></div><div class="stat-sub">Archived</div></div>
      <div class="stat-card stat-urgent"><div class="stat-label">🔥 Urgent Active</div><div class="stat-value"><?= $urgent ?></div><div class="stat-sub">Needs priority</div></div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Recent Tickets</span>
          <a href="tickets.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Ticket No</th><th>Title</th><th>Requester</th><th>Dept</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php while ($r = $recent->fetch_assoc()): ?>
            <tr>
              <td><a href="ticket_detail.php?id=<?= $r['TicketID'] ?>"><?= htmlspecialchars($r['TicketNo']) ?></a></td>
              <td><?= htmlspecialchars(mb_strimwidth($r['Title'],0,35,'...')) ?></td>
              <td><?= htmlspecialchars($r['RequesterName']) ?></td>
              <td><?= htmlspecialchars($r['DeptName']) ?></td>
              <td><span class="badge <?= priorityBadge($r['Priority']) ?>"><?= $r['Priority'] ?></span></td>
              <td><span class="badge <?= statusBadge($r['Status']) ?>"><?= $r['Status'] ?></span></td>
              <td><?= date('M d, Y', strtotime($r['CreatedDate'])) ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><span class="card-title">By Department</span></div>
        <div class="card-body">
          <?php while ($ds = $dept_stats->fetch_assoc()): $pct = $ds['total'] > 0 ? round(($ds['open_count']/$ds['total'])*100) : 0; ?>
          <div style="margin-bottom:var(--space-4);">
            <div style="display:flex;justify-content:space-between;font-size:var(--text-sm);margin-bottom:4px;">
              <span><?= htmlspecialchars($ds['DeptName']) ?></span>
              <span><strong><?= $ds['open_count'] ?></strong> open / <?= $ds['total'] ?></span>
            </div>
            <div style="height:6px;background:var(--color-divider);border-radius:var(--radius-full);overflow:hidden;">
              <div style="height:100%;width:<?= $pct ?>%;background:var(--color-primary);border-radius:var(--radius-full);transition:.3s;"></div>
            </div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>
  </div>
</div></div>
</body></html>