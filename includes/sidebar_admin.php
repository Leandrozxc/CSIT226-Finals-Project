<?php $cur = basename($_SERVER['PHP_SELF']); ?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
      <rect width="36" height="36" rx="10" fill="rgba(255,255,255,.15)"/>
      <text x="18" y="25" text-anchor="middle" font-family="serif" font-size="15" fill="#fff" font-weight="700">HD</text>
    </svg>
    <div>
      <div class="sidebar-brand-text">University Helpdesk</div>
      <div class="sidebar-brand-sub">Admin Panel</div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php" class="<?= $cur==='dashboard.php'?'active':'' ?>">
      Dashboard
    </a>
    <a href="tickets.php" class="<?= $cur==='tickets.php'?'active':'' ?>">
      All Tickets
    </a>
    <div class="sidebar-section">Management</div>
    <a href="users.php" class="<?= $cur==='users.php'?'active':'' ?>">
      Users
    </a>
    <a href="departments.php" class="<?= $cur==='departments.php'?'active':'' ?>">
      Departments
    </a>
    <div class="sidebar-section">Reports</div>
    <a href="reports.php" class="<?= $cur==='reports.php'?'active':'' ?>">
      Reports
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
        <div class="user-role"><?= ucfirst($_SESSION['user_type']) ?></div>
      </div>
    </div>
    <a href="../logout.php" class="btn btn-secondary btn-sm btn-full" style="justify-content:center;">Logout</a>
  </div>
</aside>