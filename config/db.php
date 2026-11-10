<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "lovia_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// সেশন অলরেডি চালু না থাকলে তবেই চালু করবে (নোটিশ আসার সমস্যা দূর করবে)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>