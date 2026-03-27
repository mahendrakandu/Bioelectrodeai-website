<?php
/**
 * fetch_analysis_history_api.php
 * Returns an array of previous AI analysis results for the logged-in user.
 */
session_start();
header('Content-Type: application/json');

// Handle Android App (JSON) vs Web Session
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$isJson = ($input !== null);

// If request has a token or something, handle it. Since we are session-based:
if (!isset($_SESSION['user_id'])) {
    // Check if the Android app is sending user_id in the POST request directly 
    // (In a real app, you would use a Bearer token here instead of trusting direct user_id)
    $postedUserId = $isJson ? ($input['user_id'] ?? null) : ($_POST['user_id'] ?? null);
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

$stmt = $conn->prepare("SELECT id, signal_type, technique, results_json, created_at FROM analysis_history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = [
        'id' => (int)$row['id'],
        'signal_type' => $row['signal_type'],
        'technique' => $row['technique'],
        // Decode JSON back into an array to prevent double-encoding
        'results' => json_decode($row['results_json'], true),
        'created_at' => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'count' => count($history),
    'history' => $history
]);
