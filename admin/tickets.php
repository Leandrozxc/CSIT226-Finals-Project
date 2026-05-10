<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireStaffOrAdmin();

$status   = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$dept     = $_GET['dept']     ?? '';
$search   = $_GET['search']   ?? '';

$where = "WHERE 1=1";
if ($status)   $where .= " AND t.Status='"   .mysqli_real_escape_string($conn,$status)."'";
if ($priority) $where .= " AND t.Priority='" .mysqli_real_escape_string($conn,$priority)."'";
if ($dept)     $where .= " AND t.DeptID="    .intval($dept);
if ($search)   $where .= " AND (t.TicketNo LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR t.Title LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.FullName LIKE '%".mysqli_real_escape_string($conn,$search)."%')";

$tickets = $conn->query("SELECT t.*,d.DeptName,u.FullName as RequesterName,s.FullName as AssigneeName
    FROM tickets t JOIN departments d ON t.DeptID=d.DeptID JOIN users u ON t.RequesterID=u.UserID
    LEFT JOIN users s ON t.AssignedTo=s.UserID $where ORDER BY
    FIELD(t.Priority,'Urgent','High','Normal','Low'), t.CreatedDate DESC");
$depts = $conn->query("SELECT DeptID,DeptName FROM departments WHERE IsActive=1");
$total_count = $tickets->num_rows;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Tickets – Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <h1 class="page-title">All Tickets <span style="font-size:var(--text-sm);color:var(--color-text-muted);font-weight:400;">(<?= $total_count ?>)</span></h1>
  </div>
  <div class="page-body">
    <form method="GET" class="filters-bar">
      <div class="filter-group">
        <label>Search</label>
        <input type="text" name="search" placeholder="Ticket No, Title, Name..." value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="filter-group">
        <label>Status</label>
        <select name="status">
          <option value="">All Statuses</option>
          <?php foreach(['Open','Assigned','In Progress','On Hold','Resolved','Closed'] as $s): ?>
          <option <?= $status===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Priority</label>
        <select name="priority">
          <option value="">All Priorities</option>
          <?php foreach(['Low','Normal','High','Urgent'] as $p): ?>
          <option <?= $priority===$p?'selected':'' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-group">
        <label>Department</label>
        <select name="dept">
          <option value="">All Departments</option>
          <?php while($d=$depts->fetch_assoc()): ?>
          <option value="<?= $d['DeptID'] ?>" <?= $dept==$d['DeptID']?'selected':'' ?>><?= htmlspecialchars($d['DeptName']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="filter-group" style="justify-content:flex-end;">
        <label>&nbsp;</label>
        <div style="display:flex;gap:var(--space-2);">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          <a href="tickets.php" class="btn btn-secondary btn-sm">Clear</a>
        </div>
      </div>
    </form>

    <div class="card">
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Ticket No</th><th>Title</th><th>Requester</th><th>Department</th><th>Priority</th><th>Status</th><th>Assigned To</th><th>Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php while ($t = $tickets->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($t['TicketNo']) ?></strong></td>
            <td><?= htmlspecialchars(mb_strimwidth($t['Title'],0,40,'...')) ?></td>
            <td><?= htmlspecialchars($t['RequesterName']) ?></td>
            <td><?= htmlspecialchars($t['DeptName']) ?></td>
            <td><span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span></td>
            <td><span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span></td>
            <td><?= $t['AssigneeName'] ? htmlspecialchars($t['AssigneeName']) : '<span style="color:var(--color-text-faint)">Unassigned</span>' ?></td>
            <td><?= date('M d, Y', strtotime($t['CreatedDate'])) ?></td>
            <td><a href="ticket_detail.php?id=<?= $t['TicketID'] ?>" class="btn btn-primary btn-sm">Manage</a></td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div></div>
</body></html>