<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['user_role'] === 'Admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';
$formData = $_SESSION['reg_form'] ?? [];
unset($_SESSION['reg_form']);

switch ($_GET['error'] ?? '') {
    case 'empty':  $error = 'All fields are required.'; break;
    case 'email':  $error = 'Invalid email format.'; break;
    case 'short':  $error = 'Password too short (min 6 characters).'; break;
    case 'match':  $error = 'Passwords do not match.'; break;
    case 'exists': $error = 'Email already exists. <a href="index.php" style="color:#93C5FD;">Login instead?</a>'; break;
    case 'fail':   $error = 'Registration failed. Please try again.'; break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – BioElectrode AI</title>
    <meta name="description" content="Create your BioElectrode AI account to access signal analysis, AI insights, and learning modules.">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <script>
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body>
<div class="auth-container">

    <!-- Left Panel -->
    <div class="auth-left">
        <div class="auth-brand fade-up">
            <div class="logo-icon">
                <!-- Lucide: Rocket -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5-4 5-4"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 4-5 4-5"/></svg>
            </div>
            <h1>Join the Platform</h1>
            <p>Start your journey with advanced bioelectrode signal analysis and AI-powered learning.</p>
        </div>
        <div class="auth-feature-list">
            <style>
                .feature-item-compact { padding: 8px 12px; margin-bottom: 8px; }
                .feature-text-compact { display: flex; flex-direction: column; line-height: 1.2; }
                .feature-text-compact strong { font-size: 0.85rem; color: rgba(255,255,255,0.95); }
                .feature-text-compact small { font-size: 0.7rem; color: rgba(255,255,255,0.7); margin-top: 2px; }
                .feature-icon-compact { width: 32px; height: 32px; font-size: 1.1rem; }
                .feature-icon-compact svg { width: 16px; height: 16px; }
            </style>
            <div class="feature-item feature-item-compact fade-up delay-1">
                <span class="feature-icon feature-icon-compact">📡</span>
                <span class="feature-text feature-text-compact">
                    <strong>Multi-Signal Support</strong>
                    <small>ECG, EEG, and EMG signals.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-2">
                <span class="feature-icon feature-icon-compact">⚡</span>
                <span class="feature-text feature-text-compact">
                    <strong>Dual Recording</strong>
                    <small>Bipolar & Monopolar configs.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-3">
                <span class="feature-icon feature-icon-compact">📈</span>
                <span class="feature-text feature-text-compact">
                    <strong>Real-Time Acquisition</strong>
                    <small>Live waveform graphs.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-4">
                <span class="feature-icon feature-icon-compact">🧠</span>
                <span class="feature-text feature-text-compact">
                    <strong>AI Signal Analysis</strong>
                    <small>Filtering & feature extraction.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-5">
                <span class="feature-icon feature-icon-compact">🎯</span>
                <span class="feature-text feature-text-compact">
                    <strong>Electrode Guidance</strong>
                    <small>Visual placement instructions.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-6">
                <span class="feature-icon feature-icon-compact">🔍</span>
                <span class="feature-text feature-text-compact">
                    <strong>Quality Assessment</strong>
                    <small>Noise & artifact detection.</small>
                </span>
            </div>
            <div class="feature-item feature-item-compact fade-up delay-7">
                <span class="feature-icon feature-icon-compact">📊</span>
                <span class="feature-text feature-text-compact">
                    <strong>Comparative Dashboard</strong>
                    <small>Side-by-side waveform comparisons.</small>
                </span>
            </div>
        </div>
    </div>

    <!-- Right Panel: Registration Form -->
    <div class="auth-right">
        <div class="auth-form-wrapper glass-card fade-up">
            <div class="auth-header">
                <div class="auth-icon-circle glow-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                </div>
                <h2>Create Account</h2>
                <p>Join our community of biomedical researchers</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error glass fade-up">
                    <span class="alert-icon">⚠️</span>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="api/register_api.php" id="registerForm" class="modern-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="inner-icon">👤</span>
                        <input type="text" id="name" name="name" class="form-control" 
                               placeholder="Dr. Jane Smith" required 
                               value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div>
                        <label for="email">Work Email</label>
                        <div class="input-wrapper">
                            <span class="inner-icon">✉️</span>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="name@gmail.com" required 
                                   value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div>
                        <label for="role">Specialization</label>
                        <div class="input-wrapper">
                            <select id="role" name="role" class="form-control" style="padding-left:16px;">
                                <option value="Student"    <?= ($formData['role']??'')!=='Student'    ?'':'selected' ?>>🎓 Student</option>
                                <option value="Researcher" <?= ($formData['role']??'')!=='Researcher' ?'':'selected' ?>>🔬 Researcher</option>
                                <option value="Educator"   <?= ($formData['role']??'')!=='Educator'   ?'':'selected' ?>>🏫 Educator</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                    <div>
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Min 6 chars" required style="padding-left:16px;">
                        </div>
                    </div>
                    <div>
                        <label for="confirm_password">Verify</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Repeat" required style="padding-left:16px;">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary glow-purple" id="registerBtn">
                    <span>Finalize Registration</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>

            <div class="auth-divider">
                <span>ALREADY HAVE AN ACCOUNT?</span>
            </div>

            <a href="index.php" class="btn btn-secondary glass">
                Sign In to Workspace
            </a>
        </div>
    </div>
</div>

<style>
.auth-header { text-align: center; margin-bottom: 24px; }
.auth-icon-circle { width: 64px; height: 64px; background: var(--g-purple); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #fff; }
.auth-icon-circle.glow-purple { box-shadow: 0 0 24px rgba(139, 92, 246, 0.4); }
.auth-icon-circle svg { width: 32px; height: 32px; }
.auth-header h2 { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; margin-bottom: 4px; }
.auth-header p { color: var(--text2); font-size: 0.9rem; }

.modern-form { display: flex; flex-direction: column; gap: 16px; }
.inner-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; filter: grayscale(1) opacity(0.6); }
.input-wrapper { position: relative; }
.input-wrapper .form-control { padding-left: 48px; height: 50px; background: rgba(0,0,0,0.2) !important; border-color: rgba(255,255,255,0.05); border-radius: 12px; }
.auth-divider { display: flex; align-items: center; gap: 16px; margin: 20px 0; color: var(--text3); font-size: 0.65rem; font-weight: 800; letter-spacing: 1px; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
</style>

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
