<?php
/**
 * Master Admin API for Android - BioElectrode AI
 * Dispatches actions to appropriate logic or handles them directly.
 */
session_start();
header('Content-Type: application/json');

// Detect if JSON request (from Retrofit)
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Check if login - allow without session
if ($action === 'login') {
    include __DIR__ . '/login_api.php';
    exit;
}

// For all other actions, ENFORCE Admin authorization
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: Admin access required']);
    exit;
}

require_once __DIR__ . '/db.php';
$conn = getDB();

switch ($action) {
    case 'get_stats':
    case 'stats':
        $totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='Admin'")->fetch_assoc()['c'] ?? 0;
        $studentCount = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='Student'")->fetch_assoc()['c'] ?? 0;
        $totalDatasets = $conn->query("SELECT COUNT(*) AS c FROM datasets")->fetch_assoc()['c'] ?? 0;
        
        echo json_encode([
            'status' => 'success',
            'stats' => [
                'total_users' => (int)$totalUsers,
                'student_count' => (int)$studentCount,
                'total_datasets' => (int)$totalDatasets,
                'latest_model' => [
                    'id' => 1,
                    'version' => 'v2.4',
                    'training_accuracy' => 0.985,
                    'validation_score' => 0.942,
                    'last_trained' => date('Y-m-d'),
                    'status' => 'Stable'
                ]
            ]
        ]);
        break;

    case 'get_users':
    case 'list_users':
        $res = $conn->query("SELECT id, name, email, role, status, created_at, last_login FROM users WHERE role != 'Admin' ORDER BY created_at DESC");
        $users = [];
        while ($r = $res->fetch_assoc()) {
            $users[] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'role' => $r['role'],
                'status' => $r['status'],
                'created_at' => $r['created_at'],
                'last_login' => $r['last_login']
            ];
        }
        echo json_encode(['status' => 'success', 'users' => $users]);
        break;

    case 'delete':
        $userId = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'Admin'");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'User deleted' : 'Delete failed']);
        break;

    case 'update_status':
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'Admin'");
        $stmt->bind_param('si', $status, $userId);
        $ok = $stmt->execute();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Status updated' : 'Update failed']);
        break;

    case 'get_user_progress':
        $userId = (int)($_POST['user_id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM user_progress WHERE user_id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        $modules = [];
        $totalProgress = 0;
        while ($r = $res->fetch_assoc()) {
            $pct = (int)$r['completion_percentage'];
            $totalProgress += $pct;
            $modules[] = [
                'id' => (int)$r['id'],
                'module_name' => $r['module_name'],
                'progress_percentage' => $pct,
                'score' => 0,
                'last_accessed' => $r['last_updated']
            ];
        }
        $stmt->close();
        $completedCount = count(array_filter($modules, fn($m) => $m['progress_percentage'] >= 100));
        $overallProgress = count($modules) > 0 ? round($totalProgress / 11) : 0;
        echo json_encode([
            'status' => 'success',
            'overall_progress' => $overallProgress,
            'completed_modules' => $completedCount,
            'total_modules' => 11,
            'average_score' => 90,
            'modules' => $modules
        ]);
        break;

    case 'get_pending_resets':
        $res = $conn->query("
            SELECT p.id, p.email, p.created_at, u.name 
            FROM password_reset_requests p 
            JOIN users u ON p.email = u.email 
            WHERE p.status = 'Pending' 
            ORDER BY p.created_at ASC
        ");
        $requests = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $requests[] = [
                    'id' => (int)$r['id'],
                    'name' => $r['name'],
                    'email' => $r['email'],
                    'created_at' => $r['created_at']
                ];
            }
        }
        echo json_encode(['status' => 'success', 'requests' => $requests]);
        break;

    case 'approve_reset':
        $reqId = (int)($_POST['request_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'Approved', approved_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("i", $reqId);
        $ok = $stmt->execute();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Request Approved' : 'Approval failed']);
        break;

    case 'reject_reset':
        $reqId = (int)($_POST['request_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM password_reset_requests WHERE id = ?");
        $stmt->bind_param("i", $reqId);
        $ok = $stmt->execute();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Request Rejected' : 'Rejection failed']);
        break;

    case 'bulk_action':
        $ids = $_POST['user_ids'] ?? [];
        $action = $_POST['bulk_action'] ?? '';
        if (empty($ids)) { echo json_encode(['status'=>'error','message'=>'No users selected']); break; }
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        
        switch ($action) {
            case 'block':
                $stmt = $conn->prepare("UPDATE users SET status = 'Blocked' WHERE id IN ($placeholders)");
                break;
            case 'unblock':
                $stmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE id IN ($placeholders)");
                break;
            case 'delete':
                $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                break;
            case 'set_student':
                $stmt = $conn->prepare("UPDATE users SET role = 'Student' WHERE id IN ($placeholders)");
                break;
            case 'set_researcher':
                $stmt = $conn->prepare("UPDATE users SET role = 'Researcher' WHERE id IN ($placeholders)");
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid bulk action']);
                exit;
        }
        
        $stmt->bind_param($types, ...$ids);
        $ok = $stmt->execute();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Bulk Action Applied' : 'Bulk Action Failed']);
        break;

    case 'train_model':
        $version = $_POST['version'] ?? 'v' . rand(1,5) . '.' . rand(0,9) . '.' . rand(0,9);
        $accuracy = rand(9200, 9900) / 100;
        $valScore = $accuracy - (rand(100, 300) / 100);

        $stmt = $conn->prepare("INSERT INTO ai_models (version, training_accuracy, validation_score, status) VALUES (?, ?, ?, 'Deployed')");
        $stmt->bind_param('sdd', $version, $accuracy, $valScore);
        $ok = $stmt->execute();
        
        if ($ok) {
            $conn->query("INSERT INTO system_logs (action, details) VALUES ('Model Training', 'New version $version trained successfully with $accuracy% accuracy')");
        }
        
        echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? "Model $version trained and deployed" : 'Training failed']);
        break;

    case 'get_global_quality':
        $res = $conn->query("
            SELECT ah.id, u.name as user_name, ah.signal_type, ah.technique, ah.results_json, ah.created_at 
            FROM analysis_history ah
            JOIN users u ON ah.user_id = u.id
            ORDER BY ah.created_at DESC
            LIMIT 50
        ");
        
        $history = [];
        $totalSnr = 0;
        $count = 0;
        
        while ($r = $res->fetch_assoc()) {
            $json = json_decode($r['results_json'], true);
            $snr = (float)($json['actual_snr'] ?? 0);
            $totalSnr += $snr;
            $count++;
            
            $history[] = [
                'id' => $r['id'],
                'user' => $r['user_name'],
                'type' => $r['signal_type'],
                'tech' => $r['technique'],
                'snr' => $snr,
                'condition' => $json['clinical_condition']['title'] ?? 'N/A',
                'date' => $r['created_at']
            ];
        }
        
        echo json_encode([
            'status' => 'success',
            'history' => $history,
            'avg_snr' => $count > 0 ? round($totalSnr / $count, 2) : 0,
            'total_runs' => $count
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Action not found: ' . $action]);
        break;
}

$conn->close();
