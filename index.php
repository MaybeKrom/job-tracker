<?php
require 'db.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$nickname = $_SESSION['nickname'];

$jobs = $pdo->prepare("SELECT * FROM jobs WHERE user_id = ? ORDER BY date_applied DESC");
$jobs->execute([$user_id]);
$jobs = $jobs->fetchAll();

$all = $pdo->prepare("SELECT 
    COUNT(*) as total,
    SUM(status='Got Call') as got_call,
    SUM(status='Interview') as interview,
    SUM(status='Offer') as offer,
    SUM(status='Rejected') as rejected
    FROM jobs WHERE user_id = ?");
$all->execute([$user_id]);
$stats = $all->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🎯 Job Tracker</h1>
        <div class="header-right">
            <span class="welcome">👋 <?= htmlspecialchars($nickname) ?></span>
            <a href="add.php" class="btn-add">+ Add Application</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats">
        <div class="stat"><span><?= $stats['total'] ?></span>Total</div>
        <div class="stat got-call"><span><?= $stats['got_call'] ?></span>Got Call</div>
        <div class="stat interview"><span><?= $stats['interview'] ?></span>Interview</div>
        <div class="stat offer"><span><?= $stats['offer'] ?></span>Offer 🎉</div>
        <div class="stat rejected"><span><?= $stats['rejected'] ?></span>Rejected</div>
    </div>

    <!-- Table -->
    <?php if (empty($jobs)): ?>
        <div class="empty">No applications yet. <a href="add.php">Add your first one!</a></div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Company</th>
                <th>Role</th>
                <th>Method</th>
                <th>Date Applied</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $i => $job): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
    <strong><?= htmlspecialchars($job['company']) ?></strong>
    <?php if ($job['source_text'] && filter_var($job['source_text'], FILTER_VALIDATE_URL)): ?>
        <br><a href="<?= htmlspecialchars($job['source_text']) ?>" target="_blank" class="source-link">🔗 View posting</a>
    <?php endif; ?>
</td>
                <td><?= htmlspecialchars($job['role']) ?></td>
                <td><span class="method-badge <?= strtolower($job['method']) ?>"><?= htmlspecialchars($job['method']) ?></span></td>
                <td><?= htmlspecialchars($job['date_applied']) ?></td>
                <td><?= htmlspecialchars($job['contact']) ?></td>
                <td>
                    <select class="status-select" data-id="<?= $job['id'] ?>">
                        <?php foreach (['Applied','Got Call','Interview','Offer','Rejected'] as $s): ?>
                            <option <?= $job['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
    <div class="notes-cell">
        <textarea class="notes-input" data-id="<?= $job['id'] ?>" 
            placeholder="Add notes, links, anything..."
            rows="2"><?= htmlspecialchars($job['notes']) ?></textarea>
        <span class="notes-saved" id="saved-<?= $job['id'] ?>">✓</span>
    </div>
</td>
                <td>
                    <a href="delete.php?id=<?= $job['id'] ?>" class="btn-delete" onclick="return confirm('Delete this?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
// Status update
document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
        fetch('update_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: this.dataset.id, status: this.value})
        });
        // Change row color based on status
        const row = this.closest('tr');
        row.className = '';
        if (this.value === 'Rejected') row.classList.add('row-rejected');
        if (this.value === 'Offer') row.classList.add('row-offer');
        if (this.value === 'Interview') row.classList.add('row-interview');
        if (this.value === 'Got Call') row.classList.add('row-gotcall');
    });
});

// Notes auto-save on typing (saves 1 second after you stop typing)
document.querySelectorAll('.notes-input').forEach(textarea => {
    let timer;
    textarea.addEventListener('input', function() {
        clearTimeout(timer);
        const id = this.dataset.id;
        const notes = this.value;
        const savedEl = document.getElementById('saved-' + id);
        savedEl.style.opacity = '0';
        timer = setTimeout(() => {
            fetch('update_notes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, notes: notes})
            }).then(() => {
                savedEl.style.opacity = '1';
                setTimeout(() => savedEl.style.opacity = '0', 2000);
            });
        }, 1000);
    });
});

// Apply row colors on page load based on current status
document.querySelectorAll('.status-select').forEach(select => {
    const row = select.closest('tr');
    if (select.value === 'Rejected') row.classList.add('row-rejected');
    if (select.value === 'Offer') row.classList.add('row-offer');
    if (select.value === 'Interview') row.classList.add('row-interview');
    if (select.value === 'Got Call') row.classList.add('row-gotcall');
});
</script>

</body>
</html>