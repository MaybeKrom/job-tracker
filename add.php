<?php
require 'db.php';
require 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$extracted = null;

// Handle AI extraction
if (isset($_POST['action']) && $_POST['action'] === 'extract') {
    $pasted_text = trim($_POST['pasted_text']);
    
    if ($pasted_text) {
        $prompt = 'Extract job application details from the following text. Return ONLY a valid JSON object with no extra text, no markdown, no backticks. Use these exact keys: company, role, method (one of: LinkedIn, Email, WhatsApp, Website, Other), contact (recruiter name/email/phone or empty string), notes (one useful sentence or empty string). Text: ' . $pasted_text;

        $data = [
            "model" => "claude-haiku-4-5",
            "max_tokens" => 300,
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ]
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . CLAUDE_API_KEY,
            'anthropic-version: 2023-06-01'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        $text = $result['content'][0]['text'] ?? '{}';
        $text = preg_replace('/```json|```/','', $text);
        $extracted = json_decode(trim($text), true);

        if (!$extracted) {
            $error = 'Extraction failed. Response: ' . htmlspecialchars($response);
        }
    }
}

// Handle manual save
if (isset($_POST['action']) && $_POST['action'] === 'save') {
    $company      = trim($_POST['company']);
    $role         = trim($_POST['role']);
    $method       = $_POST['method'];
    $date_applied = $_POST['date_applied'];
    $contact      = trim($_POST['contact']);
    $notes        = trim($_POST['notes']);
    $source_text  = trim($_POST['source_text']);

    if (!$company || !$role) {
        $error = 'Company and Role are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO jobs (user_id, company, role, method, date_applied, contact, notes, source_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $company, $role, $method, $date_applied, $contact, $notes, $source_text]);
        header('Location: index.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Application - Job Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎯 Job Tracker</h1>
        <div class="header-right">
            <span class="welcome">👋 <?= htmlspecialchars($_SESSION['nickname']) ?></span>
            <a href="index.php" class="btn-add">← Dashboard</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <div class="add-container">
        <h2>Add New Application</h2>

        <!-- AI Paste Box -->
        <div class="ai-box">
            <div class="ai-label">🤖 AI Auto-Extract</div>
            <p class="ai-desc">Paste a LinkedIn job post, email, job description, or anything — AI will extract the details for you.</p>
            <form method="POST">
                <input type="hidden" name="action" value="extract">
                <textarea name="pasted_text" placeholder="Paste job post, email, WhatsApp message, or any text here..." rows="5"></textarea>
                <button type="submit" class="btn-submit" style="width:auto; padding: 10px 24px;">⚡ Extract with AI</button>
            </form>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <!-- Application Form -->
        <form method="POST" class="app-form">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="source_text" value="<?= htmlspecialchars($_POST['pasted_text'] ?? '') ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>Company *</label>
                    <input type="text" name="company" required
                        value="<?= htmlspecialchars($extracted['company'] ?? $_POST['company'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <input type="text" name="role" required
                        value="<?= htmlspecialchars($extracted['role'] ?? $_POST['role'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>How did you apply?</label>
                    <select name="method">
                        <?php foreach (['LinkedIn','Email','WhatsApp','Website','Other'] as $m): ?>
                            <option <?= ($extracted['method'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date Applied</label>
                    <input type="date" name="date_applied" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact (recruiter name/email/phone)</label>
                    <input type="text" name="contact"
                        value="<?= htmlspecialchars($extracted['contact'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <input type="text" name="notes"
                        value="<?= htmlspecialchars($extracted['notes'] ?? '') ?>">
                </div>
            </div>

            <button type="submit" class="btn-submit" style="width:auto; padding: 12px 32px;">💾 Save Application</button>
        </form>
    </div>
</div>
</body>
</html>