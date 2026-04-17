<?php
session_start();

include_once("helpers/db.php");

if (!isset($_SESSION["user"])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION["user"]["id"];
$sql = "SELECT username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL hiba (prepare)");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $user = null;
} else {
    $user = $result->fetch_assoc();
}

$stmt->close();
?>