<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notifId = isset($_POST['notification_id']) ? filter_var($_POST['notification_id'], FILTER_VALIDATE_INT) : null;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    if ($notifId && $comment !== '' && $userId) {
        try {
            $stmt = $conn->prepare("INSERT INTO comments (notification_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$notifId, $userId, $comment]);

            echo json_encode(['success' => true, 'message' => 'Comment saved successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input or user not logged in.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
