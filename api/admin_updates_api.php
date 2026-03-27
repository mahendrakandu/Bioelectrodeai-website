<?php
/**
 * Admin Updates Management API – BioElectrode AI
 * Actions: add_update, delete_update
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

    case 'add_update':
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'Announcement';

        if (empty($title) || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Title and description are required.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO app_items (title, description, type, added_date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('sss', $title, $description, $type);
        $ok = $stmt->execute();
        $stmt->close();

        // Log the action
        logSystemAction($conn, 'Update Added', "New platform update published: $title");

        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    case 'delete_update':
        $id = (int)($_POST['update_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM app_items WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        logSystemAction($conn, 'Update Deleted', "Platform update ID $id removed.");

        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    default:
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

function logSystemAction($conn, $action, $details) {
    $res = $conn->query("SHOW TABLES LIKE 'system_logs'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param('ss', $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    }
}
