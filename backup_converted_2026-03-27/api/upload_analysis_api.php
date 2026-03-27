<?php
session_start();
require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
// CORS headers added to support Android app access.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileTmpPath = 'None';
    if (isset($_FILES['dataset']) && $_FILES['dataset']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['dataset']['tmp_name'];
    }

    $signalType = $_POST['signal_type'] ?? $_POST['appType'] ?? 'Custom';
    $electrodeType = ucfirst(strtolower($_POST['electrode_type'] ?? $_POST['electrode'] ?? 'bipolar'));
    $noiseLevel = ucfirst(strtolower($_POST['noise_level'] ?? $_POST['noise'] ?? 'medium'));
    
    require_once __DIR__ . '/signal_analysis.php';
    $phpResponse = analyze_signal($fileTmpPath, $signalType, $noiseLevel, $electrodeType);

    if (isset($phpResponse['status']) && $phpResponse['status'] === 'success') {
        $jsonResponse = json_encode($phpResponse);
    } else {
        // Fallback for AI-specific logic if needed
        $command = "python ai_analysis.py " . escapeshellarg($fileTmpPath) . " " . escapeshellarg($signalType) . " " . escapeshellarg($noiseLevel) . " " . escapeshellarg($electrodeType) . " 2>&1";
        $output = shell_exec($command);
        if ($output) {
            $jsonStart = strpos($output, '{');
            $jsonResponse = ($jsonStart !== false) ? substr($output, $jsonStart) : json_encode(["status" => "error", "message" => "Engine error: " . $output]);
        } else {
            $jsonResponse = json_encode(["status" => "error", "message" => "Analysis failure"]);
        }
    }

    // Save history
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $conn = getDB();
        $technique = $electrodeType . ' - ' . $noiseLevel;
        $stmt = $conn->prepare("INSERT INTO analysis_history (user_id, signal_type, technique, results_json) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $signalType, $technique, $jsonResponse);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }

    echo $jsonResponse;
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
