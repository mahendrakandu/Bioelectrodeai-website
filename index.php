<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'Admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';
switch ($_GET['error'] ?? '') {
    case 'invalid': $error = 'Invalid email or password. Please try again.'; break;
    case 'blocked': $error = 'Your account has been blocked. Contact support.'; break;
    case 'empty':   $error = 'Please enter your email and password.'; break;
}
$success = isset($_GET['logout'])
    ? 'You have been logged out successfully.'
    : (isset($_GET['registered']) ? 'Account created! Please log in below.' : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – BioElectrode AI</title>
    <meta name="description" content="Login to BioElectrode AI – your intelligent bioelectrode signal analysis platform.">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <script>
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>
<div class="auth-container">

    <!-- Left Panel: Branding -->
    <div class="auth-left">
        <div class="auth-brand fade-up">
            <div class="logo-icon">
                <!-- Lucide: Zap -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            </div>
            <h1>BioElectrode AI</h1>
            <p>Advanced signal analysis &amp; learning platform for bioelectrode research and education.</p>
        </div>
        <div class="auth-feature-list">
            <div class="feature-item fade-up delay-1">
                <span class="feature-icon">
                    <!-- Lucide: Heart Pulse -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l1.5-3 2 4 1.5-3h5.27"/></svg>
                </span>
                <span class="feature-text">ECG Heart Signals</span>
            </div>
            <div class="feature-item fade-up delay-2">
                <span class="feature-icon">
                    <!-- Lucide: Brain -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M12 5a3 3 0 1 0-5.997.125 4 4 0 0 0-2.526 5.77 4 4 0 0 0 .556 6.588A4 4 0 1 0 12 18Z"/><path d="M12 5a3 3 0 1 1 5.997.125 4 4 0 0 1 2.526 5.77 4 4 0 0 1-.556 6.588A4 4 0 1 1 12 18Z"/><path d="M15 13a4.5 4.5 0 0 1-3-4 4.5 4.5 0 0 1-3 4"/><path d="M17.599 6.5a3 3 0 0 0 .399-1.375"/><path d="M6.003 5.125A3 3 0 0 0 6.401 6.5"/><path d="M3.477 10.896a4 4 0 0 1 .585-.396"/><path d="M19.938 10.5a4 4 0 0 1 .585.396"/><path d="M6 18a4 4 0 0 1-1.967-.516"/><path d="M19.967 17.484A4 4 0 0 1 18 18"/></svg>
                </span>
                <span class="feature-text">EEG Brain Waves</span>
            </div>
            <div class="feature-item fade-up delay-3">
                <span class="feature-icon">
                    <!-- Lucide: Activity -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </span>
                <span class="feature-text">EMG Muscle Activity</span>
            </div>
            <div class="feature-item fade-up delay-4">
                <span class="feature-icon">
                    <!-- Lucide: BookOpen -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </span>
                <span class="feature-text">Step-by-Step Theory</span>
            </div>
        </div>
    </div>

    <!-- Right Panel: Login Form -->
    <div class="auth-right">
        <div class="auth-form-container fade-up">
            <h2>Welcome back
                <!-- Lucide: Hand Wave (using Sparkles as greeting) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:var(--blue-l);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
            </h2>
            <p class="subtitle">Sign in to your BioElectrode AI account to continue</p>

            <?php if ($error): ?><div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= $error ?>
            </div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?= $success ?>
            </div><?php endif; ?>

            <form method="POST" action="api/login_api.php" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: Mail -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="you@example.com" required autocomplete="email"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: Lock -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" id="togglePass" title="Show/hide password">
                            <!-- Lucide: Eye -->
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <span>Sign In</span>
                    <!-- Lucide: ArrowRight -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
                <div style="text-align: right; margin-top: 14px;">
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>
            </form>

            <style>
                .forgot-link {
                    font-size: 0.8rem;
                    color: rgba(255,255,255,0.4);
                    text-decoration: none;
                    transition: all 0.3s;
                    font-weight: 500;
                }
                .forgot-link:hover {
                    color: var(--blue-l);
                    text-decoration: underline;
                }
                .admin-login-hint {
                    margin-top: 22px;
                    background: rgba(220,38,38,0.07);
                    border: 1px solid rgba(220,38,38,0.22);
                    border-radius: 12px;
                    padding: 13px 16px;
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                }
                .admin-login-hint .hint-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 1px; }
                .admin-login-hint .hint-title { font-size: 0.82rem; font-weight: 700; color: #FCA5A5; margin-bottom: 2px; }
                .admin-login-hint .hint-body  { font-size: 0.77rem; color: rgba(255,255,255,0.45); line-height: 1.5; }
            </style>

            <div class="divider"><span>OR</span></div>

            <div class="auth-link">
                Don't have an account? <a href="register.php">Create account</a>
            </div>

            <!-- Admin login info -->
            <div class="admin-login-hint">
                <span class="hint-icon">🛡️</span>
                <div>
                    <div class="hint-title">Administrator Access</div>
                    <div class="hint-body">Admins use this same login page with their admin credentials. Upon login, you will be automatically redirected to the <strong style="color:#FCA5A5;">Admin Dashboard</strong>.</div>
                </div>
            </div>
        </div>
    </div>

</div>
<script src="js/script.js"></script>
<script>
// Toggle password visibility with icon swap
const togglePass = document.getElementById('togglePass');
const pwdInput  = document.getElementById('password');
const eyeIcon   = document.getElementById('eyeIcon');
if (togglePass) {
    togglePass.addEventListener('click', () => {
        const isHidden = pwdInput.type === 'password';
        pwdInput.type = isHidden ? 'text' : 'password';
        eyeIcon.innerHTML = isHidden
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>'
            : '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    });
}
</script>
</body>
</html>
