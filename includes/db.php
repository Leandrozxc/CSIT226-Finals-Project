<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'csit226finals');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:20px;color:red;'>
        <h3>Database Connection Failed</h3>
        <p>" . $conn->connect_error . "</p>
        <p>Make sure MySQL is running in XAMPP and the database <strong>csit226finals</strong> exists.</p>
    </div>");
}
$conn->set_charset('utf8mb4');