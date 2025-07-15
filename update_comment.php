<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'], $_POST['comment'])) {
    $id = $_POST['notification_id'];
    $comment = $_POST['comment'];

    $stmt = $conn->prepare("UPDATE notifications SET comment = :comment WHERE notification_id = :id");
    $stmt->execute(['comment' => $comment, 'id' => $id]);
}

header("Location: view_notifications.php"); // Redirect back
exit;
?>
