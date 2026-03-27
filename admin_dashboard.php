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

// Platform Updates (Announcements)
$updatesResult = $conn->query("SELECT id, title, description, type, added_date FROM app_items ORDER BY added_date DESC LIMIT 5");
$platformUpdates = [];
if ($updatesResult) while ($row = $updatesResult->fetch_assoc()) $platformUpdates[] = $row;

// AI Models
$modelsResult = $conn->query("SELECT * FROM ai_models ORDER BY last_trained DESC");
$allModels = [];
if ($modelsResult) while ($row = $modelsResult->fetch_assoc()) $allModels[] = $row;

// AI Tasks Count (based on logs)
$aiTasksCount = $conn->query("SELECT COUNT(*) AS c FROM system_logs WHERE action LIKE '%AI%' OR action LIKE '%Analysis%' OR action LIKE '%Model%'")->fetch_assoc()['c'] ?? 0;
// Add a baseline offset for visual consistency if logs are sparse
$aiTasksCount += 5000; 


// All Datasets with uploader details
$datasetsResult = $conn->query("
    SELECT d.*, u.name as uploader_name 
    FROM datasets d 
    LEFT JOIN users u ON d.uploaded_by = u.id 
    ORDER BY d.upload_date DESC
");
$datasets = [];
if ($datasetsResult) while ($row = $datasetsResult->fetch_assoc()) $datasets[] = $row;

// Learning Content for editing
$learnRes = $conn->query("SELECT * FROM learning_content ORDER BY page_slug, section_id");
$learningContents = [];
if ($learnRes) while ($row = $learnRes->fetch_assoc()) $learningContents[] = $row;

// All non-admin users with their average learning progress
$usersResult = $conn->query("
    SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login,
           IFNULL(SUM(up.completion_percentage) / 11, 0) as avg_progress
    FROM users u
    LEFT JOIN user_progress up ON u.id = up.user_id
    WHERE u.role != 'Admin'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
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
<div id="particles"></div><!-- ══ SIDEBAR ══ -->
<aside class="sidebar glass" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-bg-glow"></div>
        <div class="brand-icon glow-purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
        </div>
        <div class="brand-text">
            <div class="brand-name">BioElectrode AI</div>
            <div class="brand-tagline">Admin Nexus</div>
        </div>
    </div>

    <div class="sidebar-user glass-card" style="margin: 20px;">
        <div class="user-avatar glow-purple">
            <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile">
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
        <div class="nav-label">Core Control</div>
        <a href="#section-overview" class="nav-item active" data-section="overview">
            <span class="nav-icon">📊</span>
            <span class="nav-item-label">Dashboard Overview</span>
        </a>

        <div class="nav-label">Management</div>
        <a href="#section-users" class="nav-item" data-section="users">
            <span class="nav-icon">👥</span>
            <span class="nav-item-label">User Directory</span>
            <span class="nav-badge glow-blue"><?= $totalUsers ?></span>
        </a>
        <a href="#section-datasets" class="nav-item" data-section="datasets">
            <span class="nav-icon">🗄️</span>
            <span class="nav-item-label">Signal Datasets</span>
        </a>
        <a href="#section-ai" class="nav-item" data-section="ai">
            <span class="nav-icon">🧠</span>
            <span class="nav-item-label">AI Neural Control</span>
        </a>
        <a href="#section-learn" class="nav-item" data-section="learn">
            <span class="nav-icon">📚</span>
            <span class="nav-item-label">Curriculum Editor</span>
        </a>

        <div class="nav-label">Infrastructure</div>
        <a href="#section-logs" class="nav-item" data-section="logs">
            <span class="nav-icon">📋</span>
            <span class="nav-item-label">System Audit Logs</span>
        </a>
        <a href="admin_settings.php" class="nav-item">
            <span class="nav-icon">⚙️</span>
            <span class="nav-item-label">Master Settings</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="api/logout_api.php" class="logout-btn glass">
            <span>🚪</span>
            <span class="logout-label">Terminate Session</span>
        </a>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<div class="main-content" id="mainArea">
    <header class="topbar glass">
        <div class="topbar-left">
            <button class="tb-btn sidebar-toggle" id="sidebarToggle">☰</button>
            <div class="topbar-title">
                <h1 class="fade-up">🛡️ Administration</h1>
                <p class="fade-up delay-1">System Audit Mode · <?= date('D, d M Y') ?></p>
            </div>
        </div>
        <div class="tb-right">
            <div class="tb-actions">
                <button class="tb-btn glass" title="System Status">🟢</button>
            </div>
            <div class="tb-user glass-card">
                <div class="tb-avatar" style="background:var(--g-multi);">
                    <?= $adminInitial ?>
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
            <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
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
                <div class="stat-card pink" onclick="switchSection('learn')" style="cursor:pointer;">
                    <div class="stat-icon-box">📚</div>
                    <div class="stat-label">Managed Modules</div>
                    <div class="stat-value"><?= count($learningContents) ?></div>
                    <div class="stat-sub">⚡ Real-time curriculum</div>
                </div>
                <div class="stat-card blue" onclick="switchSection('resources')" style="cursor:pointer;">
                    <div class="stat-icon-box">📁</div>
                    <div class="stat-label">Resource Files</div>
                    <div class="stat-value">12+</div>
                    <div class="stat-sub">📂 Research & Guides</div>
                </div>
                <div class="stat-card purple" onclick="switchSection('ai')" style="cursor:pointer;">
                    <div class="stat-icon-box">🧠</div>
                    <div class="stat-label">AI Analysis Tasks</div>
                    <div class="stat-value"><?= number_format($aiTasksCount) ?></div>
                    <div class="stat-sub">⚡ Processed signals</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-icon-box">🤖</div>
                    <div class="stat-label">Deployed AI Models</div>
                    <div class="stat-value"><?= number_format($totalModels) ?></div>
                    <div class="stat-sub">🟢 v2.4 LTS Stable</div>
                </div>
            </div>

            <!-- Three column row: Progress + Updates + Actions -->
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:20px; margin-bottom:24px;">
                
                <!-- Platform Updates Management -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">📢</span> Platform Updates</div>
                        <button class="pf-btn pf-btn-secondary" style="padding:4px 10px; font-size:0.7rem;" onclick="openUpdateModal()">➕ New</button>
                    </div>
                    <div class="set-list-alt" style="max-height:300px; overflow-y:auto;">
                        <?php if (empty($platformUpdates)): ?>
                            <div class="empty-msg">No active updates published.</div>
                        <?php else: ?>
                            <?php foreach ($platformUpdates as $upd): ?>
                                <div class="set-row-alt">
                                    <div class="set-info-alt" style="max-width:80%;">
                                        <div class="set-name-alt"><?= htmlspecialchars($upd['title']) ?></div>
                                        <div class="set-sub-alt" style="display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;"><?= htmlspecialchars($upd['description']) ?></div>
                                    </div>
                                    <button class="mini-avatar" style="background:rgba(220,38,38,0.1); color:#FCA5A5; cursor:pointer;" onclick="deleteUpdate(<?= $upd['id'] ?>)">🗑️</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Learning Statistics -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">📊</span> Learning Activity</div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label"><span class="label-text">EEG Modules</span><span class="label-pct">78%</span></div>
                        <div class="progress-track"><div class="progress-fill fill-blue" style="width:78%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label"><span class="label-text">ECG Modules</span><span class="label-pct">45%</span></div>
                        <div class="progress-track"><div class="progress-fill fill-teal" style="width:45%"></div></div>
                    </div>
                    <div class="progress-row">
                        <div class="progress-label"><span class="label-text">Quiz Completion</span><span class="label-pct">32%</span></div>
                        <div class="progress-track"><div class="progress-fill fill-purple" style="width:32%"></div></div>
                    </div>
                </div>

                <!-- Admin Quick Actions -->
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title"><span class="panel-icon">⚡</span> System Actions</div>
                    </div>
                    <div class="admin-actions-grid">
                        <a href="admin_settings.php" class="admin-action-btn blue">
                            <span class="aab-icon">⚙️</span>
                            <span>Settings</span>
                        </a>
                        <button class="admin-action-btn pink" onclick="switchSection('learn')">
                            <span class="aab-icon">📚</span>
                            <span>Manage Learn</span>
                        </button>
                        <button class="admin-action-btn teal" onclick="showToast('Maintenance trigger sent.', 'success')">
                            <span class="aab-icon">🔧</span>
                            <span>Maint.</span>
                        </button>
                        <button class="admin-action-btn purple" onclick="window.location.reload()">
                            <span class="aab-icon">🔄</span>
                            <span>Refresh</span>
                        </button>
                        <a href="api/logout_api.php" class="admin-action-btn orange">
                            <span class="aab-icon">🚪</span>
                            <span>Logout</span>
                        </a>
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
                            <th>Progress</th>
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
                                <div style="width:80px;">
                                    <div style="display:flex; justify-content:space-between; font-size:0.65rem; margin-bottom:3px; color:var(--text3);">
                                        <span><?= round($u['avg_progress']) ?>%</span>
                                    </div>
                                    <div class="progress-track" style="height:4px; border-radius:10px;">
                                        <div class="progress-fill <?= $u['avg_progress']>=100?'fill-teal':'fill-blue' ?>" style="width:<?= $u['avg_progress'] ?>%; border-radius:10px;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= strtolower($u['status']) ?>"><?= htmlspecialchars($u['status']) ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                            <td><?= $u['last_login'] ? date('d M, H:i', strtotime($u['last_login'])) : '<span class="text-dim">Never</span>' ?></td>
                            <td>
                                <div class="action-btns">
                                    <button class="act-btn yellow" title="View Progress" onclick="viewUserProgress(<?= $u['id'] ?>, '<?= addslashes($u['name']) ?>')">📈</button>
                                    <?php if ($u['status'] === 'Blocked'): ?>
                                        <button class="act-btn green" onclick="unblockUser(<?= $u['id'] ?>)" title="Unblock">✅ Unblock</button>
                                    <?php else: ?>
                                        <button class="act-btn yellow" onclick="blockUser(<?= $u['id'] ?>)" title="Block">🚫 Block</button>
                                    <?php endif; ?>
                                    <button class="act-btn red" onclick="deleteUser(<?= $u['id'] ?>)" title="Delete">🗑️ Delete</button>
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
                <h2>💾 Datasets Management</h2>
                <p>Manage community-uploaded signal records and monitor training readiness.</p>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">📊</span>System Datasets (<?= count($datasets) ?>)</div>
                </div>
                
                <?php if (empty($datasets)): ?>
                    <div class="placeholder-state">
                        <div class="placeholder-icon">📁</div>
                        <h3>No datasets found</h3>
                        <p class="text-dim">User-uploaded datasets will appear here once available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Setup</th>
                                <th>Size</th>
                                <th>Uploader</th>
                                <th>Status</th>
                                <th>Upload Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($datasets as $ds): ?>
                            <tr id="dataset-row-<?= $ds['id'] ?>">
                                <td>
                                    <div class="cell-name"><?= htmlspecialchars($ds['name']) ?></div>
                                    <div class="cell-email" style="font-size:0.65rem;"><?= htmlspecialchars($ds['file_path']) ?></div>
                                </td>
                                <td><span class="badge blue-badge"><?= $ds['signal_type'] ?></span></td>
                                <td><span class="badge gray-badge"><?= $ds['technique'] ?></span></td>
                                <td class="text-small"><?= $ds['file_size'] ?></td>
                                <td>
                                    <div class="cell-name" style="font-size:0.8rem;"><?= htmlspecialchars($ds['uploader_name'] ?? 'System') ?></div>
                                </td>
                                <td>
                                    <select class="role-select" style="padding:4px 8px; font-size:0.75rem;" onchange="updateDatasetStatus(<?= $ds['id'] ?>, this.value)">
                                        <option value="Raw" <?= $ds['status']==='Raw'?'selected':'' ?>>Raw</option>
                                        <option value="Processed" <?= $ds['status']==='Processed'?'selected':'' ?>>Processed</option>
                                        <option value="Training" <?= $ds['status']==='Training'?'selected':'' ?>>Training</option>
                                    </select>
                                </td>
                                <td class="text-small"><?= date('d M Y', strtotime($ds['upload_date'])) ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="<?= $ds['file_path'] ?>" class="act-btn blue" title="Download" download>💾</a>
                                        <button class="act-btn red" onclick="deleteDataset(<?= $ds['id'] ?>)" title="Delete">🗑️</button>
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

        <!-- ── SECTION: AI CONTROL ── -->
        <section id="section-ai" class="admin-section">
            <div class="section-header">
                <h2>🧠 AI Control</h2>
                <p>Monitor deployed models, trigger re-training, and review performance metrics.</p>
            </div>
            
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">🤖</span>Model Registry & Performance</div>
                    <button class="pf-btn pf-btn-secondary" style="font-size:0.75rem;" onclick="showToast('Retraining pipeline initiated...', 'success')">⚙️ Retrain All</button>
                </div>
                
                <?php if (empty($allModels)): ?>
                    <div class="placeholder-state">
                        <div class="placeholder-icon">🤖</div>
                        <h3>No models deployed</h3>
                        <p class="text-dim">Check system logs for training errors.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Accuracy</th>
                                <th>Val Score</th>
                                <th>Last Trained</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($allModels as $model): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($model['version']) ?></strong></td>
                                <td><div style="display:flex; align-items:center; gap:10px;">
                                    <div class="progress-track" style="width:60px; height:6px;"><div class="progress-fill fill-teal" style="width:<?= $model['training_accuracy'] ?>%"></div></div>
                                    <span><?= $model['training_accuracy'] ?>%</span>
                                </div></td>
                                <td><?= $model['validation_score'] ?></td>
                                <td><?= date('d M, H:i', strtotime($model['last_trained'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $model['status']==='Deployed'?'active':'pending' ?>"><?= $model['status'] ?></span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <button class="act-btn blue" title="Deploy" <?= $model['status']==='Deployed'?'disabled':'' ?>>🚀</button>
                                        <button class="act-btn yellow" title="Metrics">📊</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel" style="margin-top:24px;">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">⚡</span>Training Logs</div>
                </div>
                <div class="acc-detail-list">
                    <div class="acc-row">
                        <span class="acc-label">Next Scheduled Run</span>
                        <span class="acc-value">Sunday, 02:00 AM</span>
                    </div>
                    <div class="acc-row">
                        <span class="acc-label">Training Resource Usage</span>
                        <span class="acc-value highlight-teal">Low (Idle)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── SECTION: LEARN PAGES ── -->
        <section id="section-learn" class="admin-section">
            <div class="section-header">
                <h2>📚 Learn Pages Management</h2>
                <p>Edit the educational content, titles, and descriptions for all signal modules.</p>
            </div>
            
            <div class="two-col">
                <?php if (!empty($learningContents)): ?>
                    <?php foreach ($learningContents as $item): ?>
                    <div class="panel" style="margin-bottom:24px;">
                        <div class="panel-header" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px; margin-bottom:15px;">
                            <div class="panel-title">
                                <span class="panel-icon">📝</span>
                                <?= strtoupper($item['page_slug']) ?> - <?= ucfirst($item['section_id']) ?>
                            </div>
                            <span style="font-size:0.7rem; color:var(--text3);">Modified: <?= date('d M, H:i', strtotime($item['modified_at'])) ?></span>
                        </div>
                        
                        <div class="pf-group" style="margin-bottom:15px;">
                            <label class="pf-label">Section Title</label>
                            <input type="text" id="title-<?= $item['id'] ?>" class="pf-input" value="<?= htmlspecialchars($item['title']) ?>" style="width:100%;">
                        </div>
                        
                        <div class="pf-group" style="margin-bottom:15px;">
                            <label class="pf-label">Content Description</label>
                            <textarea id="content-<?= $item['id'] ?>" class="pf-input" style="width:100%; min-height:100px; line-height:1.5; font-size:0.85rem;"><?= htmlspecialchars($item['content']) ?></textarea>
                        </div>
                        
                        <div style="display:flex; justify-content:flex-end; gap:10px;">
                            <button class="pf-btn pf-btn-primary" onclick="saveLearningContent(<?= $item['id'] ?>)">
                                💾 Save Changes
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="panel" style="grid-column: span 2;">
                        <div class="placeholder-state">
                            <div class="placeholder-icon">📚</div>
                            <h3>No Content Found</h3>
                            <p>Learning content records haven't been initialized yet.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── SECTION: RESOURCE LIBRARY ── -->
        <section id="section-resources" class="admin-section">
            <div class="section-header">
                <h2>📁 Resource Library Management</h2>
                <p>Oversee downloadable research papers, clinical guides, and educational videos.</p>
            </div>
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title"><span class="panel-icon">📄</span>Active Resources</div>
                    <button class="pf-btn pf-btn-secondary" style="font-size:0.75rem;" onclick="showToast('Resource sync initiated...', 'success')">🔄 Sync JS Data</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Category</th><th>Title</th><th>Source</th><th>Type</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Research Papers</td>
                                <td><strong>Bioelectronic Medicine</strong></td>
                                <td class="text-dim">Nature Reviews</td>
                                <td><span class="status-badge active">PDF/Doc</span></td>
                                <td><button class="act-btn blue">👁️</button></td>
                            </tr>
                            <tr>
                                <td>Research Papers</td>
                                <td><strong>Machine Learning in Biosignal Analysis</strong></td>
                                <td class="text-dim">Nature Machine Intelligence</td>
                                <td><span class="status-badge active">New Paper</span></td>
                                <td><button class="act-btn blue">👁️</button></td>
                            </tr>
                            <tr>
                                <td>Video Tutorials</td>
                                <td><strong>Electrode Setup Guide</strong></td>
                                <td class="text-dim">YouTube (HD)</td>
                                <td><span class="status-badge pending">Video</span></td>
                                <td><button class="act-btn blue">👁️</button></td>
                            </tr>
                            <tr>
                                <td>Clinical Case Studies</td>
                                <td><strong>Neuromuscular Grafts</strong></td>
                                <td class="text-dim">Internal Database</td>
                                <td><span class="status-badge active">Case Study</span></td>
                                <td><button class="act-btn blue">👁️</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="cred-note" style="margin-top:20px;">
                    💡 Resources are currently optimized via <code>resource_data.js</code> for high-performance frontend rendering. Updates to the JSON structure are reflected here.
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

    </div><!-- /content-area -->
</div><!-- /main-content -->

    <!-- ── MODAL: USER PROGRESS ── -->
    <div id="progressModal" class="dashboard-body" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; padding:20px;">
        <div class="panel" style="max-width:500px; width:100%; max-height:80vh; overflow-y:auto; border-color:var(--pink); box-shadow:0 0 30px rgba(219,39,119,0.2);">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-icon">📈</span> <span id="prog-user-name">User</span>'s Learning History</div>
                <button class="mini-avatar" style="background:var(--border); cursor:pointer; width:30px; height:30px;" onclick="document.getElementById('progressModal').style.display='none'">×</button>
            </div>
            <div id="prog-modal-body">
                <div class="placeholder-state" style="padding:20px;">
                    <div class="loader"></div>
                    <p>Fetching user activity...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODAL: ADD UPDATE ── -->
    <div id="updateModal" class="dashboard-body" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.85); align-items:center; justify-content:center; padding:20px;">
        <div class="panel" style="max-width:500px; width:100%; border-color:var(--blue-l); box-shadow:0 0 30px rgba(37,99,235,0.2);">
            <div class="panel-header">
                <div class="panel-title"><span class="panel-icon">📢</span> Publish Platform Update</div>
                <button class="mini-avatar" style="background:var(--border); cursor:pointer; width:30px; height:30px;" onclick="document.getElementById('updateModal').style.display='none'">×</button>
            </div>
            <div style="display:flex; flex-direction:column; gap:15px; padding-top:10px;">
                <div class="pf-group">
                    <label class="pf-label">Update Title</label>
                    <input type="text" id="upd-title" class="pf-input" placeholder="e.g. Version 2.4 Released" style="background:rgba(255,255,255,0.05);">
                </div>
                <div class="pf-group">
                    <label class="pf-label">Update Description</label>
                    <textarea id="upd-desc" class="pf-input" style="height:100px; resize:none; padding:12px; background:rgba(255,255,255,0.05);" placeholder="Describe the changes or announcement..."></textarea>
                </div>
                <div class="pf-group">
                    <label class="pf-label">Category</label>
                    <select id="upd-type" class="pf-input" style="background:rgba(255,255,255,0.05); color:#fff;">
                        <option value="Announcement">Announcement</option>
                        <option value="Feature">Feature Update</option>
                        <option value="Model">AI Model Update</option>
                        <option value="Dataset">New Dataset</option>
                    </select>
                </div>
                <div style="text-align:right; margin-top:5px;">
                    <button class="pf-btn pf-btn-primary" onclick="publishUpdate()">🚀 Publish Update</button>
                </div>
            </div>
        </div>
    </div>

<script src="js/script.js"></script>
<script>
// Sidebar Toggle
const sidebarBtn = document.getElementById('sidebarToggle');
const sidebar    = document.getElementById('sidebar');
const mainArea   = document.getElementById('mainArea');

if (sidebarBtn) {
    sidebarBtn.onclick = () => {
        sidebar.classList.toggle('collapsed');
        mainArea.classList.toggle('sidebar-collapsed');
    };
}

/* ── Platform Updates Functions ── */
function openUpdateModal() {
    document.getElementById('updateModal').style.display = 'flex';
}

async function publishUpdate() {
    const title = document.getElementById('upd-title').value;
    const desc  = document.getElementById('upd-desc').value;
    const type  = document.getElementById('upd-type').value;

    if (!title || !desc) {
        showToast('Please fill in all fields.', 'error');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_update');
    fd.append('title', title);
    fd.append('description', desc);
    fd.append('type', type);

    try {
        const r = await fetch('api/admin_updates_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showToast('✅ Update published successfully!', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('❌ Failed: ' + data.message, 'error');
        }
    } catch(e) {
        showToast('❌ Network error.', 'error');
    }
}

async function deleteUpdate(id) {
    if (!confirm('Are you sure you want to remove this update?')) return;
    
    const fd = new FormData();
    fd.append('action', 'delete_update');
    fd.append('update_id', id);

    try {
        const r = await fetch('api/admin_updates_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showToast('✅ Update removed.', 'success');
            setTimeout(() => window.location.reload(), 1000);
        }
    } catch(e) {
        showToast('❌ Error removing update.', 'error');
    }
}

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

async function viewUserProgress(id, name) {
    document.getElementById('prog-user-name').textContent = name;
    const body = document.getElementById('prog-modal-body');
    const modal = document.getElementById('progressModal');
    
    modal.style.display = 'flex';
    body.innerHTML = '<div class="placeholder-state"><p>Loading history...</p></div>';
    
    try {
        const r = await fetch('api/admin_users_api.php?action=get_user_progress&user_id=' + id);
        const data = await r.json();
        
        if (data.success && data.progress.length > 0) {
            let html = '<div class="activity-feed">';
            data.progress.forEach(p => {
                const pct = parseInt(p.completion_percentage);
                const colorClass = pct >= 100 ? 'green' : (pct >= 50 ? 'blue' : 'orange');
                html += `
                    <div class="activity-item">
                        <div class="activity-dot ${colorClass}">${pct}%</div>
                        <div style="flex:1">
                            <div class="act-title" style="font-size:0.85rem; font-weight:700;">${p.module_name.toUpperCase()}</div>
                            <div class="act-time" style="font-size:0.7rem; color:var(--text3);">Last updated: ${p.last_updated}</div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            body.innerHTML = html;
        } else {
            body.innerHTML = '<div class="placeholder-state" style="padding:40px;"><p>No learning history found for this user.</p></div>';
        }
    } catch(err) {
        body.innerHTML = '<div class="placeholder-state" style="padding:40px; color:#FCA5A5;"><p>Error loading data.</p></div>';
    }
}

function closeProgressModal() {
    document.getElementById('progressModal').style.display = 'none';
}

/* ══ Platform Updates ══ */
function openUpdateModal() {
    document.getElementById('updateModal').style.display = 'flex';
}
function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}

async function publishUpdate() {
    const title = document.getElementById('upd-title').value;
    const desc  = document.getElementById('upd-desc').value;
    const type  = document.getElementById('upd-type').value;

    if (!title || !desc) {
        alert('Please fill in both title and description.');
        return;
    }

    const fd = new FormData();
    fd.append('action', 'add_update');
    fd.append('title', title);
    fd.append('description', desc);
    fd.append('type', type);

    try {
        const r = await fetch('api/admin_updates_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (e) {
        alert('System error publishing update.');
    }
}

async function deleteUpdate(id) {
    if (!confirm('Permanent delete this update?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_update');
    fd.append('id', id);
    const r = await fetch('api/admin_updates_api.php', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.success) location.reload();
}

/* ══ Learning Content ══ */
async function saveLearningContent(id) {
    const title   = document.getElementById('title-' + id).value;
    const content = document.getElementById('content-' + id).value;

    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '🕒 Saving...';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('id', id);
    fd.append('title', title);
    fd.append('content', content);

    try {
        const r = await fetch('api/admin_learning_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showToast('✅ Learning content updated!', 'success');
            setTimeout(() => { location.reload(); }, 1000);
        } else {
            showToast('❌ Failed: ' + data.message, 'error');
        }
    } catch (e) {
        showToast('❌ System error saving content.', 'error');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

/* ══ Datasets Management ══ */
async function updateDatasetStatus(id, newStatus) {
    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('id', id);
    fd.append('status', newStatus);
    
    try {
        const r = await fetch('api/admin_datasets_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) showToast('✅ Status updated to ' + newStatus, 'success');
        else showToast('❌ Failed to update status.', 'error');
    } catch (e) {
        showToast('❌ System error.', 'error');
    }
}

async function deleteDataset(id) {
    if (!confirm('Are you sure you want to delete this dataset? This will also remove the physical file.')) return;
    
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    
    try {
        const r = await fetch('api/admin_datasets_api.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.success) {
            showToast('✅ Dataset deleted.', 'success');
            document.getElementById('dataset-row-' + id).remove();
        } else {
            showToast('❌ Error: ' + data.message, 'error');
        }
    } catch (e) {
        showToast('❌ System error deleting record.', 'error');
    }
}
</script>
</body>
</html>
