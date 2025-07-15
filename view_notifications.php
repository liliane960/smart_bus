<?php
require 'db.php';

// Fetch data
$stmt = $conn->query("SELECT * FROM notifications ORDER BY notification_id DESC");
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notifications Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Notifications</h2>
    <table class="table table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Bus ID</th>
                <th>Bus Log ID</th>
                <th>Message</th>
                <th>Sent At</th>
                <th>Status</th>
                <th>Comment</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['notification_id']) ?></td>
                <td><?= htmlspecialchars($row['bus_id']) ?></td>
                <td><?= htmlspecialchars($row['bus_log_id']) ?></td>
                <td><?= htmlspecialchars($row['message']) ?></td>
                <td><?= htmlspecialchars($row['sent_at']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td>
                    <form method="post" action="update_comment.php" class="d-flex">
                        <input type="hidden" name="notification_id" value="<?= $row['notification_id'] ?>">
                        <input type="text" name="comment" value="<?= htmlspecialchars($row['comment']) ?>" class="form-control form-control-sm me-2">
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </form>
                </td>
                <td>
                    <!-- Optionally, you could add Delete/Edit buttons here in the future -->
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
