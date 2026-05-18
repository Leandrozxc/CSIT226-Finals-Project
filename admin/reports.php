<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireStaffOrAdmin();

// Overall stats
$total      = $conn->query("SELECT COUNT(*) as c FROM tickets")->fetch_assoc()['c'];
$open       = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='Open'")->fetch_assoc()['c'];
$inprogress = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='In Progress'")->fetch_assoc()['c'];
$resolved   = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='Resolved'")->fetch_assoc()['c'];
$closed     = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='Closed'")->fetch_assoc()['c'];
$urgent     = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Priority='Urgent'")->fetch_assoc()['c'];

// By department
$by_dept = $conn->query("SELECT d.DeptName,
    COUNT(t.TicketID) as total,
    SUM(CASE WHEN t.Status='Open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN t.Status='In Progress' THEN 1 ELSE 0 END) as inprogress_count,
    SUM(CASE WHEN t.Status='Resolved' THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN t.Status='Closed' THEN 1 ELSE 0 END) as closed_count
    FROM departments d LEFT JOIN tickets t ON d.DeptID=t.DeptID
    WHERE d.IsActive=1 GROUP BY d.DeptID ORDER BY total DESC");

// By priority
$by_priority = $conn->query("SELECT Priority, COUNT(*) as count FROM tickets GROUP BY Priority ORDER BY FIELD(Priority,'Urgent','High','Normal','Low')");

// By category
$by_category = $conn->query("SELECT Category, COUNT(*) as count FROM tickets GROUP BY Category ORDER BY count DESC LIMIT 8");

// By staff performance
$by_staff = $conn->query("SELECT u.FullName,
    COUNT(t.TicketID) as assigned,
    SUM(CASE WHEN t.Status='Resolved' THEN 1 ELSE 0 END) as resolved,
    SUM(CASE WHEN t.Status IN ('Open','In Progress','Assigned') THEN 1 ELSE 0 END) as active
    FROM users u LEFT JOIN tickets t ON u.UserID=t.AssignedTo
    WHERE u.UserType IN ('staff','admin') GROUP BY u.UserID ORDER BY assigned DESC");

// Monthly trend (last 6 months)
$monthly = $conn->query("SELECT DATE_FORMAT(CreatedDate,'%b %Y') as month,
    COUNT(*) as total,
    SUM(CASE WHEN Status IN ('Resolved','Closed') THEN 1 ELSE 0 END) as resolved
    FROM tickets WHERE CreatedDate >= NOW() - INTERVAL 6 MONTH
    GROUP BY DATE_FORMAT(CreatedDate,'%Y-%m') ORDER BY CreatedDate ASC");

$monthly_data = [];
while ($m = $monthly->fetch_assoc()) $monthly_data[] = $m;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports & Analytics</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
<link rel="stylesheet" href="../assets/modal.css">  
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">Reports & Analytics</h1>
    <div class="topbar-actions">
      <span style="font-size:var(--text-sm);color:var(--color-text-muted);">As of <?= date('F d, Y') ?></span>
    </div>
  </div>
  <div class="page-body">

    <div class="stats-grid">
      <div class="stat-card stat-total"><div class="stat-label">Total Tickets</div><div class="stat-value"><?= $total ?></div></div>
      <div class="stat-card stat-open"><div class="stat-label">Open</div><div class="stat-value"><?= $open ?></div></div>
      <div class="stat-card stat-progress"><div class="stat-label">In Progress</div><div class="stat-value"><?= $inprogress ?></div></div>
      <div class="stat-card stat-resolved"><div class="stat-label">Resolved</div><div class="stat-value"><?= $resolved ?></div></div>
      <div class="stat-card stat-closed"><div class="stat-label">Closed</div><div class="stat-value"><?= $closed ?></div></div>
      <div class="stat-card stat-urgent"><div class="stat-label">Urgent</div><div class="stat-value"><?= $urgent ?></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
      <div class="card">
        <div class="card-header"><span class="card-title">📈 Monthly Ticket Trend</span></div>
        <div class="card-body"><canvas id="monthlyChart" height="220"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">🥧 Tickets by Status</span></div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:center;">
          <canvas id="statusChart" height="220"></canvas>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-5);margin-bottom:var(--space-5);">
      <div class="card">
        <div class="card-header"><span class="card-title">🚦 Tickets by Priority</span></div>
        <div class="card-body"><canvas id="priorityChart" height="200"></canvas></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">🏷️ Top Categories</span></div>
        <div class="card-body"><canvas id="categoryChart" height="200"></canvas></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">🏢 Performance by Department</span></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Department</th><th>Total</th><th>Open</th><th>In Progress</th><th>Resolved</th><th>Closed</th><th>Resolution Rate</th></tr></thead>
          <tbody>
          <?php $by_dept->data_seek(0); while ($d = $by_dept->fetch_assoc()):
            $rate = $d['total'] > 0 ? round((($d['resolved_count']+$d['closed_count'])/$d['total'])*100) : 0; ?>
          <tr>
            <td><strong><?= htmlspecialchars($d['DeptName']) ?></strong></td>
            <td><?= $d['total'] ?></td>
            <td><span class="badge badge-open"><?= $d['open_count'] ?></span></td>
            <td><span class="badge badge-inprogress"><?= $d['inprogress_count'] ?></span></td>
            <td><span class="badge badge-resolved"><?= $d['resolved_count'] ?></span></td>
            <td><span class="badge badge-closed"><?= $d['closed_count'] ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:var(--space-2);">
                <div style="flex:1;height:8px;background:var(--color-divider);border-radius:var(--radius-full);overflow:hidden;">
                  <div style="height:100%;width:<?= $rate ?>%;background:var(--color-success);border-radius:var(--radius-full);"></div>
                </div>
                <span style="font-size:var(--text-xs);font-weight:700;min-width:32px;"><?= $rate ?>%</span>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">👥 Staff Performance</span></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Staff Name</th><th>Total Assigned</th><th>Active</th><th>Resolved</th><th>Resolution Rate</th></tr></thead>
          <tbody>
          <?php while ($s = $by_staff->fetch_assoc()):
            $rate = $s['assigned'] > 0 ? round(($s['resolved']/$s['assigned'])*100) : 0; ?>
          <tr>
            <td><?= htmlspecialchars($s['FullName']) ?></td>
            <td><?= $s['assigned'] ?></td>
            <td><span class="badge badge-inprogress"><?= $s['active'] ?></span></td>
            <td><span class="badge badge-resolved"><?= $s['resolved'] ?></span></td>
            <td>
              <div style="display:flex;align-items:center;gap:var(--space-2);">
                <div style="flex:1;height:8px;background:var(--color-divider);border-radius:var(--radius-full);overflow:hidden;">
                  <div style="height:100%;width:<?= $rate ?>%;background:var(--color-primary);border-radius:var(--radius-full);"></div>
                </div>
                <span style="font-size:var(--text-xs);font-weight:700;min-width:32px;"><?= $rate ?>%</span>
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

<script>
const monthlyData = <?= json_encode($monthly_data) ?>;
new Chart(document.getElementById('monthlyChart'), {
  type: 'line',
  data: {
    labels: monthlyData.map(d => d.month),
    datasets: [
      { label: 'Total', data: monthlyData.map(d => d.total), borderColor: '#7B1D1D', backgroundColor: 'rgba(123,29,29,.1)', tension: 0.4, fill: true },
      { label: 'Resolved', data: monthlyData.map(d => d.resolved), borderColor: '#166534', backgroundColor: 'rgba(22,101,52,.1)', tension: 0.4, fill: true }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: ['Open','In Progress','Resolved','Closed','On Hold','Assigned'],
    datasets: [{ data: [<?= $open ?>,<?= $inprogress ?>,<?= $resolved ?>,<?= $closed ?>,
      <?= $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='On Hold'")->fetch_assoc()['c'] ?>,
      <?= $conn->query("SELECT COUNT(*) as c FROM tickets WHERE Status='Assigned'")->fetch_assoc()['c'] ?>
    ], backgroundColor: ['#f59e0b','#8b5cf6','#22c55e','#6b7280','#94a3b8','#3b82f6'], borderWidth: 2 }]
  },
  options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

<?php
$priority_labels=[]; $priority_data=[];
$by_priority->data_seek(0);
while($p=$by_priority->fetch_assoc()){$priority_labels[]=$p['Priority'];$priority_data[]=$p['count'];}
?>
new Chart(document.getElementById('priorityChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($priority_labels) ?>,
    datasets: [{ label: 'Tickets', data: <?= json_encode($priority_data) ?>,
      backgroundColor: ['#22c55e','#3b82f6','#f59e0b','#ef4444'], borderRadius: 6 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

<?php
$cat_labels=[]; $cat_data=[];
$by_category->data_seek(0);
while($c=$by_category->fetch_assoc()){$cat_labels[]=$c['Category'];$cat_data[]=$c['count'];}
?>
new Chart(document.getElementById('categoryChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($cat_labels) ?>,
    datasets: [{ label: 'Tickets', data: <?= json_encode($cat_data) ?>,
      backgroundColor: '#7B1D1D', borderRadius: 6 }]
  },
  options: { responsive: true, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>
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