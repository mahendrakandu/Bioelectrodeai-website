<?php
/**
 * Admin Profile Update API – BioElectrode AI
 */
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header('Location: ../index.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_profile.php'); exit;
}

require_once __DIR__ . '/db.php';
$conn   = getDB();
$userId = (int)$_SESSION['user_id'];

$fullName = trim($_POST['full_name']  ?? '');
$email    = trim($_POST['email']      ?? '');
$bio      = trim($_POST['bio']        ?? '');
$phone    = trim($_POST['phone']      ?? '');
$dept     = trim($_POST['department'] ?? '');
$newPass  = $_POST['new_password']    ?? '';
$confPass = $_POST['confirm_password'] ?? '';

// Validation
if (empty($fullName) || empty($email)) {
    header('Location: ../admin_profile.php?error=empty'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../admin_profile.php?error=email'); exit;
}
if (!empty($newPass) && strlen($newPass) < 6) {
    header('Location: ../admin_profile.php?error=short'); exit;
}
if (!empty($newPass) && $newPass !== $confPass) {
    header('Location: ../admin_profile.php?error=match'); exit;
}

// Check email uniqueness (exclude self)
$chk = $conn->prepare("SELECT id FROM users WHERE email=? AND id!=?");
$chk->bind_param('si', $email, $userId);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close(); $conn->close();
    header('Location: ../admin_profile.php?error=exists'); exit;
}
$chk->close();

// Profile photo upload
$profileImage = $_SESSION['profile_image'] ?? null;
if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
    $ext      = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','gif','webp'];
    $maxSize  = 3 * 1024 * 1024; // 3MB
    if (in_array($ext, $allowed) && $_FILES['profile_image']['size'] <= $maxSize) {
        $dir = '../uploads/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $dir . $newName)) {
            // Remove old photo
            if ($profileImage && file_exists('../' . $profileImage)) @unlink('../' . $profileImage);
            $profileImage = 'uploads/' . $newName;
        }
    }
}

// Build query
if (!empty($newPass)) {
    $hash  = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt  = $conn->prepare("UPDATE users SET name=?,email=?,bio=?,phone=?,department=?,password=?,profile_image=? WHERE id=? AND role='Admin'");
    $stmt->bind_param('sssssssi', $fullName, $email, $bio, $phone, $dept, $hash, $profileImage, $userId);
} else {
    $stmt  = $conn->prepare("UPDATE users SET name=?,email=?,bio=?,phone=?,department=?,profile_image=? WHERE id=? AND role='Admin'");
    $stmt->bind_param('ssssssi', $fullName, $email, $bio, $phone, $dept, $profileImage, $userId);
}

if ($stmt->execute()) {
    $_SESSION['user_name']     = $fullName;
    $_SESSION['user_email']    = $email;
    $_SESSION['user_bio']      = $bio;
    $_SESSION['user_phone']    = $phone;
    $_SESSION['user_dept']     = $dept;
    $_SESSION['profile_image'] = $profileImage;
    $stmt->close(); $conn->close();
    header('Location: ../admin_profile.php?success=1'); exit;
} else {
    $stmt->close(); $conn->close();
    header('Location: ../admin_profile.php?error=db'); exit;
}
