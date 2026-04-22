<?php
require 'db.php';
require 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents('php://input'), true);
$text = $data['text'] ?? '';

if (!$text) {
    echo json_encode(['success' => false, 'error' => 'No text provided']);
    exit;
}

$prompt = 'Extract job application details from the following webpage text. Return ONLY a valid JSON object with no extra text, no markdown, no backticks. Use these exact keys: company, role, contact (recruiter name/email/phone or empty string), notes (one useful sentence about the role or requirements, or empty string). Text: ' . $text;
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
$text = preg_replace('/```json|```/', '', $text);
$extracted = json_decode(trim($text), true);

if ($extracted) {
    echo json_encode(['success' => true, 'extracted' => $extracted]);
} else {
    echo json_encode(['success' => false, 'error' => 'Claude could not extract details']);
}
?>