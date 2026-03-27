<?php
/**
 * fetch_quiz_history_api.php
 * Returns past quiz results for a user.
 */
session_start();
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$isJson = ($input !== null);

// Check authentication
$postedUserId = $isJson ? ($input['user_id'] ?? null) : ($_POST['user_id'] ?? null);

if (!isset($_SESSION['user_id'])) {
    if ($postedUserId) {
        $userId = (int)$postedUserId;
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
} else {
    $userId = (int)$_SESSION['user_id'];
}

require_once __DIR__ . '/db.php';
$conn = getDB();

$stmt = $conn->prepare("SELECT id, quiz_type, score, total_questions, completed_at FROM quiz_results WHERE user_id = ? ORDER BY completed_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'id' => (int)$row['id'],
        'quiz_type' => $row['quiz_type'],
        'score' => (int)$row['score'],
        'total_questions' => (int)$row['total_questions'],
        'completed_at' => $row['completed_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'count' => count($history),
    'history' => $history
]);
