<?php
require 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    if (!$nickname || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nickname = ?");
        $stmt->execute([$email, $nickname]);
        if ($stmt->fetch()) {
            $error = 'Email or nickname already taken.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nickname, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$nickname, $email, $hash]);
            $success = 'Account created! <a href="login.php">Login now</a>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Job Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-container">
    <div class="auth-box">
        <h2>🎯 Create Account</h2>
        <p class="auth-sub">Track your job applications like a pro</p>

        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nickname</label>
                <input type="text" name="nickname" placeholder="e.g. AbdulK" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="you@email.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Min 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm" placeholder="Repeat password" required>
            </div>
            <button type="submit" class="btn-submit">Create Account</button>
        </form>

        <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>
</body>
</html>