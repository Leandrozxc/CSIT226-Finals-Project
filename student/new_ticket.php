<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();
$success = $error = $dup_warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $dept_id       = intval($_POST['dept_id']);
    $category      = trim($_POST['category'] ?? '');
    $priority      = $_POST['priority'] ?? 'Normal';
    $description   = trim($_POST['description'] ?? '');
    $location      = trim($_POST['location'] ?? '');
    $urgent_reason = trim($_POST['urgent_reason'] ?? '');
    $occ_date      = $_POST['occurrence_date'] ?: null;

    // ✅ Removed description from required check
    // ✅ Removed 50-character minimum rule
    if (!$title || !$dept_id || !$category) {
        $error = 'Please fill in all required fields.';
    } elseif ($priority === 'Urgent' && !$urgent_reason) {
        $error = 'Urgent reason is required for Urgent priority (Business Rule #8).';
    } else {
        $uid = $_SESSION['user_id'];
        $dup = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE RequesterID=? AND Category=? AND Status='Open' AND CreatedDate >= NOW() - INTERVAL 24 HOUR");
        $dup->bind_param('is', $uid, $category);
        $dup->execute();
        $dup_count = $dup->get_result()->fetch_assoc()['cnt'];
        if ($dup_count > 0) $dup_warning = "⚠️ Duplicate Warning: You already have an open \"$category\" ticket within the last 24 hours.";

        $ticket_no = generateTicketNo();
        $stmt = $conn->prepare("INSERT INTO tickets (TicketNo,Title,Description,Category,Location,Priority,UrgentReason,DeptID,RequesterID,OccurrenceDate) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssiis', $ticket_no,$title,$description,$category,$location,$priority,$urgent_reason,$dept_id,$uid,$occ_date);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $note = 'Ticket created successfully'; $new_st = 'Open';
            $log = $conn->prepare("INSERT INTO history_log (TicketID,UpdatedBy,UpdateNote,NewStatus) VALUES (?,?,?,?)");
            $log->bind_param('iiss', $new_id,$uid,$note,$new_st);
            $log->execute();
            $success = "✅ Ticket submitted! Your Ticket Number is: <strong>$ticket_no</strong> — Keep this for tracking.";
        } else { $error = 'Failed to submit. Please try again.'; }
    }
}
$depts = $conn->query("SELECT DeptID,DeptName FROM departments WHERE IsActive=1 ORDER BY DeptName");
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Submit New Request</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300..700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/style.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_student.php'; ?>
<div class="main-content">
  <div class="topbar"><h1 class="page-title">Submit New Request</h1></div>
  <div class="page-body">
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="duplicate-warning <?= $dup_warning?'show':'' ?>"><?= htmlspecialchars($dup_warning) ?></div>
    <div class="card">
      <div class="card-header"><span class="card-title">🎫 New Ticket Form</span></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="form-group full">
              <label>Ticket Title *</label>
              <input type="text" name="title" placeholder="Brief title of your concern" required>
            </div>
            <div class="form-group">
              <label>Department *</label>
              <select name="dept_id" required>
                <option value="">Select Department...</option>
                <?php while($d=$depts->fetch_assoc()): ?>
                <option value="<?= $d['DeptID'] ?>"><?= htmlspecialchars($d['DeptName']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Category *</label>
              <select name="category" required>
                <option value="">Select Category...</option>
                <option>Network / Internet</option>
                <option>Hardware Issue</option>
                <option>Software / System</option>
                <option>Enrollment / Records</option>
                <option>Facility Damage</option>
                <option>Cleanliness</option>
                <option>Security Concern</option>
                <option>Library Services</option>
                <option>Financial / Billing</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Priority *</label>
              <select name="priority" id="priority_sel" required onchange="toggleUrgent()">
                <option value="Low">Low</option>
                <option value="Normal" selected>Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
            <div class="form-group" id="urgent_box" style="display:none;">
              <label>Urgent Reason * <span class="hint">(Required for Urgent)</span></label>
              <input type="text" name="urgent_reason" id="urgent_reason" placeholder="Justify why this is urgent...">
            </div>
            <div class="form-group">
              <label>Building / Location</label>
              <input type="text" name="location" placeholder="e.g., Building A, Room 201">
            </div>
            <div class="form-group">
              <label>Date & Time of Occurrence</label>
              <input type="datetime-local" name="occurrence_date">
            </div>
            <div class="form-group full">
              <!-- ✅ Removed required, removed 50-char minimum -->
              <label>Description <span class="hint">(optional)</span></label>
              <textarea name="description" id="desc_area" placeholder="Describe your issue in detail..." oninput="countChars()"></textarea>
              <span class="char-count" id="char_count" style="color:var(--color-text-muted);">0 characters</span>
            </div>
            <div class="form-group">
              <label>Attach Evidence / Screenshot</label>
              <input type="file" name="attachment" accept="image/*,.pdf,.doc,.docx">
              <span class="hint">Accepted: images, PDF, DOC</span>
            </div>
          </div>
          <div class="form-actions">
            <button type="reset" class="btn btn-secondary" onclick="resetForm()">Clear</button>
            <button type="submit" class="btn btn-primary">Submit Ticket</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div></div>
<script>
function countChars(){
  const v=document.getElementById('desc_area').value.length;
  const el=document.getElementById('char_count');
  el.textContent=v+' characters';
}
function toggleUrgent(){
  const v=document.getElementById('priority_sel').value;
  const box=document.getElementById('urgent_box');
  const inp=document.getElementById('urgent_reason');
  box.style.display=v==='Urgent'?'block':'none';
  inp.required=v==='Urgent';
}
function resetForm(){
  document.getElementById('char_count').textContent='0 characters';
  document.getElementById('urgent_box').style.display='none';
}
</script>
</body></html>