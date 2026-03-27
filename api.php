<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Inputs
    $appType   = $_POST['appType'] ?? 'Custom';
    $electrode = $_POST['electrode'] ?? 'Bipolar';
    $noise     = $_POST['noise'] ?? 'Medium';
    
    $fileTmpPath = 'None';
    if (isset($_FILES['dataset']) && $_FILES['dataset']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['dataset']['tmp_name'];
    }

    // 2. Analysis (Try PHP library first)
    require_once __DIR__ . '/api/signal_analysis.php';
    $phpResponse = analyze_signal($fileTmpPath, $appType, $noise, $electrode);

    $jsonResponse = null;
    if (isset($phpResponse['status']) && $phpResponse['status'] === 'success') {
        $jsonResponse = json_encode($phpResponse);
    } else {
        // Fallback to Python engine
        $command = "python api/ai_analysis.py " . escapeshellarg($fileTmpPath) . " " . escapeshellarg($appType) . " " . escapeshellarg($noise) . " " . escapeshellarg($electrode) . " 2>&1";
        $output = shell_exec($command);
        if ($output) {
            $jsonStart = strpos($output, '{');
            $jsonResponse = ($jsonStart !== false) ? substr($output, $jsonStart) : json_encode(["status" => "error", "message" => "Engine error: " . $output]);
        } else {
            $jsonResponse = json_encode(["status" => "error", "message" => "Analysis engine failure"]);
        }
    }

    // 3. Persistent History
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/api/db.php';
        $conn = getDB();
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO analysis_history (user_id, signal_type, technique, results_json) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isss", $userId, $appType, $electrode, $jsonResponse);
            $stmt->execute();
            $stmt->close();
        }
        $conn->close();
    }

    // 4. Final Output
    echo $jsonResponse;

} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
