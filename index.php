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

// Fetch latest update for the landing/login page
require_once __DIR__ . '/api/db.php';
$conn = getDB();
$publicUpdate = $conn->query("SELECT title, description FROM app_items ORDER BY added_date DESC LIMIT 1")->fetch_assoc();
$conn->close();
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
        <div class="glass-orb orb-1"></div>
        <div class="glass-orb orb-2"></div>
        <div class="glass-orb orb-3"></div>
        
        <div class="auth-brand fade-up">
            <div class="logo-icon-premium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M2 12h20"/><path d="m4.93 4.93 14.14 14.14"/><path d="m4.93 19.07 14.14-14.14"/></svg>
                <div class="logo-glow"></div>
            </div>
            <h1 class="gradient-text">BioElectrode AI</h1>
            <p class="brand-tagline">Master the future of bio-signal processing with artificial intelligence.</p>
            
            <?php if ($publicUpdate): ?>
            <!-- Dynamic Update Card on Login -->
            <div class="update-card-premium glass fade-up delay-1">
                <div class="update-header">
                    <span class="badge-pulse">LATEST</span>
                    <span class="update-date"><?= date('M d', strtotime($publicUpdate['added_date'] ?? 'now')) ?></span>
                </div>
                <h3><?= htmlspecialchars($publicUpdate['title']) ?></h3>
                <p><?= htmlspecialchars($publicUpdate['description']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="features-container">
            <div class="feature-glass-item fade-up delay-2">
                <span class="f-icon">📡</span>
                <div class="f-content">
                    <strong>Multi-Signal Engine</strong>
                    <span>Real-time ECG, EEG & EMG.</span>
                </div>
            </div>
            <div class="feature-glass-item fade-up delay-3">
                <span class="f-icon">🧠</span>
                <div class="f-content">
                    <strong>AI Feature Extraction</strong>
                    <span>Heuristic & Deep Learning.</span>
                </div>
            </div>
            <div class="feature-glass-item fade-up delay-4">
                <span class="f-icon">📊</span>
                <div class="f-content">
                    <strong>Advanced Dashboards</strong>
                    <span>Clinical-grade visualization.</span>
                </div>
            </div>
        </div>
    </div>

<style>
.logo-icon-premium { position: relative; width: 80px; height: 80px; background: var(--g-multi); border-radius: 22px; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.3); }
.logo-icon-premium svg { width: 40px; height: 40px; z-index: 2; }
.logo-glow { position: absolute; inset: -10px; background: var(--g-multi); filter: blur(20px); opacity: 0.4; z-index: 1; border-radius: 50%; }

.gradient-text { font-family: 'Space Grotesk', sans-serif; font-size: 2.5rem; font-weight: 800; background: linear-gradient(to bottom, #fff, #94A3B8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 12px; }
.brand-tagline { color: rgba(255,255,255,0.7); font-size: 1rem; max-width: 320px; margin: 0 auto 40px; }

.update-card-premium { padding: 20px; border-radius: var(--r); text-align: left; max-width: 360px; margin: 0 auto; border: 1px solid rgba(255,255,255,0.1); }
.update-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.badge-pulse { background: var(--pink); color: #fff; font-size: 0.65rem; font-weight: 900; padding: 4px 10px; border-radius: 20px; box-shadow: 0 0 10px var(--pink); }
.update-date { font-size: 0.75rem; color: var(--text3); }
.update-card-premium h3 { font-size: 1rem; color: #fff; margin-bottom: 8px; font-weight: 700; }
.update-card-premium p { font-size: 0.8rem; color: var(--text2); line-height: 1.5; text-align: left; margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.features-container { margin-top: 60px; display: flex; flex-direction: column; gap: 12px; width: 100%; max-width: 320px; }
.feature-glass-item { display: flex; align-items: center; gap: 16px; padding: 12px 20px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; backdrop-filter: blur(10px); transition: 0.3s; }
.feature-glass-item:hover { background: rgba(255,255,255,0.1); transform: translateX(10px); }
.f-icon { font-size: 1.5rem; }
.f-content { display: flex; flex-direction: column; text-align: left; }
.f-content strong { font-size: 0.85rem; color: #fff; }
.f-content span { font-size: 0.75rem; color: rgba(255,255,255,0.6); }
</style>

    <!-- Right Panel: Login Form -->
    <div class="auth-right">
        <div class="auth-form-wrapper glass-card fade-up">
            <div class="auth-header">
                <div class="auth-icon-circle glow-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </div>
                <h2>Welcome Back</h2>
                <p>Enter your credentials to access your workspace</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error glass fade-up">
                    <span class="alert-icon">⚠️</span>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success glass fade-up">
                    <span class="alert-icon">✨</span>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="api/login_api.php" id="loginForm" class="modern-form">
                <div class="form-group">
                    <label for="email">Work Email</label>
                    <div class="input-wrapper">
                        <span class="inner-icon">✉️</span>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="name@gmail.com" required 
                               value="<?= htmlspecialchars($_GET['email'] ?? $_POST['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Security Password</label>
                    <div class="input-wrapper">
                        <span class="inner-icon">🔒</span>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="••••••••" required>
                        <button type="button" class="toggle-pass" id="togglePass">👁️</button>
                    </div>
                    <div class="form-footer">
                        <a href="forgot_password.php" class="text-link">Forgot password?</a>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary glow-blue" id="loginBtn">
                    <span>Account Login</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>

            <div class="auth-divider">
                <span>NEW TO THE PLATFORM?</span>
            </div>

            <a href="register.php" class="btn btn-secondary glass">
                Create Researcher Account
            </a>

            <div class="admin-hint glass">
                <span class="hint-icon">🛡️</span>
                <p><strong>Admin Access:</strong> Use your privileged credentials. System will auto-detect your role.</p>
            </div>
        </div>
    </div>
</div>

<style>
.auth-header { text-align: center; margin-bottom: 32px; }
.auth-icon-circle { width: 64px; height: 64px; background: var(--g-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #fff; }
.auth-icon-circle svg { width: 32px; height: 32px; }
.auth-header h2 { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 8px; }
.auth-header p { color: var(--text2); font-size: 0.9rem; }

.modern-form { display: flex; flex-direction: column; gap: 20px; }
.inner-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; filter: grayscale(1) opacity(0.6); }
.input-wrapper { position: relative; }
.input-wrapper .form-control { padding-left: 48px; height: 52px; background: rgba(0,0,0,0.2) !important; border-color: rgba(255,255,255,0.05); }
.form-footer { display: flex; justify-content: flex-end; margin-top: 8px; }
.text-link { font-size: 0.8rem; color: var(--blue-l); font-weight: 600; opacity: 0.8; transition: 0.3s; }
.text-link:hover { opacity: 1; text-decoration: underline; }

.auth-divider { display: flex; align-items: center; gap: 16px; margin: 24px 0; color: var(--text3); font-size: 0.65rem; font-weight: 800; letter-spacing: 1px; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

.admin-hint { margin-top: 32px; padding: 16px; border-radius: var(--r-sm); display: flex; gap: 12px; align-items: flex-start; }
.admin-hint .hint-icon { font-size: 1.2rem; margin-top: 2px; }
.admin-hint p { font-size: 0.75rem; color: var(--text2); line-height: 1.5; margin: 0; }
</style>
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
