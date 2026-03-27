<?php
session_start();
require_once __DIR__ . '/db.php';

// Detect if JSON request (from Android/Mobile)
$input = json_decode(file_get_contents('php://input'), true);
$isJson = ($input !== null);

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    } else {
        header('Location: ../register.php');
    }
    exit;
}

$name     = trim($isJson ? ($input['name'] ?? $input['fullname'] ?? '') : ($_POST['name'] ?? ''));
$email    = trim($isJson ? ($input['email'] ?? '') : ($_POST['email'] ?? ''));
$password = $isJson ? ($input['password'] ?? '') : ($_POST['password'] ?? '');
$confirm  = $isJson ? ($input['confirm_password'] ?? $input['password'] ?? '') : ($_POST['confirm_password'] ?? '');
$role     = trim($isJson ? ($input['role'] ?? 'Student') : ($_POST['role'] ?? 'Student'));

// Basic validation
if (empty($name) || empty($email) || empty($password)) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    } else {
        $_SESSION['reg_form'] = $_POST;
        header('Location: ../register.php?error=empty');
    }
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    } else {
        $_SESSION['reg_form'] = $_POST;
        header('Location: ../register.php?error=email');
    }
    exit;
}

if (substr(strtolower($email), -10) !== '@gmail.com') {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Email must be in the format: name@gmail.com']);
    } else {
        $_SESSION['reg_form'] = $_POST;
        header('Location: ../register.php?error=format');
    }
    exit;
}

if (strlen($password) < 6) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Password too short']);
    } else {
        $_SESSION['reg_form'] = $_POST;
        header('Location: ../register.php?error=short');
    }
    exit;
}

if (!$isJson && $password !== $confirm) {
    $_SESSION['reg_form'] = $_POST;
    header('Location: ../register.php?error=match');
    exit;
}

$conn = getDB();

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    } else {
        $_SESSION['reg_form'] = $_POST;
        header('Location: ../register.php?error=exists');
    }
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Insert user
// Handle roles from both Web and Admin/Android
$allowed_web = ['Student','Researcher','Educator'];
$allowed_app = [
    'Biomedical Engineering Student',
    'Medical Student',
    'Neuroscience Researcher',
    'Neurology Resident',
    'Clinical Engineer',
    'Healthcare Professional',
    'Others',
    'Admin'
];
$all_allowed = array_merge($allowed_web, $allowed_app);

if (!in_array($role, $all_allowed)) $role = 'Student';

$hashed = password_hash($password, PASSWORD_BCRYPT);
$status = 'Active';

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?,?,?,?,?,NOW())");
$stmt->bind_param('sssss', $name, $email, $hashed, $role, $status);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    if ($isJson) {
        echo json_encode(['status' => 'success', 'message' => 'Account created successfully']);
    } else {
        header('Location: ../index.php?registered=1');
    }
} else {
    $stmt->close();
    $conn->close();
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create account']);
    } else {
        header('Location: ../register.php?error=fail');
    }
}
exit;
