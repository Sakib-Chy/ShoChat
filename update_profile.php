<?php
include 'config.php';

$newUsername = $_POST['username'];
$stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
$stmt->execute([$newUsername, $_SESSION['user_id']]);
?>