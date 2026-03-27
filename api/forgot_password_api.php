<?php
/**
 * Forgot Password API - BioElectrode AI
 * App-based flow: User requests reset -> Admin approves -> User resets password.
 */
session_start();
require_once 'db.php';

// Detect JSON request
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$isJson = ($input !== null);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    } else {
        header('Location: ../forgot_password.php');
    }
    exit;
}

$action = $isJson ? ($input['action'] ?? '') : ($_POST['action'] ?? '');
$email  = $isJson ? ($input['email'] ?? '')  : ($_POST['email'] ?? '');

$db = getDB();

// Ensure the table exists dynamically
$db->query("CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    status ENUM('Pending', 'Approved', 'Used') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ═══════════════════════════════════════════════════
// STEP 1: Verify Email & Request Admin Approval
// ═══════════════════════════════════════════════════
if ($action === 'verify_email') {
    if (empty($email)) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Email is required']);
        } else {
            header('Location: ../forgot_password.php');
        }
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, check for existing request
        $stmt2 = $db->prepare("SELECT id, status, approved_at FROM password_reset_requests WHERE email = ? ORDER BY id DESC LIMIT 1");
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $reqResult = $stmt2->get_result();

        if ($reqResult->num_rows > 0) {
            $req = $reqResult->fetch_assoc();
            if ($req['status'] === 'Pending') {
                if ($isJson) {
                    echo json_encode(['status' => 'pending', 'message' => 'Your request is pending admin approval. Check back later.']);
                } else {
                    header('Location: ../forgot_password.php?step=email&email=' . urlencode($email) . '&msg=pending');
                }
                exit;
            } elseif ($req['status'] === 'Approved') {
                // Check if the 2-minute window has expired
                $approvedTime = strtotime($req['approved_at']);
                if (time() - $approvedTime > 120) {
                    // Expired
                    $db->query("UPDATE password_reset_requests SET status = 'Expired' WHERE id = " . (int)$req['id']);
                    
                    if ($isJson) {
                        echo json_encode(['status' => 'error', 'message' => 'Your approval window (2 minutes) has expired. Please request a new one.']);
                    } else {
                        header('Location: ../forgot_password.php?step=email&email=' . urlencode($email) . '&msg=expired');
                    }
                    exit;
                }

                // Admin approved it within the time limit! Let them reset
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_approved'] = true;
                if ($isJson) {
                    echo json_encode(['status' => 'success', 'message' => 'Admin approved your request. Proceed to set new password.', 'email' => $email]);
                } else {
                    header('Location: ../forgot_password.php?step=reset&email=' . urlencode($email));
                }
                exit;
            }
        }

        // Ensure no pending/approved exists, then insert new request
        $stmt3 = $db->prepare("INSERT INTO password_reset_requests (email, status) VALUES (?, 'Pending')");
        $stmt3->bind_param("s", $email);
        $stmt3->execute();

        if ($isJson) {
            echo json_encode([
                'status' => 'success_requested',
                'message' => 'Request sent to Admin. You can reset your password once approved.',
                'email' => $email
            ]);
        } else {
            header('Location: ../forgot_password.php?step=email&email=' . urlencode($email) . '&msg=sent');
        }
    } else {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Email not found']);
        } else {
            header('Location: ../forgot_password.php?error=not_found');
        }
    }
    exit;
}

// ═══════════════════════════════════════════════════
// STEP 2: Reset Password (only after Admin Approved)
// ═══════════════════════════════════════════════════
if ($action === 'reset_password') {
    $password = $isJson ? ($input['password'] ?? '') : ($_POST['password'] ?? '');
    $confirm  = $isJson ? ($input['confirm_password'] ?? $input['password'] ?? '') : ($_POST['confirm_password'] ?? '');
    $email    = $isJson ? ($input['email'] ?? '') : ($_POST['email'] ?? '');

    if (empty($_SESSION['reset_approved']) || $_SESSION['reset_approved'] !== true) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Not approved by admin.']);
        } else {
            header('Location: ../forgot_password.php?error=not_approved');
        }
        exit;
    }

    if ($email !== ($_SESSION['reset_email'] ?? '')) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Email mismatch.']);
        } else {
            header('Location: ../forgot_password.php');
        }
        exit;
    }

    if (empty($email) || empty($password) || empty($confirm)) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
        } else {
            header('Location: ../forgot_password.php?step=reset&email=' . urlencode($email));
        }
        exit;
    }

    if ($password !== $confirm) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
        } else {
            header('Location: ../forgot_password.php?step=reset&email=' . urlencode($email) . '&error=mismatch');
        }
        exit;
    }

    if (strlen($password) < 6) {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters']);
        } else {
            header('Location: ../forgot_password.php?step=reset&email=' . urlencode($email) . '&error=short');
        }
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // Mark request as used
        $stmtU = $db->prepare("UPDATE password_reset_requests SET status = 'Used' WHERE email = ? AND status = 'Approved'");
        $stmtU->bind_param("s", $email);
        $stmtU->execute();

        unset($_SESSION['reset_email'], $_SESSION['reset_approved']);

        if ($isJson) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        } else {
            header('Location: ../forgot_password.php?success=1');
        }
    } else {
        if ($isJson) {
            echo json_encode(['status' => 'error', 'message' => 'Password update failed']);
        } else {
            header('Location: ../forgot_password.php?step=reset&email=' . urlencode($email) . '&error=failed');
        }
    }
    exit;
}

if ($isJson) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} else {
    header('Location: ../forgot_password.php');
}
exit;
