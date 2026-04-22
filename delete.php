<?php
require 'db.php';
requireLogin();

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

header('Location: index.php');
exit;
?>