<?php
session_start();
require_once __DIR__ . '/db.php';

// Detect if JSON request (from Android/Mobile)
$input = json_decode(file_get_contents('php://input'), true);
$isJson = ($input !== null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    } else {
        header('Location: ../index.php');
    }
    exit;
}

$email    = trim($isJson ? ($input['email'] ?? '') : ($_POST['email'] ?? ''));
$password = $isJson ? ($input['password'] ?? '') : ($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Empty email or password']);
    } else {
        header('Location: ../index.php?error=empty');
    }
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    } else {
        header('Location: ../index.php?error=invalid&email=' . urlencode($email));
    }
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

if ($user['status'] === 'Blocked') {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Your account is blocked']);
    } else {
        header('Location: ../index.php?error=blocked&email=' . urlencode($email));
    }
    $stmt->close();
    $conn->close();
    exit;
}

if (!password_verify($password, $user['password'])) {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    } else {
        header('Location: ../index.php?error=invalid&email=' . urlencode($email));
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Update last_login
$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$update->bind_param('i', $user['id']);
$update->execute();
$update->close();

// Set session for website
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email']= $user['email'];
$_SESSION['user_role'] = $user['role'];

$stmt->close();
$conn->close();

if ($isJson) {
    // Return JSON for Android
    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful',
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
} else {
    // Role-based redirect for Website
    if ($user['role'] === 'Admin') {
        header('Location: ../admin_dashboard.php');
    } else {
        header('Location: ../dashboard.php');
    }
}
exit;
