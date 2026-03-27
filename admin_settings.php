<?php
session_start();
if (!isset($_SESSION['user_id']))       { header('Location: index.php'); exit; }
if ($_SESSION['user_role'] !== 'Admin') { header('Location: dashboard.php'); exit; }

require_once 'api/db.php';
$conn = getDB();

$adminName    = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$adminInitial = strtoupper(substr($adminName, 0, 1));

// Simulated system stats
$cpuUsage = rand(15, 45);
$memUsage = rand(30, 60);
$dbSize   = "124.5 MB";
$uptime   = "14 days, 6 hours";

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Settings – Admin – BioElectrode AI</title>
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
        <a href="admin_dashboard.php#section-logs" class="nav-item">
            <span class="nav-icon">📋</span>
            <span class="nav-item-label">System Logs</span>
        </a>
        <a href="admin_settings.php" class="nav-item active">
            <span class="nav-icon">⚙️</span>
            <span class="nav-item-label">System Settings</span>
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
                <h1>⚙️ Platform Settings</h1>
                <p>Advanced system configuration & health monitoring</p>
            </div>
        </div>
        <div class="tb-right">
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

        <!-- System Health Section -->
        <div class="section-header">
            <h2>🛡️ System Health</h2>
            <p>Real-time monitoring of server resources and database performance.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">CPU Load</div>
                <div class="stat-value"><?= $cpuUsage ?>%</div>
                <div class="progress-track" style="margin-top:10px;height:6px;"><div class="progress-fill fill-blue" style="width:<?= $cpuUsage ?>%"></div></div>
                <div class="stat-sub">Normal Operational Range</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-label">Memory Usage</div>
                <div class="stat-value"><?= $memUsage ?>%</div>
                <div class="progress-track" style="margin-top:10px;height:6px;"><div class="progress-fill fill-purple" style="width:<?= $memUsage ?>%"></div></div>
                <div class="stat-sub">Peak avoidance enabled</div>
            </div>
            <div class="stat-card teal">
                <div class="stat-label">DB Size</div>
                <div class="stat-value"><?= $dbSize ?></div>
                <div class="stat-sub">Healthy state · No bloat</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-label">Uptime</div>
                <div class="stat-value" style="font-size:1.4rem;padding-top:10px;"><?= $uptime ?></div>
                <div class="stat-sub">Current session stability</div>
            </div>
        </div>

        <div class="settings-grid">
            <!-- Platform Controls -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🔧</span>General Controls</div>
                </div>
                <div class="set-list-alt">
                    <div class="set-row-alt">
                        <div class="set-info-alt">
                            <div class="set-name-alt">Maintenance Mode</div>
                            <div class="set-sub-alt">Redirect users to "System Update" page</div>
                        </div>
                        <label class="sw-alt"><input type="checkbox" id="maint-mode"><span class="sl-alt"></span></label>
                    </div>
                    <div class="set-row-alt">
                        <div class="set-info-alt">
                            <div class="set-name-alt">Allow New Signups</div>
                            <div class="set-sub-alt">Enable/Disable public registration form</div>
                        </div>
                        <label class="sw-alt"><input type="checkbox" checked><span class="sl-alt"></span></label>
                    </div>
                    <div class="set-row-alt">
                        <div class="set-info-alt">
                            <div class="set-name-alt">Email Verification</div>
                            <div class="set-sub-alt">Require verified email before login</div>
                        </div>
                        <label class="sw-alt"><input type="checkbox" checked><span class="sl-alt"></span></label>
                    </div>
                    <div class="set-row-alt">
                        <div class="set-info-alt">
                            <div class="set-name-alt">Debug Mode</div>
                            <div class="set-sub-alt">Verbose console logging for developers</div>
                        </div>
                        <label class="sw-alt"><input type="checkbox"><span class="sl-alt"></span></label>
                    </div>
                </div>
            </div>

            <!-- Database Maintenance -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🗄️</span>Database Maintenance</div>
                </div>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <button class="admin-action-btn blue" style="flex-direction:row; justify-content:flex-start; gap:15px; padding:15px;" onclick="showToast('Backup request sent to server...', 'success')">
                        <div class="aab-icon" style="font-size:1.2rem;">💾</div>
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Generate Full Backup</div>
                            <div style="font-size:0.7rem; color:var(--text3);">Creates a timestamped .sql file</div>
                        </div>
                    </button>
                    <button class="admin-action-btn teal" style="flex-direction:row; justify-content:flex-start; gap:15px; padding:15px;" onclick="showToast('Optimizing indexes...', 'success')">
                        <div class="aab-icon" style="font-size:1.2rem;">⚡</div>
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Optimize Database</div>
                            <div style="font-size:0.7rem; color:var(--text3);">Defragment tables & clear cache</div>
                        </div>
                    </button>
                    <button class="admin-action-btn orange" style="flex-direction:row; justify-content:flex-start; gap:15px; padding:15px;" onclick="if(confirm('Clear all logs?')) showToast('All system logs cleared.', 'success')">
                        <div class="aab-icon" style="font-size:1.2rem;">📋</div>
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Prune System Logs</div>
                            <div style="font-size:0.7rem; color:var(--text3);">Delete logs older than 30 days</div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="settings-grid">
             <!-- AI Model Control -->
             <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🧠</span>AI Intelligence Tuning</div>
                </div>
                <div style="padding:10px 0;">
                    <div class="pf-group">
                        <label class="pf-label" style="display:flex; justify-content:space-between;">
                            <span>Detection Sensitivity</span>
                            <span id="sens-val" style="color:var(--pink);">85%</span>
                        </label>
                        <input type="range" class="pf-range" min="50" max="100" value="85" style="width:100%; accent-color:var(--pink);" oninput="document.getElementById('sens-val').textContent=this.value+'%'">
                        <p style="font-size:0.75rem; color:var(--text3); margin-top:8px;">Higher sensitivity increases artifact detection but may flag clean signals.</p>
                    </div>

                    <div class="set-list-alt" style="margin-top:20px;">
                        <div class="set-row-alt">
                            <div class="set-info-alt">
                                <div class="set-name-alt">Auto-Retrain Models</div>
                                <div class="set-sub-alt">Weekly training on new user data</div>
                            </div>
                            <label class="sw-alt"><input type="checkbox" checked><span class="sl-alt"></span></label>
                        </div>
                        <div class="set-row-alt">
                            <div class="set-info-alt">
                                <div class="set-name-alt">Beta Algorithm Access</div>
                                <div class="set-sub-alt">Use experimental v2.5 filters</div>
                            </div>
                            <label class="sw-alt"><input type="checkbox"><span class="sl-alt"></span></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Audit -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🔐</span>Security Auditing</div>
                </div>
                <div class="acc-detail-list">
                    <div class="acc-row">
                        <span class="acc-label">SSL Certificate</span>
                        <span class="acc-value" style="color:#34D399;">Active (Expires in 84 days)</span>
                    </div>
                    <div class="acc-row">
                        <span class="acc-label">Failed Logins (24h)</span>
                        <span class="acc-value">12 attempts</span>
                    </div>
                    <div class="acc-row">
                        <span class="acc-label">API Access</span>
                        <span class="acc-value" style="color:#60A5FA;">Restricted to Whitelisted IPs</span>
                    </div>
                </div>
                <button class="pf-btn pf-btn-secondary" style="width:100%; margin-top:15px; font-size:0.8rem;" onclick="showToast('Scanning for security anomalies...', 'success')">🔍 Run Full Security Audit</button>
            </div>
        </div>

        <!-- Notification Panel -->
        <div class="panel">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-icon">🔔</span>Administrative Notifications</div>
            </div>
            <div class="pf-grid">
                <div class="pf-group">
                    <label class="pf-label">Alert Email address</label>
                    <div class="pf-input-wrap">
                        <span class="pf-icon">✉️</span>
                        <input type="email" class="pf-input" value="alerts@bioelectrode.ai" placeholder="Enter admin email">
                    </div>
                </div>
                <div class="pf-group">
                    <label class="pf-label">Webhook URL (Slack/Teams)</label>
                    <div class="pf-input-wrap">
                        <span class="pf-icon">🔗</span>
                        <input type="url" class="pf-input" placeholder="https://hooks.slack.com/services/...">
                    </div>
                </div>
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button class="pf-btn pf-btn-primary" onclick="showToast('Administrative settings saved successfully.', 'success')">💾 Save System Configuration</button>
            </div>
        </div>

    </div><!-- /content-area -->
</div><!-- /main-content -->

<div id="usersToast" class="admin-toast" style="display:none; position:fixed; bottom:30px; right:30px; z-index:9999;"></div>

<script src="js/script.js"></script>
<script>
function showToast(msg, type) {
    const t = document.getElementById('usersToast');
    t.textContent = msg;
    t.className = 'admin-toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}
</script>
</body>
</html>
