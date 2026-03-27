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
    <script>
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body class="auth-body">
<div id="particles"></div>

<div class="auth-container">
    <div class="auth-left glass">
        <div class="auth-glow"></div>
        <div class="auth-brand fade-up">
            <div class="brand-icon glow-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5"/></svg>
            </div>
            <div class="brand-text">
                <h1>Secure Recovery</h1>
                <p>Verify your identity and regain control of your account through our encrypted recovery pipeline.</p>
            </div>
        </div>
        
        <div class="auth-badges fade-up delay-1">
            <div class="auth-badge glass"><span class="badge-dot blue"></span> 256-bit AES</div>
            <div class="auth-badge glass"><span class="badge-dot purple"></span> Biometric Sync</div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-container glass-card fade-up">
            <div class="auth-header">
                <h2>Account Recovery</h2>
                <p class="subtitle">Enter your registered email to initialize reset</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error glass fade-up">
                    <span class="alert-icon">⚠️</span>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success glass fade-up">
                    <span class="alert-icon">✅</span>
                    <span><?= $success ?></span>
                </div>
                <script>setTimeout(() => window.location.href = 'index.php', 3000);</script>
            <?php endif; ?>

            <?php if ($step === 1 && !$success): ?>
                <!-- STEP 1: VERIFY EMAIL -->
                <form method="POST" action="api/forgot_password_api.php" class="auth-form">
                    <input type="hidden" name="action" value="verify_email">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Interface</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-at-sign"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>
                            </span>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="name@gmail.com" required autocomplete="email">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary glow-blue">
                        <span>Check Identity</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-code"><path d="m9 9-2 2 2 2"/><path d="m15 9 2 2-2 2"/><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                </form>
            <?php elseif ($step === 2 && !$success): ?>
                <!-- STEP 2: NEW PASSWORD -->
                <form method="POST" action="api/forgot_password_api.php" class="auth-form">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" value="<?= $email ?>">
                    
                    <div class="alert alert-info glass" style="margin-bottom: 24px;">
                        <span class="alert-icon">🆔</span>
                        <span style="font-size: 0.85rem;">Account: <strong><?= $email ?></strong></span>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">New Access Key</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round"><path d="M2 18v3c0 .6.4 1 1 1h4v-3h3v-3h2l1.4-1.4a6.5 6.5 0 1 0-4-4Z"/><circle cx="16.5" cy="7.5" r=".5"/></svg>
                            </span>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="New Security Code" required minlength="8">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Key</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Repeat Security Code" required minlength="8">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary glow-purple">
                        <span>Update Pipeline</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-save"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="index.php">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
                    Return to Login Center
                </a>
            </div>
        </div>
    </div>
</div>
<script src="js/script.js"></script>
</body>
</html>
