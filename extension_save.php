<?php
require 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// Extension uses session — check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in. Open job tracker first.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$company = $data['company'] ?? '';
$role    = $data['role'] ?? '';
$method  = $data['method'] ?? 'Website';
$contact = $data['contact'] ?? '';
$notes   = $data['notes'] ?? '';
$source_url = $data['source_url'] ?? '';

if (!$company || !$role) {
    echo json_encode(['success' => false, 'error' => 'Company or role missing']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO jobs (user_id, company, role, method, date_applied, contact, notes, source_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $company, $role, $method, date('Y-m-d'), $contact, $notes, $source_url]);

echo json_encode(['success' => true]);
?>