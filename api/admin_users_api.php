<?php
/**
 * Admin Users Management API – BioElectrode AI
 * Actions: list_users, block, unblock, delete, change_role
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

    /* ── LIST ALL NON-ADMIN USERS ── */
    case 'list_users':
        $search = '%' . trim($_GET['search'] ?? '') . '%';
        $stmt = $conn->prepare(
            "SELECT id, name, email, role, status, created_at, last_login
             FROM users WHERE role != 'Admin' AND (name LIKE ? OR email LIKE ?)
             ORDER BY created_at DESC LIMIT 100"
        );
        $stmt->bind_param('ss', $search, $search);
        $stmt->execute();
        $res  = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'users' => $rows]);
        break;

    /* ── BLOCK USER ── */
    case 'block':
        $id   = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE users SET status='Blocked' WHERE id=? AND role!='Admin'");
        $stmt->bind_param('i', $id);
        $ok   = $stmt->execute();
        $stmt->close();
        logAction($conn, 'User Blocked', "User ID $id blocked by admin.");
        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    /* ── UNBLOCK USER ── */
    case 'unblock':
        $id   = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE users SET status='Active' WHERE id=? AND role!='Admin'");
        $stmt->bind_param('i', $id);
        $ok   = $stmt->execute();
        $stmt->close();
        logAction($conn, 'User Unblocked', "User ID $id unblocked by admin.");
        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    /* ── DELETE USER ── */
    case 'delete':
        $id   = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role!='Admin'");
        $stmt->bind_param('i', $id);
        $ok   = $stmt->execute();
        $stmt->close();
        logAction($conn, 'User Deleted', "User ID $id deleted by admin.");
        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    /* ── CHANGE ROLE ── */
    case 'change_role':
        $id      = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? '';
        $allowed = ['Student', 'Researcher', 'Educator'];
        if (!in_array($newRole, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid role']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=? AND role!='Admin'");
        $stmt->bind_param('si', $newRole, $id);
        $ok   = $stmt->execute();
        $stmt->close();
        logAction($conn, 'Role Changed', "User ID $id role changed to $newRole.");
        $conn->close();
        echo json_encode(['success' => $ok]);
        break;

    /* ── GET STATS ── */
    case 'stats':
        $total   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='Admin'")->fetch_assoc()['c'] ?? 0;
        $active  = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='Admin' AND status='Active'")->fetch_assoc()['c'] ?? 0;
        $blocked = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='Admin' AND status='Blocked'")->fetch_assoc()['c'] ?? 0;
        $conn->close();
        echo json_encode(['success' => true, 'total' => $total, 'active' => $active, 'blocked' => $blocked]);
        break;

    /* ── GET USER PROGRESS DETAILED ── */
    case 'get_user_progress':
        $id = (int)($_GET['user_id'] ?? 0);
        $stmt = $conn->prepare("
            SELECT module_name, completion_percentage, last_updated 
            FROM user_progress 
            WHERE user_id = ? 
            ORDER BY last_updated DESC
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $progress = [];
        while ($r = $res->fetch_assoc()) $progress[] = $r;
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'progress' => $progress]);
        break;

    default:
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}

/* ── Helper: log system action ── */
function logAction(mysqli $conn, string $action, string $details): void {
    // Only log if table exists
    $res = $conn->query("SHOW TABLES LIKE 'system_logs'");
    if ($res && $res->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO system_logs (action, details, created_at) VALUES (?,?,NOW())");
        if ($stmt) {
            $stmt->bind_param('ss', $action, $details);
            $stmt->execute();
            $stmt->close();
        }
    }
}
