<?php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: ../index.php?error=empty');
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php?error=invalid');
    $stmt->close();
    $conn->close();
    exit;
}

$user = $result->fetch_assoc();

if ($user['status'] === 'Blocked') {
    header('Location: ../index.php?error=blocked');
    $stmt->close();
    $conn->close();
    exit;
}

if (!password_verify($password, $user['password'])) {
    header('Location: ../index.php?error=invalid');
    $stmt->close();
    $conn->close();
    exit;
}

// Update last_login
$update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$update->bind_param('i', $user['id']);
$update->execute();
$update->close();

// Set session
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_email']= $user['email'];
$_SESSION['user_role'] = $user['role'];

$stmt->close();
$conn->close();

// Role-based redirect
if ($user['role'] === 'Admin') {
    header('Location: ../admin_dashboard.php');
} else {
    header('Location: ../dashboard.php');
}
exit;
