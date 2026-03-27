<?php
/**
 * submit_quiz_api.php
 * Handles submitting a completed quiz score to the database.
 */
session_start();
header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$isJson = ($input !== null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

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

$quizType      = $isJson ? ($input['quiz_type'] ?? '') : ($_POST['quiz_type'] ?? '');
$score         = $isJson ? ($input['score'] ?? 0) : ($_POST['score'] ?? 0);
$totalQuestions= $isJson ? ($input['total_questions'] ?? 0) : ($_POST['total_questions'] ?? 0);

if (empty($quizType) || $totalQuestions == 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid quiz data provided']);
    exit;
}

require_once __DIR__ . '/db.php';
$conn = getDB();

$stmt = $conn->prepare("INSERT INTO quiz_results (user_id, quiz_type, score, total_questions) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isii", $userId, $quizType, $score, $totalQuestions);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Quiz result saved']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save quiz result']);
}

$stmt->close();
$conn->close();
