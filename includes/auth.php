<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn() { return isset($_SESSION['user_id']); }

function requireLogin($base = '..') {
    if (!isLoggedIn()) { header("Location: $base/index.php"); exit(); }
}
function requireStaffOrAdmin($base = '..') {
    if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['admin','staff'])) {
        header("Location: $base/index.php"); exit();
    }
}
function requireAdmin($base = '..') {
    if (!isLoggedIn() || $_SESSION['user_type'] !== 'admin') {
        header("Location: $base/index.php"); exit();
    }
}
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}
function isStaff() {
    return isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin','staff']);
}
function generateTicketNo() {
    return 'TKT-' . strtoupper(substr(uniqid(), -6)) . '-' . date('y');
}
function statusBadge($s) {
    $map = ['Open'=>'badge-open','Assigned'=>'badge-assigned','In Progress'=>'badge-inprogress',
            'On Hold'=>'badge-onhold','Resolved'=>'badge-resolved','Closed'=>'badge-closed'];
    return $map[$s] ?? 'badge-open';
}
function priorityBadge($p) {
    $map = ['Low'=>'badge-low','Normal'=>'badge-normal','High'=>'badge-high','Urgent'=>'badge-urgent'];
    return $map[$p] ?? 'badge-normal';
}