<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireStaffOrAdmin();
$id  = intval($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_note']) && isAdmin()) {
    $new_status    = $_POST['status'] ?? '';
    $update_note   = trim($_POST['update_note'] ?? '');
    $assigned_to   = intval($_POST['assigned_to'] ?? 0) ?: null;
    $resolution    = trim($_POST['resolution_summary'] ?? '');
    $reassign_note = trim($_POST['reassign_reason'] ?? '');
    $cur = $conn->query("SELECT Status FROM tickets WHERE TicketID=$id")->fetch_assoc();
    $old_status = $cur['Status'];
    $date_closed = $new_status === 'Closed' ? date('Y-m-d H:i:s') : null;
    $upd = $conn->prepare("UPDATE tickets SET Status=?,AssignedTo=?,ResolutionSummary=?,DateClosed=?,UpdatedAt=NOW() WHERE TicketID=?");
    $upd->bind_param('sissi', $new_status,$assigned_to,$resolution,$date_closed,$id);
    $upd->execute();
    $log = $conn->prepare("INSERT INTO history_log (TicketID,UpdatedBy,UpdateNote,OldStatus,NewStatus,ReassignReason) VALUES (?,?,?,?,?,?)");
    $log->bind_param('iissss', $id,$uid,$update_note,$old_status,$new_status,$reassign_note);
    $log->execute();
    $msg = 'Ticket updated successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reply'])) {
    $reply = trim($_POST['reply_msg'] ?? '');
    if ($reply) {
        $stmt = $conn->prepare("INSERT INTO followups (TicketID,SenderID,Message) VALUES (?,?,?)");
        $stmt->bind_param('iis', $id,$uid,$reply);
        $stmt->execute();
        header("Location: ticket_detail.php?id=$id"); exit();
    }
}

$t = $conn->query("SELECT t.*,d.DeptName,u.FullName as RequesterName,u.Email as RequesterEmail,s.FullName as AssigneeName
    FROM tickets t JOIN departments d ON t.DeptID=d.DeptID JOIN users u ON t.RequesterID=u.UserID
    LEFT JOIN users s ON t.AssignedTo=s.UserID WHERE t.TicketID=$id")->fetch_assoc();
if (!$t) { echo "<p style='padding:2rem'>Ticket not found. <a href='tickets.php'>Go back</a></p>"; exit(); }

$logs      = $conn->query("SELECT h.*,u.FullName FROM history_log h JOIN users u ON h.UpdatedBy=u.UserID WHERE h.TicketID=$id ORDER BY h.UpdateDate DESC");
$followups = $conn->query("SELECT f.*,u.FullName,u.UserType FROM followups f JOIN users u ON f.SenderID=u.UserID WHERE f.TicketID=$id ORDER BY f.SentAt");
$staff_list= $conn->query("SELECT UserID,FullName FROM users WHERE UserType IN ('staff','admin') AND IsActive=1");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($t['TicketNo']) ?> – Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <a href="tickets.php" style="font-size:var(--text-sm);color:var(--color-text-muted);">← All Tickets</a>
      <h1 class="page-title"><?= htmlspecialchars($t['TicketNo']) ?> – <?= htmlspecialchars(mb_strimwidth($t['Title'],0,50,'...')) ?></h1>
    </div>
    <span class="badge <?= statusBadge($t['Status']) ?>" style="font-size:var(--text-sm);padding:var(--space-2) var(--space-4);"><?= $t['Status'] ?></span>
  </div>
  <div class="page-body">
    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);align-items:start;">
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">📋 Ticket Details</span></div>
          <div class="card-body">
            <div class="ticket-meta-grid">
              <div class="ticket-meta-item"><div class="meta-label">Status</div><div class="meta-value"><span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Priority</div><div class="meta-value"><span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Department</div><div class="meta-value"><?= htmlspecialchars($t['DeptName']) ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Category</div><div class="meta-value"><?= htmlspecialchars($t['Category']) ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Requester</div><div class="meta-value"><?= htmlspecialchars($t['RequesterName']) ?><br><span style="font-size:var(--text-xs);color:var(--color-text-muted);"><?= htmlspecialchars($t['RequesterEmail']) ?></span></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Assigned To</div><div class="meta-value"><?= $t['AssigneeName'] ?: 'Unassigned' ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Location</div><div class="meta-value"><?= $t['Location'] ?: '—' ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Created</div><div class="meta-value"><?= date('M d, Y H:i', strtotime($t['CreatedDate'])) ?></div></div>
            </div>
            <?php if ($t['UrgentReason']): ?>
            <div class="alert alert-warning">⚠️ <strong>Urgent Reason:</strong> <?= htmlspecialchars($t['UrgentReason']) ?></div>
            <?php endif; ?>
            <div style="background:var(--color-bg);border-radius:var(--radius-md);padding:var(--space-4);">
              <div class="meta-label" style="margin-bottom:var(--space-2);">Description</div>
              <p style="font-size:var(--text-sm);"><?= nl2br(htmlspecialchars($t['Description'])) ?></p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="card-title">💬 Messages</span></div>
          <div class="card-body">
            <div class="chat-container">
              <?php while ($f = $followups->fetch_assoc()): $out = in_array($f['UserType'],['admin','staff']); ?>
              <div class="chat-msg <?= $out?'outgoing':'incoming' ?>">
                <div class="chat-bubble"><?= htmlspecialchars($f['Message']) ?></div>
                <div class="chat-meta"><?= htmlspecialchars($f['FullName']) ?> (<?= $f['UserType'] ?>) · <?= date('M d, H:i', strtotime($f['SentAt'])) ?></div>
              </div>
              <?php endwhile; ?>
            </div>
            <?php if ($t['Status'] !== 'Closed'): ?>
            <form method="POST" style="margin-top:var(--space-3);display:flex;gap:var(--space-2);">
              <input type="hidden" name="admin_reply" value="1">
              <input type="text" name="reply_msg" placeholder="Reply to requester..." style="flex:1;padding:var(--space-2) var(--space-3);border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:var(--text-sm);" required>
              <button type="submit" class="btn btn-primary btn-sm">Reply</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="card-title">📜 History Log</span></div>
          <div class="card-body">
            <div class="timeline">
              <?php while ($l = $logs->fetch_assoc()): ?>
              <div class="timeline-item">
                <div class="timeline-dot dot-progress">●</div>
                <div class="timeline-content">
                  <strong><?= htmlspecialchars($l['UpdateNote']) ?></strong>
                  <?php if ($l['OldStatus'] && $l['NewStatus']): ?>
                    <span class="badge <?= statusBadge($l['OldStatus']) ?>" style="font-size:.65rem;margin-left:4px"><?= $l['OldStatus'] ?></span>
                    → <span class="badge <?= statusBadge($l['NewStatus']) ?>" style="font-size:.65rem"><?= $l['NewStatus'] ?></span>
                  <?php endif; ?>
                  <div class="tl-meta">By <?= htmlspecialchars($l['FullName']) ?> · <?= date('M d, Y H:i', strtotime($l['UpdateDate'])) ?></div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>

      <?php if (isAdmin()): ?>
      <div style="position:sticky;top:80px;">
        <?php if ($t['Status'] !== 'Closed'): ?>
        <div class="card">
          <div class="card-header"><span class="card-title">⚙️ Update Ticket</span></div>
          <div class="card-body">
            <form method="POST">
              <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                  <?php foreach(['Open','Assigned','In Progress','On Hold','Resolved','Closed'] as $s): ?>
                  <option value="<?= $s ?>" <?= $t['Status']===$s?'selected':'' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Assign To</label>
                <select name="assigned_to">
                  <option value="">Unassigned</option>
                  <?php while($st=$staff_list->fetch_assoc()): ?>
                  <option value="<?= $st['UserID'] ?>" <?= $t['AssignedTo']==$st['UserID']?'selected':'' ?>><?= htmlspecialchars($st['FullName']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Update Note *</label>
                <textarea name="update_note" rows="3" placeholder="What was done?" required></textarea>
              </div>
              <div class="form-group">
                <label>Reassign Reason</label>
                <input type="text" name="reassign_reason" placeholder="If reassigning...">
              </div>
              <div class="form-group">
                <label>Resolution Summary</label>
                <textarea name="resolution_summary" rows="3" placeholder="How was it resolved?"><?= htmlspecialchars($t['ResolutionSummary'] ?? '') ?></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-full">Update Ticket</button>
            </form>
          </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">🔒 This ticket is <strong>Closed</strong> and read-only.</div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div></div>
</body></html>