<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';
$conn = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = $_POST['id'] ?? 0;
    $title   = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if (!$id || !$title || !$content) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE learning_content SET title = ?, content = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $id);

    if ($stmt->execute()) {
        // Log action
        $logStmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details) VALUES (?, 'Update Learning Content', ?)");
        $details = "Updated learning content ID: $id (Title: $title)";
        $logStmt->bind_param("is", $_SESSION['user_id'], $details);
        $logStmt->execute();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
$conn->close();
