<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$step = 1; // 1: Enter Email, 2: Reset Password
$email = '';

if (isset($_GET['error'])) {
    if ($_GET['error'] === 'not_found') $error = 'Email not found in our records.';
    if ($_GET['error'] === 'mismatch') $error = 'Passwords do not match.';
    if ($_GET['error'] === 'failed') $error = 'Password update failed. Try again.';
}

if (isset($_GET['success'])) {
    $success = 'Password updated successfully! Redirecting to login...';
}

if (isset($_GET['email']) && !isset($_GET['success'])) {
    $email = htmlspecialchars($_GET['email']);
    $step = 2;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – BioElectrode AI</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        .auth-form-container { max-width: 450px; width: 100%; }
        .back-link { margin-top: 20px; text-align: center; }
        .back-link a { color: #94A3B8; text-decoration: none; font-size: 0.9rem; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px; }
        .back-link a:hover { color: var(--g-blue); }
    </style>
    <script>
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>
<div class="auth-container">
    <div class="auth-left">
        <div class="auth-brand fade-up">
            <div class="logo-icon">
                <!-- Lucide: ShieldCheck -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
            </div>
            <h1>Secure Reset</h1>
            <p>Verify your registered email to regain access to your account.</p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-container fade-up">
            <h2>Reset Password</h2>
            <p class="subtitle">Enter your registered email to update your password</p>

            <?php if ($error): ?><div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= $error ?>
            </div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <?= $success ?>
                </div>
                <script>setTimeout(() => window.location.href = 'index.php', 3000);</script>
            <?php endif; ?>

            <?php if ($step === 1 && !$success): ?>
                <!-- STEP 1: VERIFY EMAIL -->
                <form method="POST" action="api/forgot_password_api.php">
                    <input type="hidden" name="action" value="verify_email">
                    <div class="form-group">
                        <label for="email">Registered Email Address</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <!-- Lucide: Mail -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="you@example.com" required autocomplete="email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span>Check Email</span>
                        <!-- Lucide: ArrowRight -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </form>
            <?php elseif ($step === 2 && !$success): ?>
                <!-- STEP 2: NEW PASSWORD -->
                <form method="POST" action="api/forgot_password_api.php">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" value="<?= $email ?>">
                    
                    <div style="background: rgba(37,99,235,0.1); padding: 10px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(37,99,235,0.2);">
                        <p style="margin:0; font-size:0.85rem; color: #fff;">Email Verified: <strong><?= $email ?></strong></p>
                    </div>

                    <div class="form-group">
                        <label for="password">New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <!-- Lucide: Lock -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Min 8 characters" required minlength="8">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <!-- Lucide: Lock -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Re-enter password" required minlength="8">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="background: var(--g-blue);">
                        <span>Update Password</span>
                        <!-- Lucide: Check -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="m15 18-6-6 6-6"/></svg>
                    Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
