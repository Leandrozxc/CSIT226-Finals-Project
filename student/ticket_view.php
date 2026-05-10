<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$uid = $_SESSION['user_id'];
$id  = intval($_GET['id'] ?? 0);

// Handle follow-up message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['followup'])) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg) {
        $stmt = $conn->prepare("INSERT INTO followups (TicketID,SenderID,Message) VALUES (?,?,?)");
        $stmt->bind_param('iis', $id, $uid, $msg);
        $stmt->execute();
    }
    header("Location: ticket_view.php?id=$id"); exit();
}

$t = $conn->query("SELECT t.*,d.DeptName,u.FullName as AssigneeName
    FROM tickets t JOIN departments d ON t.DeptID=d.DeptID
    LEFT JOIN users u ON t.AssignedTo=u.UserID
    WHERE t.TicketID=$id AND t.RequesterID=$uid")->fetch_assoc();

if (!$t) {
    echo "<p style='padding:2rem;font-family:sans-serif;'>Ticket not found or access denied. <a href='my_tickets.php'>Go back</a></p>";
    exit();
}

$followups = $conn->query("SELECT f.*,u.FullName,u.UserType FROM followups f JOIN users u ON f.SenderID=u.UserID WHERE f.TicketID=$id ORDER BY f.SentAt");
$history   = $conn->query("SELECT h.*,u.FullName FROM history_log h JOIN users u ON h.UpdatedBy=u.UserID WHERE h.TicketID=$id ORDER BY h.UpdateDate DESC");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($t['TicketNo']) ?> – My Ticket</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_student.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div>
      <a href="my_tickets.php" style="font-size:var(--text-sm);color:var(--color-text-muted);">← Back to My Tickets</a>
      <h1 class="page-title"><?= htmlspecialchars($t['TicketNo']) ?></h1>
    </div>
    <span class="badge <?= statusBadge($t['Status']) ?>" style="font-size:var(--text-sm);padding:var(--space-2) var(--space-4);"><?= $t['Status'] ?></span>
  </div>
  <div class="page-body">
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--space-5);align-items:start;">

      <!-- Left Column -->
      <div>
        <!-- Ticket Details -->
        <div class="card">
          <div class="card-header"><span class="card-title">📋 Ticket Details</span></div>
          <div class="card-body">
            <h2 style="font-size:var(--text-lg);margin-bottom:var(--space-4);"><?= htmlspecialchars($t['Title']) ?></h2>
            <div class="ticket-meta-grid">
              <div class="ticket-meta-item"><div class="meta-label">Status</div><div class="meta-value"><span class="badge <?= statusBadge($t['Status']) ?>"><?= $t['Status'] ?></span></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Priority</div><div class="meta-value"><span class="badge <?= priorityBadge($t['Priority']) ?>"><?= $t['Priority'] ?></span></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Department</div><div class="meta-value"><?= htmlspecialchars($t['DeptName']) ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Category</div><div class="meta-value"><?= htmlspecialchars($t['Category']) ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Assigned To</div><div class="meta-value"><?= $t['AssigneeName'] ? htmlspecialchars($t['AssigneeName']) : '<span style="color:var(--color-text-faint)">Not yet assigned</span>' ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Location</div><div class="meta-value"><?= $t['Location'] ? htmlspecialchars($t['Location']) : '—' ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Submitted</div><div class="meta-value"><?= date('F d, Y h:i A', strtotime($t['CreatedDate'])) ?></div></div>
              <div class="ticket-meta-item"><div class="meta-label">Last Updated</div><div class="meta-value"><?= date('F d, Y h:i A', strtotime($t['UpdatedAt'])) ?></div></div>
            </div>
            <?php if ($t['UrgentReason']): ?>
            <div class="alert alert-warning" style="margin-top:var(--space-4);">⚠️ <strong>Urgent Reason:</strong> <?= htmlspecialchars($t['UrgentReason']) ?></div>
            <?php endif; ?>
            <div style="margin-top:var(--space-4);background:var(--color-bg);border-radius:var(--radius-md);padding:var(--space-4);">
              <div class="meta-label" style="margin-bottom:var(--space-2);">Description</div>
              <p style="font-size:var(--text-sm);"><?= nl2br(htmlspecialchars($t['Description'])) ?></p>
            </div>
            <?php if ($t['ResolutionSummary']): ?>
            <div style="margin-top:var(--space-4);background:#f0fdf4;border-radius:var(--radius-md);padding:var(--space-4);border:1px solid #bbf7d0;">
              <div class="meta-label" style="color:#166534;margin-bottom:var(--space-2);">✅ Resolution Summary</div>
              <p style="font-size:var(--text-sm);"><?= nl2br(htmlspecialchars($t['ResolutionSummary'])) ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Messages -->
        <div class="card">
          <div class="card-header"><span class="card-title">💬 Messages</span></div>
          <div class="card-body">
            <div class="chat-container">
              <?php if ($followups->num_rows === 0): ?>
              <p style="text-align:center;color:var(--color-text-muted);font-size:var(--text-sm);">No messages yet. You can send a follow-up below.</p>
              <?php endif; ?>
              <?php while ($f = $followups->fetch_assoc()): $out = $f['SenderID'] == $uid; ?>
              <div class="chat-msg <?= $out?'outgoing':'incoming' ?>">
                <div class="chat-bubble"><?= htmlspecialchars($f['Message']) ?></div>
                <div class="chat-meta"><?= htmlspecialchars($f['FullName']) ?> (<?= $f['UserType'] ?>) · <?= date('M d, Y H:i', strtotime($f['SentAt'])) ?></div>
              </div>
              <?php endwhile; ?>
            </div>
            <?php if ($t['Status'] !== 'Closed'): ?>
            <form method="POST" style="margin-top:var(--space-4);display:flex;gap:var(--space-2);">
              <input type="hidden" name="followup" value="1">
              <input type="text" name="message" placeholder="Send a follow-up message..." style="flex:1;padding:var(--space-2) var(--space-3);border:1px solid var(--color-border);border-radius:var(--radius-md);font-size:var(--text-sm);" required>
              <button type="submit" class="btn btn-primary btn-sm">Send</button>
            </form>
            <?php else: ?>
            <p style="font-size:var(--text-xs);color:var(--color-text-muted);margin-top:var(--space-3);">🔒 This ticket is closed. No further messages can be sent.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Right Column: History -->
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">📜 Ticket History</span></div>
          <div class="card-body">
            <div class="timeline">
              <?php while ($h = $history->fetch_assoc()): ?>
              <div class="timeline-item">
                <div class="timeline-dot dot-progress">●</div>
                <div class="timeline-content">
                  <strong style="font-size:var(--text-sm);"><?= htmlspecialchars($h['UpdateNote']) ?></strong>
                  <?php if ($h['OldStatus'] && $h['NewStatus']): ?>
                  <div style="margin-top:4px;">
                    <span class="badge <?= statusBadge($h['OldStatus']) ?>" style="font-size:.65rem;"><?= $h['OldStatus'] ?></span>
                    → <span class="badge <?= statusBadge($h['NewStatus']) ?>" style="font-size:.65rem;"><?= $h['NewStatus'] ?></span>
                  </div>
                  <?php endif; ?>
                  <div class="tl-meta">By <?= htmlspecialchars($h['FullName']) ?><br><?= date('M d, Y H:i', strtotime($h['UpdateDate'])) ?></div>
                </div>
              </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div></div>
</body></html>