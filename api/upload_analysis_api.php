<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['dataset']) && $_FILES['dataset']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['dataset']['tmp_name'];
        
        // Execute Python script (assumes 'python' is in PATH)
        $command = "python ai_analysis.py " . escapeshellarg($fileTmpPath) . " 2>&1";
        $output = shell_exec($command);
        
        if ($output) {
            // Find the JSON part in the output in case there are warnings
            $jsonStart = strpos($output, '{');
            if ($jsonStart !== false) {
                $jsonResponse = substr($output, $jsonStart);
                echo $jsonResponse;
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid JSON from Python: " . $output]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Python script execution failed or empty output"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No file uploaded or upload error: " . ($_FILES['dataset']['error'] ?? 'Unknown error')]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
