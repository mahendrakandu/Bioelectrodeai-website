<?php
session_start();
if (!isset($_SESSION['user_id']))         { header('Location: index.php'); exit; }
if ($_SESSION['user_role'] !== 'Admin')   { header('Location: dashboard.php'); exit; }

require_once 'api/db.php';
$conn = getDB();

$totalUsers    = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role!='Admin'")->fetch_assoc()['c'] ?? 0;
$activeUsers   = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='Active' AND role!='Admin'")->fetch_assoc()['c'] ?? 0;
$blockedUsers  = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status='Blocked' AND role!='Admin'")->fetch_assoc()['c'] ?? 0;
$totalDatasets = $conn->query("SELECT COUNT(*) AS c FROM datasets")->fetch_assoc()['c'] ?? 0;
$totalModels   = $conn->query("SELECT COUNT(*) AS c FROM ai_models")->fetch_assoc()['c'] ?? 0;

// Recent logs
$logsResult = $conn->query("SELECT action, details, created_at FROM system_logs ORDER BY created_at DESC LIMIT 8");
$logs = [];
if ($logsResult) while ($row = $logsResult->fetch_assoc()) $logs[] = $row;

// All non-admin users
$usersResult = $conn->query("SELECT id, name, email, role, status, created_at, last_login FROM users WHERE role!='Admin' ORDER BY created_at DESC");
$allUsers = [];
while ($row = $usersResult->fetch_assoc()) $allUsers[] = $row;

$conn->close();

$adminName    = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – BioElectrode AI</title>
    <meta name="description" content="BioElectrode AI Admin Control Panel – Manage users, datasets, AI models and system logs.">
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
            <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
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
        <a href="#section-overview" class="nav-item active" data-section="overview">
            <span class="nav-icon">📊</span>
            <span class="nav-item-label">Dashboard</span>
        </a>

        <div class="nav-label">Management</div>
        <a href="#section-users" class="nav-item" data-section="users">
            <span class="nav-icon">👥</span>
            <span class="nav-item-label">Manage Users</span>
            <span class="nav-badge"><?= $totalUsers ?></span>
        </a>
        <a href="#section-datasets" class="nav-item" data-section="datasets">
            <span class="nav-icon">🗄️</span>
            <span class="nav-item-label">Datasets</span>
        </a>
        <a href="#section-ai" class="nav-item" data-section="ai">
            <span class="nav-icon">🧠</span>
            <span class="nav-item-label">AI Control</span>
        </a>

        <div class="nav-label">System</div>
        <a href="#section-logs" class="nav-item" data-section="logs">
            <span class="nav-icon">📋</span>
            <span class="nav-item-label">System Logs</span>
        </a>
        <a href="#section-role" class="nav-item" data-section="role">
            <span class="nav-icon">🔑</span>
            <span class="nav-item-label">Admin Role</span>
        </a>
        <a href="admin_profile.php" class="nav-item">
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
                <h1>🛡️ Admin Dashboard</h1>
                <p>System Overview · <?= date('D, d M Y') ?></p>
            </div>
        </div>
        <div class="tb-right">
            <div class="tb-actions">
                <button class="tb-btn" title="Notifications" style="position:relative;">
                    🔔<span class="notification-dot"></span>
                </button>
            </div>
            <div class="tb-user">
                <div class="tb-avatar" style="background:linear-gradient(135deg,#9d174d,#DB2777);overflow:hidden;">
                    <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                        <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <?= $adminInitial ?>
                    <?php endif; ?>
                </div>
                <span class="tb-user-name"><?= $adminName ?></span>
            </div>
        </div>
    </header>

    <div class="content-area">

        <!-- ── SECTION: OVERVIEW ── -->
        <section id="section-overview" class="admin-section active">

            <!-- Alert Banner -->
            <div class="admin-alert-banner">
                <span class="alert-icon">🛡️</span>
                <div>
                    <strong>Administrator Access Active</strong>
                    <span>You have full control over users, datasets, AI models, and system configuration.</span>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="stats-grid">
                <div class="stat-card blue" onclick="switchSection('users')" style="cursor:pointer;">
                    <div class="stat-icon-box">👥</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= number_format($totalUsers) ?></div>
                    <div class="stat-sub">✅ Active: <?= $activeUsers ?> &nbsp;|&nbsp; 🚫 Blocked: <?= $blockedUsers ?></div>
                </div>
                <div class="stat-card teal" onclick="switchSection('datasets')" style="cursor:pointer;">
                    <div class="stat-icon-box">💾</div>
                    <div class="stat-label">Total Datasets</div>
                    <div class="stat-value"><?= number_format($totalDatasets) ?></div>
                    <div class="stat-sub">↑ Uploaded by users</div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon-box">🧠</div>
                    <div class="stat-label">AI Analysis Tasks</div>
                    <div class="stat-value">5,621</div>
                    <div class="stat-sub">⚡ Processed signals</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon-box">🤖</div>
                    <div class="stat-label">Deployed AI Models</div>
                    <div class="stat-value"><?= number_format($totalModels) ?></div>
                    <div class="stat-sub">🟢 v2.4 LTS Stable</div>
                </div>
            </div>

            <!-- Two-col: Technique + Quick Actions -->
            <div class="two-col">
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">📈</span>Technique Distribution</div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label">
                            <span class="label-text">⚡ Bipolar Recording</span>
                            <span class="label-pct">62%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-blue" style="width:62%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label">
                            <span class="label-text">🔵 Monopolar Recording</span>
                            <span class="label-pct">38%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-teal" style="width:38%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label">
                            <span class="label-text">🧠 AI Analysis Usage</span>
                            <span class="label-pct">78%</span>
                        </div>
                        <div class="progress-track"><div class="progress-fill fill-purple" style="width:78%"></div></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">⚡</span>Quick Admin Actions</div>
                    </div>
                    <div class="admin-actions-grid">
                        <button class="admin-action-btn blue" onclick="switchSection('users')">
                            <div class="aab-icon">👥</div><span>Manage Users</span>
                        </button>
                        <button class="admin-action-btn teal" onclick="switchSection('datasets')">
                            <div class="aab-icon">💾</div><span>Datasets</span>
                        </button>
                        <button class="admin-action-btn purple" onclick="switchSection('ai')">
                            <div class="aab-icon">🧠</div><span>AI Control</span>
                        </button>
                        <button class="admin-action-btn orange" onclick="switchSection('logs')">
                            <div class="aab-icon">📋</div><span>System Logs</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🕒</span>Recent Activity</div>
                    <a href="#section-logs" onclick="switchSection('logs')" class="panel-link">View All →</a>
                </div>
                <?php if (empty($logs)): ?>
                    <p class="empty-msg">No activity recorded yet.</p>
                <?php else: ?>
                    <?php $dotIcons=['👤','💾','🧠','📊']; $dotColors=['green','blue','purple','orange']; ?>
                    <?php foreach ($logs as $i => $log): ?>
                    <div class="activity-item">
                        <div class="activity-dot <?= $dotColors[$i % 4] ?>"><?= $dotIcons[$i % 4] ?></div>
                        <div>
                            <div class="act-title"><?= htmlspecialchars($log['action']) ?></div>
                            <div class="act-time"><?= htmlspecialchars($log['details'] ?? '') ?> · <?= date('d M, H:i', strtotime($log['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── SECTION: MANAGE USERS ── -->
        <section id="section-users" class="admin-section">
            <div class="section-header">
                <h2>👥 Manage Users</h2>
                <p>View, block, unblock, or remove registered users. Change roles as needed.</p>
            </div>

            <!-- User stats row -->
            <div class="user-stat-row">
                <div class="user-stat-pill blue">Total: <?= $totalUsers ?></div>
                <div class="user-stat-pill teal">Active: <?= $activeUsers ?></div>
                <div class="user-stat-pill red">Blocked: <?= $blockedUsers ?></div>
            </div>

            <!-- Search + filter -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🔍</span>User Directory</div>
                    <div class="user-search-wrap">
                        <input type="text" id="userSearchInput" class="form-control search-input" placeholder="Search by name or email..." oninput="filterUsers()">
                    </div>
                </div>

                <div id="usersToast" class="admin-toast" style="display:none;"></div>

                <?php if (empty($allUsers)): ?>
                    <p class="empty-msg">No users registered yet.</p>
                <?php else: ?>
                <div class="table-wrap">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name / Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allUsers as $i => $u): ?>
                        <tr id="user-row-<?= $u['id'] ?>" data-name="<?= strtolower($u['name']) ?>" data-email="<?= strtolower($u['email']) ?>">
                            <td class="row-num"><?= $i + 1 ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="mini-avatar"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                                    <div>
                                        <div class="cell-name"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="cell-email"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <select class="role-select" onchange="changeRole(<?= $u['id'] ?>, this.value)">
                                    <?php foreach (['Student','Researcher','Educator'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= $r ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td><?= $u['last_login'] ? date('d M, H:i', strtotime($u['last_login'])) : '<span class="text-dim">Never</span>' ?></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($u['status'] === 'Blocked'): ?>
                                        <button class="act-btn green" onclick="userAction('unblock', <?= $u['id'] ?>)" title="Unblock">✅ Unblock</button>
                                    <?php else: ?>
                                        <button class="act-btn yellow" onclick="userAction('block', <?= $u['id'] ?>)" title="Block">🚫 Block</button>
                                    <?php endif; ?>
                                    <button class="act-btn red" onclick="userAction('delete', <?= $u['id'] ?>)" title="Delete">🗑️ Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── SECTION: DATASETS ── -->
        <section id="section-datasets" class="admin-section">
            <div class="section-header">
                <h2>💾 Datasets</h2>
                <p>Overview of uploaded bioelectrode signal datasets in the system.</p>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">💾</span>Dataset Records</div>
                </div>
                <div class="placeholder-state">
                    <div class="placeholder-icon">💾</div>
                    <h3>Dataset Management</h3>
                    <p>Total datasets in the system: <strong><?= number_format($totalDatasets) ?></strong></p>
                    <p class="text-dim">Advanced dataset management (view, download, delete) coming soon.</p>
                </div>
            </div>
        </section>

        <!-- ── SECTION: AI CONTROL ── -->
        <section id="section-ai" class="admin-section">
            <div class="section-header">
                <h2>🧠 AI Control</h2>
                <p>Monitor deployed models, trigger re-training, and review performance metrics.</p>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🤖</span>Model Status</div>
                </div>
                <div class="placeholder-state">
                    <div class="placeholder-icon">🤖</div>
                    <h3>AI Models: <?= number_format($totalModels) ?> Deployed</h3>
                    <p>Current version: <strong>v2.4 LTS Stable</strong></p>
                    <p class="text-dim">Model control panel (trigger training, review accuracy, rollback) coming soon.</p>
                </div>
            </div>
        </section>

        <!-- ── SECTION: SYSTEM LOGS ── -->
        <section id="section-logs" class="admin-section">
            <div class="section-header">
                <h2>📋 System Logs</h2>
                <p>Full audit trail of all admin and user actions in the system.</p>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">📋</span>Activity Log</div>
                </div>
                <?php if (empty($logs)): ?>
                    <p class="empty-msg">No system events logged yet.</p>
                <?php else: ?>
                    <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Event</th><th>Details</th><th>Time</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($log['action']) ?></strong></td>
                                <td class="text-dim"><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                                <td><?= date('d M Y, H:i:s', strtotime($log['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── SECTION: ADMIN ROLE ── -->
        <section id="section-role" class="admin-section">
            <div class="section-header">
                <h2>🔑 Admin Role & Responsibilities</h2>
                <p>What the Administrator can do in BioElectrode AI.</p>
            </div>

            <div class="role-grid">
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(37,99,235,0.15);color:#60A5FA;">👥</div>
                    <h3>User Management</h3>
                    <ul>
                        <li>View all registered users (Students, Researchers, Educators)</li>
                        <li>Block or unblock accounts instantly</li>
                        <li>Change user roles (Student ↔ Researcher ↔ Educator)</li>
                        <li>Permanently delete user accounts</li>
                        <li>Monitor last login activity</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(5,150,105,0.15);color:#34D399;">💾</div>
                    <h3>Dataset Control</h3>
                    <ul>
                        <li>View all uploaded bioelectrode datasets</li>
                        <li>Review ECG, EEG, and EMG signal datasets</li>
                        <li>Remove corrupted or invalid datasets</li>
                        <li>Monitor dataset growth and storage usage</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(124,58,237,0.15);color:#A78BFA;">🧠</div>
                    <h3>AI & Model Oversight</h3>
                    <ul>
                        <li>Monitor deployed AI analysis models</li>
                        <li>Trigger model re-training pipelines</li>
                        <li>Review accuracy and performance metrics</li>
                        <li>Approve or rollback model versions</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(234,88,12,0.15);color:#FB923C;">📋</div>
                    <h3>System & Audit Logs</h3>
                    <ul>
                        <li>View full audit trail of all system events</li>
                        <li>Track user login and activity history</li>
                        <li>Monitor blocked/deleted account history</li>
                        <li>Export logs for compliance review</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(219,39,119,0.15);color:#F472B6;">🔒</div>
                    <h3>Access & Security</h3>
                    <ul>
                        <li>Admin-only access – never exposed to regular users</li>
                        <li>Session-based authentication with role verification</li>
                        <li>Can reset user passwords via admin API</li>
                        <li>Enforce usage policies across the platform</li>
                    </ul>
                </div>
                <div class="role-card">
                    <div class="role-card-icon" style="background:rgba(217,119,6,0.15);color:#FCD34D;">⚙️</div>
                    <h3>Platform Configuration</h3>
                    <ul>
                        <li>Configure global platform settings</li>
                        <li>Enable / disable features per role group</li>
                        <li>Manage notification and alert rules</li>
                        <li>Oversee onboarding for new users</li>
                    </ul>
                </div>
            </div>

            <!-- Admin Credentials Info -->
            <div class="panel cred-panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🔐</span>Admin Login Details</div>
                </div>
                <div class="cred-info">
                    <div class="cred-row">
                        <span class="cred-label">Logged in as</span>
                        <span class="cred-value highlight-pink"><?= $adminName ?></span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Role</span>
                        <span class="cred-value"><span class="status-badge admin-badge">🔑 Administrator</span></span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Session Started</span>
                        <span class="cred-value"><?= date('d M Y, H:i') ?></span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Admin Access</span>
                        <span class="cred-value" style="color:#34D399;">✅ Full System Access</span>
                    </div>
                    <div class="cred-row">
                        <span class="cred-label">Login Page</span>
                        <span class="cred-value"><a href="index.php" class="cred-link">index.php (shared login)</a> — use Admin credentials</span>
                    </div>
                    <div class="cred-note">
                        ⚠️ Admin accounts are created directly in the database with <code>role = 'Admin'</code>. They are not listed in the public registration form.
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /content-area -->
</div><!-- /main-content -->

<script src="js/script.js"></script>
<script>
/* ══ Section Switching ══ */
function switchSection(name) {
    document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    const sec = document.getElementById('section-' + name);
    if (sec) sec.classList.add('active');
    const nav = document.querySelector('[data-section="' + name + '"]');
    if (nav) nav.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.querySelectorAll('.nav-item[data-section]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        switchSection(link.dataset.section);
    });
});

/* ══ User Search Filter ══ */
function filterUsers() {
    const q = document.getElementById('userSearchInput').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(row => {
        const match = row.dataset.name.includes(q) || row.dataset.email.includes(q);
        row.style.display = match ? '' : 'none';
    });
}

/* ══ User Actions (Block/Unblock/Delete) ══ */
function userAction(action, userId) {
    const labels = { block: 'block this user', unblock: 'unblock this user', delete: 'permanently delete this user' };
    if (!confirm('Are you sure you want to ' + labels[action] + '?')) return;

    const fd = new FormData();
    fd.append('action', action);
    fd.append('user_id', userId);

    fetch('api/admin_users_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Done! Action "' + action + '" applied successfully.', 'success');
                if (action === 'delete') {
                    const row = document.getElementById('user-row-' + userId);
                    if (row) row.remove();
                } else {
                    // Update badge
                    const row = document.getElementById('user-row-' + userId);
                    if (row) {
                        const badge = row.querySelector('.status-badge');
                        const btn   = row.querySelector('.act-btn.yellow, .act-btn.green');
                        if (action === 'block') {
                            badge.className = 'status-badge blocked'; badge.textContent = 'Blocked';
                            btn.className = 'act-btn green'; btn.textContent = '✅ Unblock';
                            btn.setAttribute('onclick', 'userAction(\'unblock\',' + userId + ')');
                        } else {
                            badge.className = 'status-badge active'; badge.textContent = 'Active';
                            btn.className = 'act-btn yellow'; btn.textContent = '🚫 Block';
                            btn.setAttribute('onclick', 'userAction(\'block\',' + userId + ')');
                        }
                    }
                }
            } else {
                showToast('❌ Error: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(() => showToast('❌ Network error. Please try again.', 'error'));
}

/* ══ Change Role ══ */
function changeRole(userId, newRole) {
    const fd = new FormData();
    fd.append('action', 'change_role');
    fd.append('user_id', userId);
    fd.append('role', newRole);
    fetch('api/admin_users_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) showToast('✅ Role updated to ' + newRole, 'success');
            else showToast('❌ Failed to update role.', 'error');
        });
}

/* ══ Toast ══ */
function showToast(msg, type) {
    const t = document.getElementById('usersToast');
    if (!t) return;
    t.textContent = msg;
    t.className = 'admin-toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}
</script>
</body>
</html>
