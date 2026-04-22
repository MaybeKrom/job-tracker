<?php
require 'db.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];
$status = $data['status'];

$allowed = ['Applied', 'Got Call', 'Interview', 'Offer', 'Rejected'];
if (!in_array($status, $allowed)) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$status, $id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>