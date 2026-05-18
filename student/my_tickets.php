<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['followup'])) {
    $tid = intval($_POST['ticket_id']);
    $msg = trim($_POST['message'] ?? '');
    if ($msg) {
        $stmt = $conn->prepare("INSERT INTO followups (TicketID,SenderID,Message) VALUES (?,?,?)");
        $stmt->bind_param('iis', $tid, $uid, $msg);
        $stmt->execute();
        setFlash('success', 'Message Sent', 'Your follow-up message has been sent.');
    }
    header("Location: my_tickets.php"); exit();
}

$status_filter = $_GET['status'] ?? '';
$where = "WHERE t.RequesterID=$uid";
if ($status_filter) $where .= " AND t.Status='".mysqli_real_escape_string($conn,$status_filter)."'";

$tickets = $conn->query("SELECT t.*,d.DeptName,u.FullName as AssigneeName
    FROM tickets t JOIN departments d ON t.DeptID=d.DeptID
    LEFT JOIN users u ON t.AssignedTo=u.UserID
    $where ORDER BY t.CreatedDate DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Tickets</title>
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
    <h1 class="page-title">My Tickets & Follow-Ups</h1>
    <div class="topbar-actions">
      <a href="new_ticket.php" class="btn btn-primary btn-sm">+ New Request</a>
    </div>
  </div>
  <div class="page-body">
    <div class="filters-bar">
      <div class="filter-group">
        <label>Filter by Status</label>
        <select onchange="location='my_tickets.php?status='+this.value">
          <option value="" <?= !$status_filter?'selected':'' ?>>All Statuses</option>
          <?php foreach(['Open','Assigned','In Progress','On Hold','Resolved','Closed'] as $s): ?>
          <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php while ($t = $tickets->fetch_assoc()):
      $fups = $conn->prepare("SELECT f.*,u.FullName FROM followups f JOIN users u ON f.SenderID=u.UserID WHERE f.TicketID=? ORDER BY f.SentAt");
      $fups->bind_param('i', $t['TicketID']);
      $fups->execute();
      $fups_res = $fups->get_result();
    ?>
    <div class="card">
      <div class="card-header">
        <div style="display:flex;align-items:center;gap:var(--space-2);flex-wrap:wrap;">
          <strong><?= htmlspecialchars($t['TicketNo']) ?></strong>
          <span>–</span>
          <span><?= htmlspecialchars(mb_strimwidth($t['Title'],0,50,'...')) ?></span>
          <span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span>
          <span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span>
        </div>
        <a href="ticket_view.php?id=<?= $t['TicketID'] ?>" class="btn btn-secondary btn-sm">Full View</a>
      </div>
      <div class="card-body">
        <div class="ticket-meta-grid">
          <div class="ticket-meta-item"><div class="meta-label">Department</div><div class="meta-value"><?= htmlspecialchars($t['DeptName']) ?></div></div>
          <div class="ticket-meta-item"><div class="meta-label">Category</div><div class="meta-value"><?= htmlspecialchars($t['Category']) ?></div></div>
          <div class="ticket-meta-item"><div class="meta-label">Assigned To</div><div class="meta-value"><?= $t['AssigneeName'] ? htmlspecialchars($t['AssigneeName']) : 'Unassigned' ?></div></div>
          <div class="ticket-meta-item"><div class="meta-label">Submitted</div><div class="meta-value"><?= date('M d, Y', strtotime($t['CreatedDate'])) ?></div></div>
        </div>
        <div class="chat-container">
          <?php if ($fups_res->num_rows === 0): ?>
            <p style="text-align:center;color:var(--color-text-muted);font-size:var(--text-sm);">No messages yet.</p>
          <?php endif; ?>
          <?php while ($f = $fups_res->fetch_assoc()): $out = $f['SenderID'] == $uid; ?>
          <div class="chat-msg <?= $out?'outgoing':'incoming' ?>">
            <div class="chat-bubble"><?= htmlspecialchars($f['Message']) ?></div>
            <div class="chat-meta"><?= htmlspecialchars($f['FullName']) ?> · <?= date('M d, H:i', strtotime($f['SentAt'])) ?></div>
          </div>
          <?php endwhile; ?>
        </div>
        <?php if ($t['Status'] !== 'Closed'): ?>
        <div style="margin-top:var(--space-3);display:flex;gap:var(--space-2);">
          <input type="text" class="followup-input" data-tid="<?= $t['TicketID'] ?>" placeholder="Type a follow-up message..."
            style="flex:1;padding:var(--space-2) var(--space-3);border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:var(--text-sm);">
          <button type="button" class="btn btn-primary btn-sm send-followup-btn" data-tid="<?= $t['TicketID'] ?>">Send</button>
        </div>
        <!-- hidden form for this ticket -->
        <form id="fupForm_<?= $t['TicketID'] ?>" method="POST" style="display:none;">
          <input type="hidden" name="ticket_id" value="<?= $t['TicketID'] ?>">
          <input type="hidden" name="followup" value="1">
          <input type="hidden" name="message" class="hidden-msg-<?= $t['TicketID'] ?>">
        </form>
        <?php else: ?>
        <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:var(--space-2);">🔒 This ticket is closed.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
</div></div>

<script src="../assets/modal.js"></script>
<script>
document.querySelectorAll('.send-followup-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const tid = btn.dataset.tid;
        const input = document.querySelector(`.followup-input[data-tid="${tid}"]`);
        const msg   = input.value.trim();
        if (!msg) {
            await Modal.alert({ type: 'error', title: 'Empty Message', message: 'Please type a message before sending.' });
            return;
        }
        const confirmed = await Modal.confirm({
            title: 'Send Follow-up',
            message: `Send this message?<br><br><em style="background:var(--color-bg);padding:8px;border-radius:6px;display:block;">"${msg}"</em>`,
            confirmText: 'Send'
        });
        if (confirmed) {
            document.querySelector(`.hidden-msg-${tid}`).value = msg;
            Modal.loading(btn, 'Sending...');
            document.getElementById(`fupForm_${tid}`).submit();
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
