<?php
session_start();
if (!isset($_SESSION['user_id']))       { header('Location: index.php'); exit; }
if ($_SESSION['user_role'] !== 'Admin') { header('Location: dashboard.php'); exit; }

require_once 'api/db.php';
$conn   = getDB();
$userId = (int)$_SESSION['user_id'];

// Fetch full admin record
$stmt = $conn->prepare("SELECT id,name,email,role,status,bio,profile_image,phone,department,created_at,last_login FROM users WHERE id=? AND role='Admin'");
$stmt->bind_param('i', $userId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$admin) { header('Location: index.php'); exit; }

$adminName    = htmlspecialchars($admin['name']);
$adminInitial = strtoupper(substr($admin['name'], 0, 1));
$adminEmail   = htmlspecialchars($admin['email']);
$adminBio     = htmlspecialchars($admin['bio'] ?? '');
$adminPhone   = htmlspecialchars($admin['phone'] ?? '');
$adminDept    = htmlspecialchars($admin['department'] ?? '');
$joined       = date('d M Y', strtotime($admin['created_at']));
$lastLogin    = $admin['last_login'] ? date('d M Y, H:i', strtotime($admin['last_login'])) : 'N/A';

// Alert messages
$errorMsgs = [
    'empty'  => 'Name and Email are required.',
    'email'  => 'Please enter a valid email address.',
    'short'  => 'New password must be at least 6 characters.',
    'match'  => 'New passwords do not match.',
    'exists' => 'That email is already in use by another account.',
    'db'     => 'Database error. Please try again.',
];
$error   = $errorMsgs[$_GET['error'] ?? ''] ?? '';
$success = isset($_GET['success']) ? 'Profile updated successfully!' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile – BioElectrode AI</title>
    <meta name="description" content="Manage your BioElectrode AI administrator profile.">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="css/admin.css?v=<?= time() ?>">
    <script>
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
</head>
<body class="dashboard-body admin-body">
<div id="particles"></div>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">🛡️</div>
        <div class="brand-text">
            <div class="brand-name">BioElectrode AI</div>
            <div class="brand-tag">Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-user admin-user">
        <div class="user-avatar" style="overflow:hidden;">
            <?php if (!empty($admin['profile_image']) && file_exists($admin['profile_image'])): ?>
                <img src="<?= htmlspecialchars($admin['profile_image']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
                <?= $adminInitial ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="u-name"><?= $adminName ?></div>
            <div class="u-role" style="background:rgba(220,38,38,0.15);color:#FCA5A5;">🔑 Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Overview</div>
        <a href="admin_dashboard.php" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-item-label">Dashboard</span>
        </a>
        <div class="nav-label">Management</div>
        <a href="admin_dashboard.php#section-users" class="nav-item">
            <span class="nav-icon">👥</span>
            <span class="nav-item-label">Manage Users</span>
        </a>
        <a href="admin_dashboard.php#section-datasets" class="nav-item">
            <span class="nav-icon">🗄️</span>
            <span class="nav-item-label">Datasets</span>
        </a>
        <a href="admin_dashboard.php#section-ai" class="nav-item">
            <span class="nav-icon">🧠</span>
            <span class="nav-item-label">AI Control</span>
        </a>
        <div class="nav-label">System</div>
        <a href="admin_settings.php" class="nav-item">
            <span class="nav-icon">⚙️</span>
            <span class="nav-item-label">System Settings</span>
        </a>
        <a href="admin_profile.php" class="nav-item active">
            <span class="nav-icon">👤</span>
            <span class="nav-item-label">Admin Profile</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="api/logout_api.php" class="logout-btn">
            <span>🚪</span>
            <span class="logout-label">Logout from Admin</span>
        </a>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<div class="main-content" id="mainArea">
    <header class="topbar">
        <div class="topbar-left">
            <button class="tb-btn sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">☰</button>
            <div class="topbar-title">
                <h1>👤 Admin Profile</h1>
                <p>Manage your administrator account details</p>
            </div>
        </div>
        <div class="tb-right">
            <div class="tb-user">
                <div class="tb-avatar" style="background:linear-gradient(135deg,#9d174d,#DB2777);overflow:hidden;">
                    <?php if (!empty($admin['profile_image']) && file_exists($admin['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($admin['profile_image']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <?= $adminInitial ?>
                    <?php endif; ?>
                </div>
                <span class="tb-user-name"><?= $adminName ?></span>
            </div>
        </div>
    </header>

    <div class="content-area">

        <!-- Alerts -->
        <?php if ($error): ?>
        <div class="alert-strip error">
            <span>❌</span> <?= htmlspecialchars($error) ?>
            <button onclick="this.parentElement.remove()" class="alert-close">✕</button>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert-strip success">
            <span>✅</span> <?= htmlspecialchars($success) ?>
            <button onclick="this.parentElement.remove()" class="alert-close">✕</button>
        </div>
        <?php endif; ?>

        <!-- Profile Hero Card -->
        <div class="profile-hero">
            <div class="profile-hero-bg"></div>
            <div class="profile-hero-content">
                <div class="profile-avatar-wrap" id="avatarWrap" onclick="document.getElementById('photoInput').click()" title="Click to change photo">
                    <div class="profile-avatar-inner">
                        <?php if (!empty($admin['profile_image']) && file_exists($admin['profile_image'])): ?>
                            <img id="avatarPreview" src="<?= htmlspecialchars($admin['profile_image']) ?>" alt="Profile Photo">
                        <?php else: ?>
                            <div class="profile-avatar-initial" id="avatarInitial"><?= $adminInitial ?></div>
                            <img id="avatarPreview" src="" alt="Profile Photo" style="display:none;">
                        <?php endif; ?>
                    </div>
                    <div class="avatar-edit-overlay">
                        <span>📷</span>
                        <small>Change Photo</small>
                    </div>
                </div>
                <div class="profile-hero-info">
                    <h2><?= $adminName ?></h2>
                    <div class="profile-hero-badges">
                        <span class="phb phb-red">🔑 Administrator</span>
                        <span class="phb phb-green">✅ <?= htmlspecialchars($admin['status']) ?></span>
                    </div>
                    <div class="profile-hero-meta">
                        <span>✉️ <?= $adminEmail ?></span>
                        <?php if ($adminPhone): ?><span>📞 <?= $adminPhone ?></span><?php endif; ?>
                        <?php if ($adminDept): ?><span>🏢 <?= $adminDept ?></span><?php endif; ?>
                        <span>📅 Joined <?= $joined ?></span>
                        <span>🕐 Last login: <?= $lastLogin ?></span>
                    </div>
                    <?php if ($adminBio): ?>
                    <div style="margin-top:10px;font-size:0.82rem;color:rgba(255,255,255,0.55);max-width:560px;line-height:1.6;"><?= $adminBio ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Two-column edit layout -->
        <div class="profile-grid">

            <!-- Left: Edit Form -->
            <form method="POST" action="api/admin_profile_api.php" enctype="multipart/form-data" id="profileForm">
                <input type="file" id="photoInput" name="profile_image" accept="image/*" style="display:none;" onchange="previewPhoto(this)">

                <!-- Basic Info -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">📝</span>Basic Information</div>
                    </div>

                    <div class="pf-grid">
                        <div class="pf-group">
                            <label class="pf-label">Full Name</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">👤</span>
                                <input type="text" name="full_name" class="pf-input" value="<?= $adminName ?>" placeholder="Your full name" required>
                            </div>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Email Address</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">✉️</span>
                                <input type="email" name="email" class="pf-input" value="<?= $adminEmail ?>" placeholder="admin@example.com" required>
                            </div>
                        </div>
                    </div>

                    <div class="pf-grid" style="margin-top:16px;">
                        <div class="pf-group">
                            <label class="pf-label">Phone Number</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">📞</span>
                                <input type="text" name="phone" class="pf-input" value="<?= $adminPhone ?>" placeholder="+91-XXXXXXXXXX">
                            </div>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Department</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">🏢</span>
                                <input type="text" name="department" class="pf-input" value="<?= $adminDept ?>" placeholder="e.g. Platform Administration">
                            </div>
                        </div>
                    </div>

                    <div class="pf-group" style="margin-top:16px;">
                        <label class="pf-label">Role (Read-only)</label>
                        <div class="pf-input-wrap">
                            <span class="pf-icon">🔑</span>
                            <input type="text" class="pf-input" value="Administrator" readonly style="opacity:0.5;cursor:not-allowed;">
                        </div>
                    </div>

                    <div class="pf-group" style="margin-top:16px;">
                        <label class="pf-label">Bio / About</label>
                        <textarea name="bio" class="pf-textarea" placeholder="Tell us a little about yourself..." rows="3"><?= $adminBio ?></textarea>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">🔒</span>Change Password</div>
                        <span class="panel-note">Leave blank to keep current password</span>
                    </div>

                    <div class="pf-grid">
                        <div class="pf-group">
                            <label class="pf-label">New Password</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">🔒</span>
                                <input type="password" name="new_password" id="newPass" class="pf-input" placeholder="Min. 6 characters">
                                <button type="button" class="pf-eye" onclick="togglePw('newPass','eye1')">
                                    <span id="eye1">👁️</span>
                                </button>
                            </div>
                        </div>
                        <div class="pf-group">
                            <label class="pf-label">Confirm New Password</label>
                            <div class="pf-input-wrap">
                                <span class="pf-icon">🔒</span>
                                <input type="password" name="confirm_password" id="confPass" class="pf-input" placeholder="Repeat new password">
                                <button type="button" class="pf-eye" onclick="togglePw('confPass','eye2')">
                                    <span id="eye2">👁️</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Password strength -->
                    <div class="pw-strength-wrap" id="pwStrengthWrap" style="display:none;margin-top:12px;">
                        <div class="pw-strength-bar">
                            <div id="pwStrengthFill" class="pw-fill"></div>
                        </div>
                        <span id="pwStrengthLabel" class="pw-label"></span>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pf-submit-row">
                    <a href="admin_dashboard.php" class="pf-btn pf-btn-secondary">← Back to Dashboard</a>
                    <button type="submit" class="pf-btn pf-btn-primary" id="saveBtn">
                        <span id="saveBtnText">💾 Save Changes</span>
                    </button>
                </div>
            </form>

            <!-- Right: Info cards -->
            <div class="profile-right-col">

                <!-- Account Details Card -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">🛡️</span>Account Details</div>
                    </div>
                    <div class="acc-detail-list">
                        <div class="acc-row">
                            <span class="acc-label">Admin ID</span>
                            <span class="acc-value mono">#<?= $admin['id'] ?></span>
                        </div>
                        <div class="acc-row">
                            <span class="acc-label">Role</span>
                            <span class="acc-value"><span class="status-badge admin-badge">🔑 Administrator</span></span>
                        </div>
                        <div class="acc-row">
                            <span class="acc-label">Status</span>
                            <span class="acc-value"><span class="status-badge active">✅ Active</span></span>
                        </div>
                        <div class="acc-row">
                            <span class="acc-label">Account Created</span>
                            <span class="acc-value"><?= $joined ?></span>
                        </div>
                        <div class="acc-row">
                            <span class="acc-label">Last Login</span>
                            <span class="acc-value"><?= $lastLogin ?></span>
                        </div>
                        <div class="acc-row">
                            <span class="acc-label">Email</span>
                            <span class="acc-value mono"><?= $adminEmail ?></span>
                        </div>
                        <?php if ($adminPhone): ?>
                        <div class="acc-row">
                            <span class="acc-label">Phone</span>
                            <span class="acc-value"><?= $adminPhone ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($adminDept): ?>
                        <div class="acc-row">
                            <span class="acc-label">Department</span>
                            <span class="acc-value"><?= $adminDept ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($adminBio): ?>
                        <div class="acc-row" style="align-items:flex-start;">
                            <span class="acc-label">Bio</span>
                            <span class="acc-value" style="font-size:0.78rem;color:var(--text2);text-align:right;max-width:200px;line-height:1.5;"><?= $adminBio ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Profile Photo Card -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">📷</span>Profile Photo</div>
                    </div>
                    <div class="photo-upload-area" id="photoDropArea" onclick="document.getElementById('photoInput').click()">
                        <div class="photo-upload-icon">📷</div>
                        <p>Click or drag & drop</p>
                        <small>JPG, PNG, WEBP · Max 3MB</small>
                        <span id="photoFileName" class="photo-filename"></span>
                    </div>
                </div>

                <!-- Permissions Card -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">⚡</span>Admin Permissions</div>
                    </div>
                    <ul class="perm-list">
                        <li class="perm-item granted"><span>✅</span> Manage all users</li>
                        <li class="perm-item granted"><span>✅</span> Block / unblock accounts</li>
                        <li class="perm-item granted"><span>✅</span> Delete user accounts</li>
                        <li class="perm-item granted"><span>✅</span> Change user roles</li>
                        <li class="perm-item granted"><span>✅</span> View all datasets</li>
                        <li class="perm-item granted"><span>✅</span> AI model oversight</li>
                        <li class="perm-item granted"><span>✅</span> View system logs</li>
                        <li class="perm-item granted"><span>✅</span> Full platform access</li>
                    </ul>
                </div>

            </div>
        </div>

    </div><!-- /content-area -->
</div><!-- /main-content -->

<style>
/* ── Profile Hero ── */
.profile-hero {
    position: relative;
    border-radius: var(--r-lg);
    overflow: hidden;
    margin-bottom: 24px;
    border: 1px solid var(--border);
}
.profile-hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(155,28,122,0.25) 0%, rgba(37,99,235,0.2) 60%, rgba(124,58,237,0.15) 100%);
}
.profile-hero-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: 28px;
    padding: 32px 28px;
    flex-wrap: wrap;
}
.profile-avatar-wrap {
    position: relative;
    width: 100px; height: 100px;
    cursor: pointer;
    flex-shrink: 0;
}
.profile-avatar-inner {
    width: 100%; height: 100%;
    border-radius: 22px;
    overflow: hidden;
    background: var(--g-pink);
    display: flex; align-items: center; justify-content: center;
    border: 3px solid rgba(244,114,182,0.5);
    box-shadow: 0 0 28px rgba(219,39,119,0.35);
}
.profile-avatar-inner img { width: 100%; height: 100%; object-fit: cover; display: block; }
.profile-avatar-initial { font-size: 2.5rem; font-weight: 900; color: #fff; }
.avatar-edit-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.55);
    border-radius: 22px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 4px;
    opacity: 0;
    transition: var(--transition);
    font-size: 1.2rem;
}
.avatar-edit-overlay small { font-size: 0.65rem; color: #fff; font-weight: 600; }
.profile-avatar-wrap:hover .avatar-edit-overlay { opacity: 1; }

.profile-hero-info h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 8px;
}
.profile-hero-badges { display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
.phb {
    padding: 4px 12px; border-radius: 20px;
    font-size: 0.78rem; font-weight: 700;
}
.phb-red   { background: rgba(220,38,38,0.18); color: #FCA5A5; border: 1px solid rgba(220,38,38,0.35); }
.phb-green { background: rgba(5,150,105,0.18); color: #34D399; border: 1px solid rgba(5,150,105,0.35); }
.profile-hero-meta {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    font-size: 0.82rem;
    color: rgba(255,255,255,0.6);
}

/* ── Profile Grid ── */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 22px;
    align-items: start;
}
@media (max-width: 1050px) { .profile-grid { grid-template-columns: 1fr; } }

/* ── Profile Form Inputs ── */
.pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media (max-width: 600px) { .pf-grid { grid-template-columns: 1fr; } }
.pf-group { display: flex; flex-direction: column; }
.pf-label {
    font-size: 0.78rem; font-weight: 700;
    color: var(--text2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 7px;
}
.pf-input-wrap { position: relative; display: flex; align-items: center; }
.pf-icon {
    position: absolute; left: 13px;
    font-size: 0.95rem; pointer-events: none;
    display: flex; align-items: center;
    line-height: 1;
}
.pf-input {
    width: 100%;
    background: var(--card);
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    padding: 11px 14px 11px 40px;
    color: var(--text);
    font-size: 0.9rem;
    outline: none;
    transition: var(--transition);
    font-family: inherit;
}
.pf-input:focus { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(219,39,119,0.15); background: var(--card-hover); }
.pf-input::placeholder { color: var(--text3); }
.pf-eye {
    position: absolute; right: 12px;
    background: none; border: none;
    cursor: pointer; font-size: 0.9rem;
    color: var(--text3); padding: 4px;
    transition: var(--transition);
}
.pf-eye:hover { color: var(--text2); }
.pf-textarea {
    background: var(--card);
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    padding: 11px 14px;
    color: var(--text);
    font-size: 0.9rem;
    font-family: inherit;
    outline: none;
    resize: vertical;
    width: 100%;
    transition: var(--transition);
    line-height: 1.6;
}
.pf-textarea:focus { border-color: var(--pink); box-shadow: 0 0 0 3px rgba(219,39,119,0.15); background: var(--card-hover); }
.pf-textarea::placeholder { color: var(--text3); }

/* ── Password Strength ── */
.pw-strength-wrap { display: flex; align-items: center; gap: 12px; }
.pw-strength-bar { flex: 1; height: 5px; background: var(--glass); border-radius: 10px; overflow: hidden; }
.pw-fill { height: 100%; border-radius: 10px; transition: width 0.4s ease, background 0.4s ease; }
.pw-label { font-size: 0.78rem; font-weight: 700; min-width: 60px; }

/* ── Submit row ── */
.pf-submit-row {
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 4px;
    flex-wrap: wrap;
}
.pf-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px; border-radius: var(--r-sm);
    font-size: 0.9rem; font-weight: 700;
    cursor: pointer; border: none;
    transition: var(--transition); text-decoration: none;
}
.pf-btn-primary {
    background: var(--g-pink); color: #fff;
    box-shadow: 0 4px 18px rgba(219,39,119,0.4);
}
.pf-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 26px rgba(219,39,119,0.55); }
.pf-btn-secondary {
    background: var(--glass); color: var(--text2);
    border: 1px solid var(--border);
}
.pf-btn-secondary:hover { background: var(--glass-hover); color: var(--text); }

/* ── Account Detail List ── */
.acc-detail-list { display: flex; flex-direction: column; }
.acc-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    gap: 8px;
    flex-wrap: wrap;
}
.acc-row:last-child { border-bottom: none; }
.acc-label { font-size: 0.78rem; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: 0.4px; }
.acc-value { font-size: 0.85rem; color: var(--text); font-weight: 500; text-align: right; }
.mono { font-family: monospace; font-size: 0.82rem; }

/* ── Photo Upload Area ── */
.photo-upload-area {
    border: 2px dashed var(--border);
    border-radius: var(--r-sm);
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    color: var(--text2);
}
.photo-upload-area:hover { border-color: var(--pink); background: rgba(219,39,119,0.04); color: var(--text); }
.photo-upload-icon { font-size: 2rem; margin-bottom: 8px; }
.photo-upload-area p { font-size: 0.88rem; font-weight: 600; margin-bottom: 4px; }
.photo-upload-area small { font-size: 0.75rem; color: var(--text3); }
.photo-filename { display: block; margin-top: 10px; font-size: 0.78rem; color: var(--teal-l); font-weight: 600; }

/* ── Permissions ── */
.perm-list { list-style: none; padding: 0; }
.perm-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 0;
    border-bottom: 1px solid var(--border);
    font-size: 0.85rem; color: var(--text2);
    font-weight: 500;
}
.perm-item:last-child { border-bottom: none; }
.perm-item.granted { color: var(--text); }
.perm-item span { flex-shrink: 0; }

/* ── Alert Strip ── */
.alert-strip {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 16px; border-radius: var(--r-sm);
    margin-bottom: 18px; font-size: 0.88rem; font-weight: 600;
    animation: fadeInUp 0.25s ease both;
    position: relative;
}
.alert-strip.error   { background: rgba(220,38,38,0.12); border: 1px solid rgba(220,38,38,0.3); color: #FCA5A5; }
.alert-strip.success { background: rgba(5,150,105,0.12); border: 1px solid rgba(5,150,105,0.3); color: #6EE7B7; }
.alert-close {
    margin-left: auto; background: none; border: none;
    cursor: pointer; color: inherit; font-size: 1rem; opacity: 0.6;
}
.alert-close:hover { opacity: 1; }
.panel-note { font-size: 0.75rem; color: var(--text3); }
</style>

<script src="js/script.js"></script>
<script>
/* ── Photo Preview ── */
function previewPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById('photoFileName').textContent = '📎 ' + file.name;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('avatarPreview');
        const initial = document.getElementById('avatarInitial');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (initial) initial.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

/* ── Toggle Password Visibility ── */
function togglePw(inputId, eyeId) {
    const inp = document.getElementById(inputId);
    inp.type = inp.type === 'password' ? 'text' : 'password';
    document.getElementById(eyeId).textContent = inp.type === 'password' ? '👁️' : '🙈';
}

/* ── Password Strength ── */
document.getElementById('newPass').addEventListener('input', function () {
    const v = this.value;
    const wrap = document.getElementById('pwStrengthWrap');
    const fill = document.getElementById('pwStrengthFill');
    const lbl  = document.getElementById('pwStrengthLabel');
    if (!v) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';

    let score = 0;
    if (v.length >= 6)  score++;
    if (v.length >= 10) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    const levels = [
        { label: 'Very Weak', color: '#DC2626', pct: '15%' },
        { label: 'Weak',      color: '#EA580C', pct: '30%' },
        { label: 'Fair',      color: '#D97706', pct: '55%' },
        { label: 'Good',      color: '#059669', pct: '75%' },
        { label: 'Strong',    color: '#10B981', pct: '100%' },
    ];
    const l = levels[Math.min(score, 4)];
    fill.style.width      = l.pct;
    fill.style.background = l.color;
    lbl.textContent       = l.label;
    lbl.style.color       = l.color;
});

/* ── Submit Spinner ── */
document.getElementById('profileForm').addEventListener('submit', () => {
    document.getElementById('saveBtnText').textContent = '⏳ Saving…';
    document.getElementById('saveBtn').disabled = true;
});

/* ── Drag & drop on photo panel ── */
const dropArea = document.getElementById('photoDropArea');
['dragenter','dragover'].forEach(e => dropArea.addEventListener(e, ev => {
    ev.preventDefault(); dropArea.style.borderColor = 'var(--pink)';
}));
['dragleave','drop'].forEach(e => dropArea.addEventListener(e, ev => {
    ev.preventDefault(); dropArea.style.borderColor = 'var(--border)';
}));
dropArea.addEventListener('drop', ev => {
    const f = ev.dataTransfer.files[0];
    if (f) {
        const dt = new DataTransfer();
        dt.items.add(f);
        document.getElementById('photoInput').files = dt.files;
        previewPhoto(document.getElementById('photoInput'));
    }
});
</script>
</body>
</html>
