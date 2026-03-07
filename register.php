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
            <div class="feature-item fade-up delay-1">
                <span class="feature-icon">
                    <!-- Lucide: Graduation Cap -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </span>
                <span class="feature-text">Ready to master electrode recording?</span>
            </div>
            <div class="feature-item fade-up delay-2">
                <span class="feature-icon">
                    <!-- Lucide: Microscope -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 1 0 0-14h-1"/><path d="M9 14h2"/><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"/><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"/></svg>
                </span>
                <span class="feature-text">Interactive Simulators</span>
            </div>
            <div class="feature-item fade-up delay-3">
                <span class="feature-icon">
                    <!-- Lucide: Bot -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="#fff"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                </span>
                <span class="feature-text">AI Smart Recommendations</span>
            </div>
        </div>
    </div>

    <!-- Right Panel: Registration Form -->
    <div class="auth-right">
        <div class="auth-form-container fade-up">
            <h2>Create Account
                <!-- Lucide: Sparkles -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:var(--blue-l);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
            </h2>
            <p class="subtitle">Fill in your details to get started</p>

            <?php if ($error): ?><div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:18px;height:18px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?= $error ?>
            </div><?php endif; ?>

            <form method="POST" action="api/register_api.php" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: User -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="name" name="name" class="form-control"
                               placeholder="Dr. John Doe" required autocomplete="name"
                               value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: Mail -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        </span>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="you@example.com" required autocomplete="email"
                               value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Your Role</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: Tag -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>
                        </span>
                        <select id="role" name="role" class="form-control" style="padding-left:44px;">
                            <option value="Student"    <?= ($formData['role']??'')!=='Student'    ?'':'selected' ?>>🎓 Student</option>
                            <option value="Researcher" <?= ($formData['role']??'')!=='Researcher' ?'':'selected' ?>>🔬 Researcher</option>
                            <option value="Educator"   <?= ($formData['role']??'')!=='Educator'   ?'':'selected' ?>>🏫 Educator</option>
                        </select>
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
                               placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-pass" id="togglePass">
                            <!-- Lucide: Eye -->
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <!-- Lucide: Key -->
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
                        </span>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="registerBtn" data-loading-text="Creating Account...">
                    <span>Create Account</span>
                    <!-- Lucide: ArrowRight -->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>

            <div class="divider"><span>OR</span></div>
            <div class="auth-link">
                Already have an account? <a href="index.php">Sign in</a>
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
