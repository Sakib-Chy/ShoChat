<?php
include 'config.php';

$type = $_GET['type'] ?? 'global';
$receiver = $_GET['receiver'] ?? null;

if ($type === 'global') {
    $stmt = $pdo->query("SELECT g.*, u.username 
                        FROM global_messages g 
                        JOIN users u ON g.user_id = u.id 
                        ORDER BY sent_at ASC");
} elseif ($type === 'private') {
    $stmt = $pdo->prepare("SELECT p.*, u.username 
                          FROM private_messages p 
                          JOIN users u ON p.sender_id = u.id 
                          WHERE (sender_id = ? AND receiver_id = ?) 
                          OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY sent_at ASC");
    $stmt->execute([$_SESSION['user_id'], $receiver, $receiver, $_SESSION['user_id']]);
}

header('Content-Type: application/json');
echo json_encode($stmt->fetchAll());
?>