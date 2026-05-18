<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title         = trim($_POST['title'] ?? '');
    $dept_id       = intval($_POST['dept_id']);
    $category      = trim($_POST['category'] ?? '');
    $priority      = $_POST['priority'] ?? 'Normal';
    $description   = trim($_POST['description'] ?? '');
    $location      = trim($_POST['location'] ?? '');
    $urgent_reason = trim($_POST['urgent_reason'] ?? '');
    $occ_date      = $_POST['occurrence_date'] ?: null;

    if (!$title || !$dept_id || !$category) {
        setFlash('error', 'Validation Error', 'Please fill in all required fields.');
    } elseif ($priority === 'Urgent' && !$urgent_reason) {
        setFlash('error', 'Urgent Reason Required', 'You must provide an urgent reason for Urgent priority tickets.');
    } else {
        $uid = $_SESSION['user_id'];
        $dup = $conn->prepare("SELECT COUNT(*) as cnt FROM tickets WHERE RequesterID=? AND Category=? AND Status='Open' AND CreatedDate >= NOW() - INTERVAL 24 HOUR");
        $dup->bind_param('is', $uid, $category);
        $dup->execute();
        $dup_count = $dup->get_result()->fetch_assoc()['cnt'];

        $ticket_no = generateTicketNo();
        $stmt = $conn->prepare("INSERT INTO tickets (TicketNo,Title,Description,Category,Location,Priority,UrgentReason,DeptID,RequesterID,OccurrenceDate) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssiis', $ticket_no,$title,$description,$category,$location,$priority,$urgent_reason,$dept_id,$uid,$occ_date);
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            $note = 'Ticket created successfully'; $new_st = 'Open';
            $log = $conn->prepare("INSERT INTO history_log (TicketID,UpdatedBy,UpdateNote,NewStatus) VALUES (?,?,?,?)");
            $log->bind_param('iiss', $new_id, $uid, $note, $new_st);
            $log->execute();
            $dup_note = $dup_count > 0 ? " Note: A similar ticket was already open in the last 24 hours." : '';
            setFlash('success', 'Ticket Submitted!', "Your ticket number is <strong>$ticket_no</strong>. Keep this for tracking.$dup_note");
            header("Location: my_tickets.php"); exit();
        } else {
            setFlash('error', 'Submission Failed', 'Could not submit your ticket. Please try again.');
        }
    }
    header("Location: new_ticket.php"); exit();
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
<link rel="stylesheet" href="../assets/modal.css">
</head><body>
<div class="app-layout">
<?php include '../includes/sidebar_student.php'; ?>
<div class="main-content">
  <div class="topbar"><h1 class="page-title">Submit New Request</h1></div>
  <div class="page-body">
    <div class="card">
      <div class="card-header"><span class="card-title">New Ticket Form</span></div>
      <div class="card-body">
        <form id="ticketForm" method="POST" enctype="multipart/form-data">
          <div class="form-grid">
            <div class="form-group full">
              <label>Ticket Title *</label>
              <input type="text" id="f_title" name="title" placeholder="Brief title of your concern" required>
            </div>
            <div class="form-group">
              <label>Department *</label>
              <select id="f_dept" name="dept_id" required>
                <option value="">Select Department...</option>
                <?php while($d=$depts->fetch_assoc()): ?>
                <option value="<?= $d['DeptID'] ?>"><?= htmlspecialchars($d['DeptName']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Category *</label>
              <select id="f_category" name="category" required>
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
              <select id="f_priority" name="priority" required onchange="toggleUrgent()">
                <option value="Low">Low</option>
                <option value="Normal" selected>Normal</option>
                <option value="High">High</option>
                <option value="Urgent">Urgent</option>
              </select>
            </div>
            <div class="form-group" id="urgent_box" style="display:none;">
              <label>Urgent Reason * <span class="hint">(Required for Urgent)</span></label>
              <input type="text" id="f_urgent" name="urgent_reason" placeholder="Justify why this is urgent...">
            </div>
            <div class="form-group">
              <label>Building / Location</label>
              <input type="text" id="f_location" name="location" placeholder="e.g., Building A, Room 201">
            </div>
            <div class="form-group">
              <label>Date & Time of Occurrence</label>
              <input type="datetime-local" id="f_occdate" name="occurrence_date">
            </div>
            <div class="form-group full">
              <label>Description <span class="hint">(optional)</span></label>
              <textarea id="f_desc" name="description" placeholder="Describe your issue in detail..." oninput="countChars()"></textarea>
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
            <button type="button" id="reviewSubmitBtn" class="btn btn-primary">Review & Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div></div>

<script>
function countChars(){
  const v=document.getElementById('f_desc').value.length;
  const el=document.getElementById('char_count');
  el.textContent=v+' characters';
}
function toggleUrgent(){
  const v=document.getElementById('f_priority').value;
  const box=document.getElementById('urgent_box');
  const inp=document.getElementById('f_urgent');
  box.style.display=v==='Urgent'?'block':'none';
  inp.required=v==='Urgent';
}
function resetForm(){
  document.getElementById('char_count').textContent='0 characters';
  document.getElementById('urgent_box').style.display='none';
}
</script>
<script src="../assets/modal.js"></script>
<script>
document.getElementById('reviewSubmitBtn').addEventListener('click', async () => {
    const title    = document.getElementById('f_title').value.trim();
    const deptEl   = document.getElementById('f_dept');
    const dept     = deptEl.options[deptEl.selectedIndex]?.text || '';
    const catEl    = document.getElementById('f_category');
    const category = catEl.options[catEl.selectedIndex]?.text || '';
    const priority = document.getElementById('f_priority').value;
    const urgent   = document.getElementById('f_urgent').value.trim();
    const location = document.getElementById('f_location').value.trim();
    const desc     = document.getElementById('f_desc').value.trim();

    if (!title || !deptEl.value || !catEl.value) {
        await Modal.alert({ type: 'error', title: 'Validation Error', message: 'Title, Department, and Category are required.' });
        return;
    }
    if (priority === 'Urgent' && !urgent) {
        await Modal.alert({ type: 'error', title: 'Urgent Reason Required', message: 'Please provide an urgent reason for Urgent priority.' });
        return;
    }

    const rows = [
        { label: 'Title',      value: title },
        { label: 'Department', value: dept },
        { label: 'Category',   value: category },
        { label: 'Priority',   value: priority }
    ];
    if (location) rows.push({ label: 'Location', value: location });
    if (urgent)   rows.push({ label: 'Urgent Reason', value: urgent });
    if (desc)     rows.push({ label: 'Description', value: desc.length > 100 ? desc.substring(0,100)+'…' : desc });

    const confirmed = await Modal.review({
        title: 'Review Your Ticket',
        message: 'Please verify all details before submitting.',
        rows,
        confirmText: 'Submit Ticket'
    });
    if (confirmed) {
        Modal.loading(document.getElementById('reviewSubmitBtn'), 'Submitting...');
        document.getElementById('ticketForm').submit();
    }
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
