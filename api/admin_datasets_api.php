<?php
/**
 * Admin Datasets Management API – BioElectrode AI
 * Actions: list_datasets, delete, update_status
 */
session_start();
header('Content-Type: application/json');

// Only Admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';
$conn = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    /* ── LIST ALL DATASETS ── */
    case 'list_datasets':
        $res = $conn->query("
            SELECT d.*, u.name as user_name 
            FROM datasets d 
            LEFT JOIN users u ON d.uploaded_by = u.id 
            ORDER BY d.upload_date DESC
        ");
        $rows = [];
        if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode(['success' => true, 'datasets' => $rows]);
        break;

    /* ── DELETE DATASET ── */
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        // Get file path first to delete physically if needed
        $stmt = $conn->prepare("SELECT file_path FROM datasets WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $ds = $res->fetch_assoc();
        $stmt->close();

        if ($ds) {
            $fpath = __DIR__ . '/../' . ltrim($ds['file_path'], '/');
            if (file_exists($fpath)) @unlink($fpath);

            $stmt = $conn->prepare("DELETE FROM datasets WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            
            logAction($conn, 'Dataset Deleted', "Dataset ID $id deleted by admin.");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Dataset not found']);
        }
        break;

    /* ── UPDATE STATUS ── */
    case 'update_status':
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowed = ['Raw', 'Processed', 'Training'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE datasets SET status=? WHERE id=?");
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        logAction($conn, 'Dataset Status Updated', "Dataset ID $id changed to $status.");
        echo json_encode(['success' => $ok]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

$conn->close();

function logAction(mysqli $conn, string $action, string $details): void {
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, details, created_at) VALUES (?,?,?,NOW())");
    $uid = $_SESSION['user_id'];
    $stmt->bind_param('iss', $uid, $action, $details);
    $stmt->execute();
    $stmt->close();
}
