<?php
include 'config.php';
$data = json_decode(file_get_contents('php://input'), true);

if ($data['type'] === 'global') {
    $stmt = $pdo->prepare("INSERT INTO global_messages (user_id, message) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $data['message']]);
} elseif ($data['type'] === 'private') {
    $stmt = $pdo->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $data['receiver'], $data['message']]);
}
?>