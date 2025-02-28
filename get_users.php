<?php
include 'config.php';

$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
$users = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($users);
?>