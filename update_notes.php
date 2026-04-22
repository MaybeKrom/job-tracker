<?php
require 'db.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];
$notes = trim($data['notes']);

$stmt = $pdo->prepare("UPDATE jobs SET notes = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$notes, $id, $_SESSION['user_id']]);

echo json_encode(['success' => true]);
?>