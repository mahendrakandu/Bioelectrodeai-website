<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
if ($_SESSION['user_role'] === 'Admin') { header('Location: admin_dashboard.php'); exit; }

$userName    = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userRole    = htmlspecialchars($_SESSION['user_role'] ?? 'Student');
$userInitial = strtoupper(substr($userName, 0, 1));

$page = htmlspecialchars($_GET['page'] ?? 'home');

$roleChip = [
    'Student'    => ['bg'=>'rgba(37,99,235,0.15)',  'color'=>'#93C5FD', 'emoji'=>'🎓'],
    'Researcher' => ['bg'=>'rgba(5,150,105,0.15)',  'color'=>'#6EE7B7', 'emoji'=>'🔬'],
    'Educator'   => ['bg'=>'rgba(124,58,237,0.15)', 'color'=>'#C4B5FD', 'emoji'=>'🏫'],
];
$rc = $roleChip[$userRole] ?? $roleChip['Student'];

$modules = [
    'learn'     => ['icon'=>'📚','title'=>'Learn',            'desc'=>'Study topics'],
    'compare'   => ['icon'=>'⚡','title'=>'Compare',          'desc'=>'Side by side'],
    'simulator' => ['icon'=>'🖥️','title'=>'Simulator',        'desc'=>'Interactive'],
    'ai'        => ['icon'=>'🧠','title'=>'AI Analysis',      'desc'=>'Smart insights'],
    'visualize' => ['icon'=>'📊','title'=>'Visualizations',   'desc'=>'Graphs & charts'],
    'ecg'       => ['icon'=>'❤️','title'=>'ECG (Electrocardiography)', 'desc'=>'Heart electrical activity', 'next'=>'eeg'],
    'eeg'       => ['icon'=>'🧠','title'=>'EEG (Electroencephalography)', 'desc'=>'Brain wave patterns', 'next'=>'emg'],
    'emg'       => ['icon'=>'💪','title'=>'EMG (Electromyography)', 'desc'=>'Muscle electrical signals', 'next'=>'electrode_placement'],
    'electrode_placement' => ['icon'=>'📍','title'=>'Electrode Placement Guide', 'desc'=>'Proper positioning guide', 'next'=>'recording_techniques'],
    'recording_techniques' => ['icon'=>'⚡','title'=>'Recording Techniques', 'desc'=>'Monopolar vs Bipolar analysis', 'next'=>'compare'],
    'compare'   => ['icon'=>'⚖️','title'=>'Bipolar vs Monopolar',   'desc'=>'Expert comparison guide', 'next'=>'pros_cons'],
    'pros_cons'   => ['icon'=>'⚖️','title'=>'Pros & Cons Analysis','desc'=>'Detailed comparison', 'next'=>'decision_guide'],
    'decision_guide' => ['icon'=>'🧭','title'=>'Decision Guide','desc'=>'Selection tool', 'next'=>'signal_quality'],
    'signal_quality' => ['icon'=>'📡','title'=>'Signal Quality','desc'=>'Waveform analysis', 'next'=>'bipolar_recording'],
    'bipolar_recording' => ['icon'=>'🧬','title'=>'Bipolar Recording', 'desc'=>'Differential signal analysis', 'next'=>'monopolar_recording'],
    'monopolar_recording' => ['icon'=>'📡','title'=>'Monopolar Recording', 'desc'=>'Absolute potential analysis', 'next'=>'quiz'],
    'quiz'      => ['icon'=>'❓','title'=>'Take Quiz',         'desc'=>'Test your knowledge'],
    'educational'  => ['icon'=>'🏥','title'=>'Practice Scenarios',   'desc'=>'Real-world examples'],
    'report'    => ['icon'=>'📄','title'=>'Comparison Report','desc'=>'Side-by-side analysis'],
    'resources' => ['icon'=>'📁','title'=>'Resources Library','desc'=>'Study materials'],
    'glossary'  => ['icon'=>'📖','title'=>'Glossary',         'desc'=>'Browse definitions for bioelectrode terminology.'],
    'settings'  => ['icon'=>'⚙️','title'=>'Settings',         'desc'=>'Manage your account preferences and notifications.'],
    'profile'   => ['icon'=>'👤','title'=>'Profile',          'desc'=>'Manage your account settings and view your learning statistics'],
    'edit_profile' => ['icon'=>'✏️','title'=>'Edit Profile',  'desc'=>'Update your information'],
];
$m = $modules[$page] ?? null;

// ═══ Real-Time Learning Progress Tracking ═══
if (isset($_SESSION['user_id']) && $m) {
    require_once __DIR__ . '/api/db.php';
    $_sdb = getDB();
    $uid = $_SESSION['user_id'];
    
    // Modules that count towards learning progress
    $trackableModules = [
        'ecg', 'eeg', 'emg', 'electrode_placement', 'recording_techniques', 
        'compare', 'pros_cons', 'decision_guide', 'signal_quality', 
        'bipolar_recording', 'monopolar_recording'
    ];
    
    if (in_array($page, $trackableModules)) {
        // Mark current page as in-progress (50%)
        $checkP = $_sdb->prepare("SELECT id FROM user_progress WHERE user_id=? AND module_name=?");
        $checkP->bind_param('is', $uid, $page);
        $checkP->execute();
        if ($checkP->get_result()->num_rows === 0) {
            $insP = $_sdb->prepare("INSERT INTO user_progress (user_id, module_name, completion_percentage) VALUES (?,?,50)");
            $insP->bind_param('is', $uid, $page);
            $insP->execute(); $insP->close();
        }
        $checkP->close();
        
        // Mark previous module as 100% complete
        $idx = array_search($page, $trackableModules);
        if ($idx > 0) {
            $prev = $trackableModules[$idx - 1];
            $updP = $_sdb->prepare("UPDATE user_progress SET completion_percentage=100 WHERE user_id=? AND module_name=?");
            $updP->bind_param('is', $uid, $prev);
            $updP->execute(); $updP->close();
        }
    } elseif ($page === 'quiz') {
        // Reaching quiz marks the last module as complete
        $lastM = end($trackableModules);
        $updP = $_sdb->prepare("UPDATE user_progress SET completion_percentage=100 WHERE user_id=? AND module_name=?");
        $updP->bind_param('is', $uid, $lastM);
        $updP->execute(); $updP->close();
    }
}

// Fetch latest platform updates for home page
$platformUpdates = [];
$dynContent = [];
if ($page === 'home' || $page === 'settings' || in_array($page, ['ecg', 'eeg', 'emg', 'electrode_placement'])) {
    if (!isset($_sdb)) { require_once __DIR__ . '/api/db.php'; $_sdb = getDB(); }
    
    if ($page === 'home' || $page === 'settings') {
        $updRes = $_sdb->query("SELECT title, description, type, added_date FROM app_items ORDER BY added_date DESC LIMIT 3");
        if ($updRes) while ($row = $updRes->fetch_assoc()) $platformUpdates[] = $row;
    }

    // Fetch dynamic learning content
    $cRes = $_sdb->prepare("SELECT section_id, title, content FROM learning_content WHERE page_slug = ?");
    $cRes->bind_param("s", $page);
    $cRes->execute();
    $cResult = $cRes->get_result();
    while ($row = $cResult->fetch_assoc()) {
        $dynContent[$row['section_id']] = $row;
    }
    $cRes->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $m ? $m['title'].' — ' : '' ?>BioElectrode AI</title>
    <meta name="description" content="BioElectrode AI — Advanced signal analysis & learning platform.">
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <script>
        // Apply Dark/Light theme instantly before page render
        if (localStorage.getItem('set_tog_tog-dark') === '0') {
            document.documentElement.classList.add('light-mode');
        }
    </script>
    <script src="quiz_data.js"></script>
    <script src="scenario_data.js"></script>
    <script src="resource_data.js"></script>
    <style>
        /* Shared Modal System */
        .set-modal-overlay { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(0,0,0,0.8); 
            backdrop-filter: blur(8px);
            z-index: 9999; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
            animation: fadeIn 0.3s ease;
        }
        .set-modal-overlay.open { display: flex; }
        .set-modal-content { 
            background: #1a1f2e; 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 24px; 
            width: 100%; 
            max-height: 90vh; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-up { animation: fadeUp 0.5s ease forwards; }

        /* Interactive Expansion System */
        .expand-card { cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; }
        .expand-card:hover { transform: translateY(-2px); background: rgba(255,255,255,0.04) !important; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
        .expand-detail { 
            max-height: 0; 
            overflow: hidden; 
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease; 
            opacity: 0;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            margin-top: 0;
        }
        .expand-card.active .expand-detail { 
            max-height: 500px; 
            opacity: 1; 
            margin-top: 12px;
            padding: 15px;
            border: 1px dashed rgba(255,255,255,0.1);
        }
        .expand-icon { transition: transform 0.3s ease; display: inline-block; }
        .expand-card.active .expand-icon { transform: rotate(180deg); }
    </style>
</head>
<body class="dashboard-body">
<div id="particles"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside class="sidebar glass" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-bg-glow"></div>
        <div class="brand-icon glow-blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <div class="brand-text">
            <div class="brand-name">BioElectrode AI</div>
            <div class="brand-tagline">Advanced Analysis</div>
        </div>
    </div>

    <a href="dashboard.php?page=profile" class="sidebar-user glass-card" style="margin: 20px; text-decoration:none;">
        <div class="user-avatar glow-purple">
            <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile">
            <?php else: ?>
                <?= $userInitial ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <div class="u-name"><?= $userName ?></div>
            <div class="u-role" style="background:<?= $rc['bg'] ?>;color:<?= $rc['color'] ?>;">
                <?= $rc['emoji'] ?> <?= $userRole ?>
            </div>
        </div>
    </a>

    <nav class="sidebar-nav">
        <!-- Dashboard Sections -->
        <div class="nav-label">Workspace</div>
        <a href="dashboard.php" class="nav-item <?= $page==='home'?'active':'' ?>">
            <span class="nav-icon">🏠</span>
            <span class="nav-item-label">Home</span>
        </a>
        <a href="dashboard.php?page=ai" class="nav-item <?= $page==='ai'?'active':'' ?>">
            <span class="nav-icon">🧠</span>
            <span class="nav-item-label">AI Analysis</span>
            <span class="nav-badge glow-purple">PRO</span>
        </a>
        <a href="dashboard.php?page=visualize" class="nav-item <?= $page==='visualize'?'active':'' ?>">
            <span class="nav-icon">📊</span>
            <span class="nav-item-label">Analytics</span>
        </a>

        <div class="nav-label">Intelligence</div>
        <a href="dashboard.php?page=ecg" class="nav-item <?= $page==='ecg'?'active':'' ?>">
            <span class="nav-icon">❤️</span>
            <span class="nav-item-label">ECG Engine</span>
        </a>
        <a href="dashboard.php?page=eeg" class="nav-item <?= $page==='eeg'?'active':'' ?>">
            <span class="nav-icon">🧠</span>
            <span class="nav-item-label">EEG Engine</span>
        </a>
        <a href="dashboard.php?page=emg" class="nav-item <?= $page==='emg'?'active':'' ?>">
            <span class="nav-icon">💪</span>
            <span class="nav-item-label">EMG Engine</span>
        </a>

        <div class="nav-label">Education</div>
        <a href="dashboard.php?page=learn" class="nav-item <?= $page==='learn'?'active':'' ?>">
            <span class="nav-icon">📚</span>
            <span class="nav-item-label">Learning Academy</span>
        </a>
        <a href="dashboard.php?page=quiz" class="nav-item <?= $page==='quiz'?'active':'' ?>">
            <span class="nav-icon">❓</span>
            <span class="nav-item-label">Knowledge Quiz</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="api/logout_api.php" class="logout-btn glass">
            <span>🚪</span>
            <span class="logout-label">Sign Out</span>
        </a>
    </div>
</aside>

<!-- ═══ MAIN CONTENT ═══ -->
<div class="main-content" id="mainArea">
    <header class="topbar glass">
        <div class="topbar-left">
            <button class="tb-btn sidebar-toggle" id="sidebarToggle">☰</button>
            <div class="topbar-title">
                <h1 class="fade-up"><?= $m ? $m['icon'].' '.$m['title'] : 'Explore Dashboard' ?></h1>
                <p class="fade-up delay-1"><?= $m ? $m['desc'] : 'Welcome back, '.$userName.'.' ?></p>
            </div>
        </div>
        <div class="tb-right">
            <div class="tb-actions">
                <button class="tb-btn glass" title="System Notifications">🔔</button>
                <button class="tb-btn glass" title="Settings">⚙️</button>
            </div>
            <a href="dashboard.php?page=profile" class="tb-user glass-card">
                <div class="tb-avatar">
                   <?= $userInitial ?>
                </div>
                <span class="tb-user-name"><?= $userName ?></span>
            </a>
        </div>
    </header>

    <div class="content-area scroll-container">

    <?php if (!$m): // ═══ HOME PAGE ═══ ?>

        <!-- Welcome Banner -->
        <div class="welcome-banner-premium glow-blue fade-up">
            <div class="banner-content">
                <div class="banner-tag">BIOELECTRODE AI v4.0</div>
                <h2>Next-Gen Signal Intelligence</h2>
                <p>Welcome back, <strong><?= $userName ?></strong>. You have 3 active signal modules and 1 pending quiz. Start your session today.</p>
                <div class="banner-actions">
                    <a href="dashboard.php?page=ai" class="btn btn-primary glow-blue">Launch AI Analyzer</a>
                    <a href="dashboard.php?page=learn" class="btn btn-secondary glass">Explore Academy</a>
                </div>
            </div>
            <div class="banner-visual-elements">
                <div class="pulse-ring"></div>
                <div class="pulse-ring delay-1"></div>
                <div class="floating-icon">🧠</div>
            </div>
        </div>

        <?php if (!empty($platformUpdates)): ?>
        <!-- Latest Intelligence Feed -->
        <div class="section-header-modern fade-up delay-1">
            <span class="sh-icon">📢</span>
            <div class="sh-text">
                <h3>Intelligence Feed</h3>
                <p>Latest platform updates and research findings</p>
            </div>
        </div>
        <div class="intelligence-card glass-card fade-up delay-1">
            <div class="ic-header">
                <span class="badge-pulse"><?= strtoupper($platformUpdates[0]['type'] ?? 'UPDATE') ?></span>
                <span class="ic-date"><?= date('M d, Y', strtotime($platformUpdates[0]['added_date'])) ?></span>
            </div>
            <h4><?= htmlspecialchars($platformUpdates[0]['title']) ?></h4>
            <p><?= htmlspecialchars($platformUpdates[0]['description']) ?></p>
            <a href="#" class="ic-link">Read Full Intelligence Report →</a>
        </div>
        <?php endif; ?>

        <!-- Modules Grid -->
        <div class="section-header-modern fade-up delay-2">
            <span class="sh-icon">🚀</span>
            <div class="sh-text">
                <h3>Core Engines</h3>
                <p>Access your primary research and diagnostic tools</p>
            </div>
        </div>
        <div class="premium-modules-grid">
            <a href="dashboard.php?page=ai" class="p-module glass-card fade-up delay-2">
                <div class="pm-icon purple">🧠</div>
                <div class="pm-info">
                    <h4>AI Signal Analysis</h4>
                    <span>Smart feature extraction</span>
                </div>
                <div class="pm-status online"></div>
            </a>
            <a href="dashboard.php?page=visualize" class="p-module glass-card fade-up delay-3">
                <div class="pm-icon blue">📊</div>
                <div class="pm-info">
                    <h4>Data Visualization</h4>
                    <span>Real-time waveform charts</span>
                </div>
            </a>
            <a href="dashboard.php?page=compare" class="p-module glass-card fade-up delay-4">
                <div class="pm-icon teal">⚖️</div>
                <div class="pm-info">
                    <h4>Signal Comparator</h4>
                    <span>Bipolar vs Monopolar</span>
                </div>
            </a>
            <a href="dashboard.php?page=simulator" class="p-module glass-card fade-up delay-5">
                <div class="pm-icon orange">🖥️</div>
                <div class="pm-info">
                    <h4>Lab Simulator</h4>
                    <span>Virtual electrode setup</span>
                </div>
            </a>
        </div>

        <div class="section-title fade-up">🚀 What you can do</div>
        <div class="section-sub fade-up">Features and tools integrated from the BioElectrode App</div>
        <div class="actions-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
            <a href="dashboard.php?page=learn" class="action-card fade-up delay-1">
                <div class="ac-icon" style="background:rgba(21, 101, 192, 0.15);color:#60A5FA;">📚</div>
                <div>
                    <div class="ac-title">Step-by-Step Theory</div>
                    <div class="ac-subtitle">Learn fundamental concepts of electrodes</div>
                </div>
                <span class="ac-arrow">›</span>
            </a>
            <a href="dashboard.php?page=simulator" class="action-card fade-up delay-2">
                <div class="ac-icon" style="background:rgba(0, 137, 123, 0.15);color:#34D399;">🔬</div>
                <div>
                    <div class="ac-title">Interactive Simulators</div>
                    <div class="ac-subtitle">Practice electrode placement &amp; recording</div>
                </div>
                <span class="ac-arrow">›</span>
            </a>
            <a href="dashboard.php?page=ai" class="action-card fade-up delay-3">
                <div class="ac-icon" style="background:rgba(123, 31, 162, 0.15);color:#C4B5FD;">🤖</div>
                <div>
                    <div class="ac-title">AI Smart Insights</div>
                    <div class="ac-subtitle">Get personalized advice and signal feedback</div>
                </div>
                <span class="ac-arrow">›</span>
            </a>
            <a href="dashboard.php?page=educational" class="action-card fade-up delay-4">
                <div class="ac-icon" style="background:rgba(239, 68, 68, 0.15);color:#FCA5A5;">🏥</div>
                <div>
                    <div class="ac-title">Real Practice Scenarios</div>
                    <div class="ac-subtitle">Explore actual medical scenarios</div>
                </div>
                <span class="ac-arrow">›</span>
            </a>
            <a href="dashboard.php?page=quiz" class="action-card fade-up delay-5">
                <div class="ac-icon" style="background:rgba(245, 158, 11, 0.15);color:#FCD34D;">📊</div>
                <div>
                    <div class="ac-title">Track your Progress</div>
                    <div class="ac-subtitle">Take quizzes &amp; earn achievements</div>
                </div>
                <span class="ac-arrow">›</span>
            </a>
        </div>

    <?php elseif ($page === 'learn'): // ═══ LEARN MODULE ═══ ?>

        <div class="module-hero">
            <div class="hero-icon-box">📚</div>
            <div class="hero-text">
                <h2>Learning Hub</h2>
                <p>Comprehensive modules covering biomedical signals, recording techniques, and AI-powered analysis.</p>
            </div>
        </div>

        <div class="mod-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dna" style="margin-right:8px; vertical-align:middle;"><path d="m8 8 8 8"/><path d="m8 16 8-8"/><path d="m13 3 3 3"/><path d="m9 19 3 3"/><path d="m18 8 3 3"/><path d="m2 13 3 3"/><path d="m21 13-3 3"/><path d="m5 8-3 3"/><path d="m13 21 3-3"/><path d="m9 5 3-3"/></svg> Biomedical Signals</div>
        <div class="mod-grid">
            <a href="dashboard.php?page=ecg" class="mod-card">
                <div class="mod-icon-wrap red"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart-pulse"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">ECG (Electrocardiography)</div>
                    <div class="mod-desc">Heart electrical activity</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=eeg" class="mod-card">
                <div class="mod-icon-wrap purple"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">EEG (Electroencephalography)</div>
                    <div class="mod-desc">Brain wave patterns</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=emg" class="mod-card">
                <div class="mod-icon-wrap teal" style="background:rgba(20,184,166,0.2); border:1px solid rgba(20,184,166,0.4);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dumbbell"><path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.767-2.828-2.828 1.767-1.767a2 2 0 1 1-2.828-2.829l1.767-1.767L9.11 8.405l-1.767 1.767a2 2 0 1 1-2.829-2.828l1.767-1.767-2.828-2.828 1.767-1.768a2 2 0 1 1-2.828-2.828l2.828 2.828 1.768-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.828 2.829l1.767-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.829 2.828l-1.768-1.767-2.828-2.828 1.767-1.767a2 2 0 1 1 2.828 2.829l-1.767 1.767 2.828 2.828-1.767 1.767Z"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">EMG (Electromyography)</div>
                    <div class="mod-desc">Muscle electrical signals</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
        </div>

        <div class="mod-section-title">📍 Electrode Techniques</div>
        <div class="mod-grid">
            <a href="dashboard.php?page=electrode_placement" class="mod-card">
                <div class="mod-icon-wrap teal" style="background:rgba(20,184,166,0.1); border:1px solid rgba(20,184,166,0.3);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-map-pin"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">Electrode Placement Guide</div>
                    <div class="mod-desc">Proper positioning for ECG, EEG & EMG</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=recording_techniques" class="mod-card">
                <div class="mod-icon-wrap blue" style="background:rgba(37,99,235,0.1); border:1px solid rgba(37,99,235,0.3);"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu"><rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">Recording Techniques</div>
                    <div class="mod-desc">Monopolar vs Bipolar analysis</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
        </div>

        <div class="mod-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book" style="margin-right:8px; vertical-align:middle;"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg> Core Learning Modules</div>
        <div class="mod-grid">
            <a href="dashboard.php?page=bipolar_recording" class="mod-card">
                <div class="mod-icon-wrap blue"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-up"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
                <div class="mod-text"><div class="mod-title">Bipolar Recording</div><div class="mod-desc">4 Modules • Differential technique</div></div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=monopolar_recording" class="mod-card">
                <div class="mod-icon-wrap orange"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trending-down"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg></div>
                <div class="mod-text"><div class="mod-title">Monopolar Recording</div><div class="mod-desc">4 Modules • Referential technique</div></div>
                <div class="mod-arrow">›</div>
            </a>
        </div>

        <div class="mod-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bot" style="margin-right:8px; vertical-align:middle;"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg> Advanced Analysis</div>
        <div class="mod-grid">
            <a href="dashboard.php?page=ai" class="mod-card ai-card">
                <div class="mod-icon-wrap" style="background:rgba(255,255,255,0.12);font-size:1.4rem;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-2.54Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-2.54Z"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">AI Analysis System</div>
                    <div class="mod-desc">Upload datasets & extract features using advanced AI algorithms</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
        </div>

        <div class="mod-section-title"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket" style="margin-right:8px; vertical-align:middle;"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.71-2.13.09-2.91a2.18 2.18 0 0 0-3.09-.09z"/><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0"/><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/></svg> Application Mastery</div>
        <div class="mod-grid">
            <a href="dashboard.php?page=simulator" class="mod-card">
                <div class="mod-icon-wrap orange" style="background:rgba(249,115,22,0.15); border-color:#F97316;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">Interactive Simulator</div>
                    <div class="mod-desc">Hands-on practice with virtual recording equipment</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=signal_quality" class="mod-card">
                <div class="mod-icon-wrap pink" style="background:rgba(219,39,119,0.15); border-color:#DB2777;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart-big"><path d="M3 3v18h18"/><rect width="4" height="7" x="7" y="10" rx="1"/><rect width="4" height="12" x="15" y="5" rx="1"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">Signal Quality Hub</div>
                    <div class="mod-desc">Master the assessment of real-time signal integrity</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
            <a href="dashboard.php?page=educational" class="mod-card">
                <div class="mod-icon-wrap green" style="background:rgba(16,185,129,0.15); border-color:#10B981;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-graduation-cap"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
                <div class="mod-text">
                    <div class="mod-title">Practice Scenarios</div>
                    <div class="mod-desc">Apply your knowledge to real-world medical cases</div>
                </div>
                <div class="mod-arrow">›</div>
            </a>
        </div>

        <div class="mod-section-title" id="quick-start"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-help-circle" style="margin-right:8px; vertical-align:middle;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg> App Quick Start & Tips</div>
        <div class="detail-card" style="background:rgba(37,99,235,0.05); border:1px solid rgba(37,99,235,0.1); border-radius:16px; margin-top:15px; margin-bottom: 40px;">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px;">
                <div class="educational-item" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px;">
                    <div class="educational-dot" style="background:var(--blue);"></div>
                    <div><strong>Upload Any Dataset:</strong> Use the AI Analysis page to upload .csv or .txt files for instant signal feature extraction.</div>
                </div>
                <div class="educational-item" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px;">
                    <div class="educational-dot" style="background:var(--purple);"></div>
                    <div><strong>Track Your Journey:</strong> Your progress and quiz scores are automatically saved in your <a href="dashboard.php?page=profile" style="color:var(--blue-l); text-decoration:none;">Personal Profile</a>.</div>
                </div>
                <div class="educational-item" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px;">
                    <div class="educational-dot" style="background:var(--teal);"></div>
                    <div><strong>Compare Side-by-Side:</strong> Use the "Compare" module to see how Bipolar and Monopolar setups affect signal SNR in real-time.</div>
                </div>
                <div class="educational-item">
                    <div class="educational-dot" style="background:var(--orange);"></div>
                    <div><strong>Dark Mode Toggle:</strong> Find the theme switch in Settings to customize your visual experience.</div>
                </div>
            </div>
        </div>

    <?php elseif ($page === 'ecg'): // ═══ ECG DETAIL ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px;">
            <div class="hero-icon-box" style="font-size:2.8rem;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart-pulse"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/><path d="M3.22 12H9.5l.5-1 2 4.5 2-7 1.5 3.5h5.27"/></svg></div>
            <div class="hero-text">
                <h2>ECG</h2>
                <p style="color:#F472B6;">Electrocardiography — Heart electrical activity</p>
            </div>
        </div>

        <div style="max-width:860px;">
            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg></span> <?= htmlspecialchars($dynContent['intro']['title'] ?? 'What is ECG?') ?></div>
                <div class="text-small">
                    <?= nl2br(htmlspecialchars($dynContent['intro']['content'] ?? "Electrocardiography (ECG or EKG) measures the electrical activity of the heart over time using electrodes placed on the skin. It captures the depolarization and repolarization of heart muscles during each cardiac cycle.")) ?>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart-3"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg></span> ECG Waveform Components (Click to explore)</div>

                <div style="background:#0D1224;border-radius:12px;margin-bottom:20px;border:1px solid rgba(255,255,255,0.06);overflow:hidden;">
                    <img src="images/ecg_waveform.png" alt="ECG Waveform"
                         style="width:100%;height:auto;display:block;"
                         onerror="this.style.display='none'">
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#F472B6;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">P Wave</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Atrial Depolarization — Atria contract to pump blood into ventricles</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">The P wave represents the electrical impulse starting in the sinus node and spreading through the atria. Abnormalities can indicate atrial enlargement or conduction blocks.</p>
                        </div>
                    </div>
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#60A5FA;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">QRS Complex</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Ventricular Depolarization — Ventricles contract to pump blood out</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">This is the most visually prominent part of the ECG. Its width (duration) is critical—if it's too wide, it could mean a bundle branch block or ventricular rhythm.</p>
                        </div>
                    </div>
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#34D399;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">T Wave</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Ventricular Repolarization — Ventricles relax and recover</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">The T wave represents the recovery phase of the ventricles. Inverted T waves can be a sign of myocardial ischemia or electrolyte imbalances (like potassium levels).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title" style="color:var(--pink-l);"><span>📋</span> Systematic Interpretation (5-Step Method)</div>
                <p class="text-small" style="margin-bottom:15px;">A professional protocol for analyzing any ECG strip (Click to expand):</p>
                
                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--pink);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>1. Rate & Rhythm</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Calculate BPM (300 / large squares between R-waves). Determine if the rhythm is sinus (P-wave before every QRS) or irregular.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--blue);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>2. The Axis</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Determines the overall direction of the heart's electrical vector. Left Axis Deviation (LAD) or Right Axis Deviation (RAD) can hint at underlying structural issues.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--teal);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>3. Hypertrophy</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Assess for chamber enlargement. High R-wave voltage in V5-V6 suggests Left Ventricular Hypertrophy (LVH).</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--orange);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>4. Ischemia & Infarction</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Look for ST-segment elevation (STEMI) or T-wave inversion, indicating oxygen deprivation to the heart muscle.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title" style="color:var(--blue-l);"><span>📡</span> The 12-Lead System Breakdown</div>
                <div style="background:rgba(255,255,255,0.02); padding:20px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong style="color:var(--pink-l);">Limb Leads (I, II, III)</strong>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Standard bipolar leads forming Einthoven's Triangle. They view the heart in the frontal plane.</p>
                            </div>
                        </div>
                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong style="color:var(--blue-l);">Precordial Leads (V1-V6)</strong>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Unipolar leads placed directly on the chest. They provide a horizontal view, slicing the heart from front to back.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span>🏥</span> Educational Uses (Click for details)</div>
                
                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--pink);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Detect Arrhythmias</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Analyze irregular heartbeats like Atrial Fibrillation (AFib), Bradycardia (slow heart), and Tachycardia (fast heart).</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--blue);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Analyze Myocardial Infarction</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Commonly known as a heart attack. ECG is the gold standard for identifying ST-segment elevation (STEMI).</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:var(--teal);"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Monitor Hypertrophy</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Thickening of the heart walls (like Left Ventricular Hypertrophy) causes increased voltage in specific leads like V5 and V6.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="detail-card">
                <div class="panel-title"><span>⚡</span> Recording Parameters</div>
                <div class="param-grid">
                    <div class="param-box"><div class="param-label">Frequency Range</div><div class="param-value">0.5 – 150 Hz</div></div>
                    <div class="param-box"><div class="param-label">Amplitude</div><div class="param-value">0.5 – 4 mV</div></div>
                    <div class="param-box"><div class="param-label">Sample Rate</div><div class="param-value">250 – 500 Hz</div></div>
                    <div class="param-box"><div class="param-label">Duration</div><div class="param-value">10 sec – 24 hr</div></div>
                </div>
            </div>

            <div class="detail-card key-points" style="border-left:4px solid var(--pink); background:rgba(244,114,182,0.03);">
                <div class="panel-title"><span>🔑</span> Key Technical Takeaways</div>
                <div class="key-item"><span class="key-dot" style="color:var(--pink);">•</span> <strong>Standard 12-lead ECG:</strong> Uses 10 electrodes to derive 12 distinct viewpoints of the heart's electrical vector.</div>
                <div class="key-item"><span class="key-dot" style="color:var(--pink);">•</span> <strong>Einthoven's Triangle:</strong> The fundamental concept involving Leads I, II, and III forming a triangle around the heart.</div>
                <div class="key-item"><span class="key-dot" style="color:var(--pink);">•</span> <strong>Wilson Central Terminal:</strong> A theoretical "zero" reference point used for unipolar precordial leads (V1-V6).</div>
                <div class="key-item"><span class="key-dot" style="color:var(--pink);">•</span> Quick, non-invasive, and bedside-ready for immediate diagnostic screening.</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 10px 25px rgba(37,99,235,0.3); text-decoration:none;">
                    Next: <?= $modules[$m['next']]['title'] ?> <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'profile'): // ═══ VIBRANT PROFILE PAGE ═══
        require_once __DIR__ . '/api/db.php';
        $_sdb = getDB();
        $uid = $_SESSION['user_id'];
        
        $trackableModules = ['ecg', 'eeg', 'emg', 'electrode_placement', 'recording_techniques', 'compare', 'pros_cons', 'decision_guide', 'signal_quality', 'bipolar_recording', 'monopolar_recording'];
        $totalMods = count($trackableModules);
        
        // Stats
        $compS = $_sdb->prepare("SELECT COUNT(*) as c FROM user_progress WHERE user_id=? AND completion_percentage=100");
        $compS->bind_param('i', $uid); $compS->execute();
        $compCount = $compS->get_result()->fetch_assoc()['c']; $compS->close();
        
        $avgS = $_sdb->prepare("SELECT SUM(completion_percentage) as s FROM user_progress WHERE user_id=?");
        $avgS->bind_param('i', $uid); $avgS->execute();
        $totalP = $avgS->get_result()->fetch_assoc()['s'] ?? 0; $avgS->close();
        $overallP = $totalMods > 0 ? round($totalP / ($totalMods * 100) * 100) : 0;
        
        // Activity
        $recentAct = [];
        $actS = $_sdb->prepare("SELECT module_name, completion_percentage, last_updated FROM user_progress WHERE user_id=? ORDER BY last_updated DESC LIMIT 3");
        $actS->bind_param('i', $uid); $actS->execute();
        $actRes = $actS->get_result();
        while($ar = $actRes->fetch_assoc()) {
            $mInf = $modules[$ar['module_name']] ?? ['title'=>$ar['module_name']];
            $recentAct[] = [
                't' => ($ar['completion_percentage']==100?'Completed ':'Studied ').$mInf['title'],
                's' => 'Progress: '.$ar['completion_percentage'].'% • '.date('M d, H:i', strtotime($ar['last_updated'])),
                'c' => ($ar['completion_percentage']==100?'dot-green':'dot-blue')
            ];
        }
        $actS->close();
    ?>

        <div class="module-hero" style="background:linear-gradient(135deg, rgba(37,99,235,0.1), rgba(124,58,237,0.1)); border:1px solid rgba(255,255,255,0.05); padding:30px; border-radius:24px;">
            <div class="hero-icon-box" style="background:var(--g-multi); color:#fff; border:none; box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4); font-size:2.5rem;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <div class="hero-text">
                <h2 style="background:var(--g-multi); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2rem; font-weight:800;">User Profile</h2>
                <p style="color:var(--text2); font-weight:500;">Your learning journey and academic statistics</p>
            </div>
        </div>

        <div class="profile-container">
            <!-- Profile Header Card -->
            <div class="profile-header-card">
                <div class="ph-main">
                    <div class="ph-avatar-section">
                        <div class="ph-avatar" style="overflow:hidden;">
                            <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <?= $userInitial ?>
                            <?php endif; ?>
                        </div>
                        <a href="dashboard.php?page=edit_profile" class="ph-edit-icon" title="Edit Photo" style="text-decoration:none;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                    </div>
                    <div class="ph-info">
                        <h3><?= $userName ?></h3>
                        <span class="ph-role"><?= $_SESSION['user_role'] ?? 'Student' ?></span>
                    </div>
                </div>
                
                <div class="ph-stats">
                    <div class="ph-stat-item">
                        <div class="ph-stat-val"><?= $overallP ?>%</div>
                        <div class="ph-stat-label">Progress</div>
                    </div>
                    <div class="ph-stat-divider"></div>
                    <div class="ph-stat-item">
                        <div class="ph-stat-val"><?= $compCount ?></div>
                        <div class="ph-stat-label">Completed</div>
                    </div>
                    <div class="ph-stat-divider"></div>
                    <div class="ph-stat-item">
                        <div class="ph-stat-val">5</div>
                        <div class="ph-stat-label">Days Streak</div>
                    </div>
                </div>
            </div>

            <div class="profile-grid">
                <!-- Column 1: Stats -->
                <div class="profile-column">
                    <div class="section-title-small">Learning Statistics</div>
                    
                    <div class="stat-card" style="background:rgba(37,99,235,0.05); border:1px solid rgba(37,99,235,0.2); box-shadow:0 10px 20px rgba(0,0,0,0.1);">
                        <div class="sc-header">
                            <div class="sc-icon" style="background:var(--g-blue); color:#fff; box-shadow:0 4px 15px rgba(37,99,235,0.3);">🕒</div>
                            <div class="sc-text">
                                <div class="sc-title" style="color:var(--blue-l); font-weight:700;">Study Time</div>
                                <div class="sc-subtitle">Weekly Progress</div>
                            </div>
                            <div class="sc-value" style="color:#fff;"><?= round($overallP * 0.15, 1) ?>h</div>
                        </div>
                        <div class="sc-progress-bg" style="background:rgba(255,255,255,0.05); height:8px;">
                            <div class="sc-progress-fill" style="width: <?= $overallP ?>%; background: var(--g-blue);"></div>
                        </div>
                    </div>

                    <div class="stat-card" style="background:rgba(124,58,237,0.05); border:1px solid rgba(124,58,237,0.2); box-shadow:0 10px 20px rgba(0,0,0,0.1); margin-top:20px;">
                        <div class="sc-header">
                            <div class="sc-icon" style="background:var(--g-purple); color:#fff; box-shadow:0 4px 15px rgba(124,58,237,0.3);">🎯</div>
                            <div class="sc-text">
                                <div class="sc-title" style="color:var(--purple-l); font-weight:700;">Knowledge Accuracy</div>
                                <div class="sc-subtitle">Average Score</div>
                            </div>
                            <div class="sc-value" style="color:#fff;"><?= $overallP > 0 ? 80 + ($overallP * 0.15) : 0 ?>%</div>
                        </div>
                        <div class="sc-progress-bg" style="background:rgba(255,255,255,0.05); height:8px;">
                            <div class="sc-progress-fill" style="width: <?= min(100, 80 + ($overallP * 0.15)) ?>%; background: var(--g-purple);"></div>
                        </div>
                    </div>

                    <div class="section-title-small" style="margin-top:24px;">Achievements</div>
                    <div class="achievements-row" style="flex-wrap:wrap;">
                        <div class="achievement-badge badge-gold" title="First Quiz">🏆<span style="font-size:0.6rem;display:block;margin-top:2px;">First Quiz</span></div>
                        <div class="achievement-badge badge-purple" title="Fast Learner">⚡<span style="font-size:0.6rem;display:block;margin-top:2px;">Fast Learner</span></div>
                        <div class="achievement-badge badge-blue" title="Perfect Score">⭐<span style="font-size:0.6rem;display:block;margin-top:2px;">Perfect Score</span></div>
                        <div class="achievement-badge badge-locked" title="100% Complete">🔒<span style="font-size:0.6rem;display:block;margin-top:2px;">100%</span></div>
                        <div class="achievement-badge badge-locked" title="30 Day Streak">🔒<span style="font-size:0.6rem;display:block;margin-top:2px;">30 Day</span></div>
                        <div class="achievement-badge badge-locked" title="Expert Level">🔒<span style="font-size:0.6rem;display:block;margin-top:2px;">Expert</span></div>
                    </div>
                </div>

                <!-- Column 2: Activity & Goals -->
                <div class="profile-column">
                    <div class="section-title-small">Recent Activity</div>
                    <div class="activity-list">
                        <?php if(!empty($recentAct)): ?>
                            <?php foreach($recentAct as $act): ?>
                            <div class="activity-item">
                                <div class="activity-dot <?= $act['c'] ?>"></div>
                                <div class="activity-text">
                                    <div class="at-title"><?= $act['t'] ?></div>
                                    <div class="at-subtitle" style="font-size:0.75rem;color:var(--text3);"><?= $act['s'] ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align:center; padding:30px; color:var(--text3); font-size:0.8rem;">
                                No recent activity. Start learning!
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="goals-card">
                        <div class="gc-title" style="color:#fff;">Weekly Goals</div>
                        <div class="gc-item">
                            <span>Complete 4 modules</span>
                            <span class="gc-status">4/4 <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check" style="vertical-align:middle;"><polyline points="20 6 9 17 4 12"/></svg></span>
                        </div>
                        <div class="gc-item">
                            <span>Study 12 hours</span>
                            <span class="gc-status">12.5/12 <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#4ADE80" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check" style="vertical-align:middle;"><polyline points="20 6 9 17 4 12"/></svg></span>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="dashboard.php?page=edit_profile" class="btn btn-primary" style="width:100%; margin-bottom:12px;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit Profile</a>
                        <a href="api/logout_api.php" class="btn btn-outline-danger" style="width:100%; border:1px solid #EF4444; color:#EF4444; background:transparent;">Logout</a>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($page === 'eeg'): // ═══ EEG DETAIL ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px;">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(124, 58, 237, 0.2); border-color:#7C3AED;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
            <div class="hero-text">
                <h2>EEG</h2>
                <p style="color:#C4B5FD;">Electroencephalography — Brain wave patterns</p>
            </div>
        </div>

        <div style="max-width:860px;">
            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg></span> <?= htmlspecialchars($dynContent['intro']['title'] ?? 'What is EEG?') ?></div>
                <div class="text-small">
                    <?= nl2br(htmlspecialchars($dynContent['intro']['content'] ?? "Electroencephalography (EEG) records electrical activity of the brain through electrodes placed on the scalp. It monitors synchronized neuronal firing patterns, providing a window into brain function and neurological disorders.")) ?>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-waves"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/></svg></span> Brain Wave Types</div>
                
                <div style="background:#0D1224;border-radius:12px;margin-bottom:24px;border:1px solid rgba(124,58,237,0.3);overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.5);">
                    <img src="images/eeg_waveform.png" alt="EEG Waveform Visualization"
                         style="width:100%;height:auto;display:block;"
                         onerror="this.style.display='none'">
                </div>
                <div class="wave-item"><div class="wave-dot" style="background:#3B82F6;"></div><div><div class="wave-title">Delta (δ) [0.5 - 4 Hz]</div><div class="wave-desc">Deep sleep, unconscious states</div></div></div>
                <div class="wave-item"><div class="wave-dot" style="background:#10B981;"></div><div><div class="wave-title">Theta (θ) [4 - 8 Hz]</div><div class="wave-desc">Drowsiness, meditation, creativity</div></div></div>
                <div class="wave-item"><div class="wave-dot" style="background:#F59E0B;"></div><div><div class="wave-title">Alpha (α) [8 - 13 Hz]</div><div class="wave-desc">Relaxed, calm wakefulness</div></div></div>
                <div class="wave-item"><div class="wave-dot" style="background:#EF4444;"></div><div><div class="wave-title">Beta (β) [13 - 30 Hz]</div><div class="wave-desc">Active thinking, concentration, focus</div></div></div>
                <div class="wave-item"><div class="wave-dot" style="background:#8B5CF6;"></div><div><div class="wave-title">Gamma (γ) [30 - 100 Hz]</div><div class="wave-desc">Perception, 30Hz binding, problem solving</div></div></div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span>🏥</span> Educational Uses</div>
                <div class="educational-item"><div class="educational-dot"></div>Epilepsy analysis & seizure detection</div>
                <div class="educational-item"><div class="educational-dot"></div>Sleep disorders research</div>
                <div class="educational-item"><div class="educational-dot"></div>Brain injury assessment</div>
                <div class="educational-item"><div class="educational-dot"></div>Neurofeedback (BCI neurofeedback)</div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span>⚡</span> Recording Parameters</div>
                <div class="param-grid">
                    <div class="param-box"><div class="param-label">Frequency Range</div><div class="param-value">0.5 - 100 Hz</div></div>
                    <div class="param-box"><div class="param-label">Amplitude</div><div class="param-value">10 - 100 μV</div></div>
                    <div class="param-box"><div class="param-label">Sample Rate</div><div class="param-value">256 - 512 Hz</div></div>
                    <div class="param-box"><div class="param-label">Electrode System</div><div class="param-value">10-20 Standard</div></div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title" style="color:#C4B5FD;"><span>🧠</span> Clinical Rhythms & Meaning (Click to explore)</div>
                
                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#7C3AED;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Mu (μ) Rhythm [8-12 Hz]</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p>Occurs over the motor cortex. It suppression (desynchronization) indicates movement or even the intent to move. In neurofeedback, mastering Mu suppression is key for BCI control.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#7C3AED;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Sleep Spindles [12-14 Hz]</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p>Bursts of brain activity during Stage 2 NREM sleep, essential for memory consolidation. They are thought to protect the brain from being woken up by external noise.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#7C3AED;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>K-Complexes</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p>Large, high-amplitude waves during sleep that respond to external stimuli while sleeping. They represent a dual role: protecting sleep and brief arousal to evaluate the environment.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card" style="border-left:4px solid #F87171;">
                <div class="panel-title" style="color:#F87171;"><span>⚠️</span> Common EEG Artifacts</div>
                <p class="text-small" style="margin-bottom:15px;">EEG signals are extremely low amplitude (μV), making them highly susceptible to non-neural interference (Click to learn more):</p>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="param-box expand-card" onclick="this.classList.toggle('active')" style="background:rgba(239, 68, 68, 0.05); height:fit-content;">
                        <div class="param-label" style="display:flex; justify-content:space-between;">EOG (Ocular) <span class="expand-icon">▼</span></div>
                        <div class="param-value" style="font-size:0.85rem;">Eye blinks create massive voltage spikes.</div>
                        <div class="expand-detail" style="font-size:0.75rem; color: #fca5a5;">
                            Blinks act like dipole shifts. Pro-tip: Monitor Fp1/Fp2 to identify ocular artifacts vs. frontal neural events.
                        </div>
                    </div>
                    <div class="param-box expand-card" onclick="this.classList.toggle('active')" style="background:rgba(239, 68, 68, 0.05); height:fit-content;">
                        <div class="param-label" style="display:flex; justify-content:space-between;">EMG (Muscle) <span class="expand-icon">▼</span></div>
                        <div class="param-value" style="font-size:0.85rem;">Jaw clenching creates high-frequency noise.</div>
                        <div class="expand-detail" style="font-size:0.75rem; color: #fca5a5;">
                            Filtering > 30Hz often removes EMG but can also clip Gamma waves. Relaxation techniques are preferred.
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title" style="color:#60A5FA;"><span>📍</span> Anatomical Mapping (10-20 System)</div>
                <p class="text-small">Click any region to see its neuro-educational importance:</p>
                <div style="background:rgba(255,255,255,0.02); padding:20px; border-radius:12px; border:1px solid rgba(255,255,255,0.05);">
                    <div style="display:flex; flex-direction:column; gap:12px;">
                        
                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--purple-l); font-weight:700;">F (Frontal Region)</span>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Focus: Higher cognitive function, planning, personality, and voluntary movement. Electrodes F3/F4 are key for cognitive load analysis.</p>
                            </div>
                        </div>

                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--blue-l); font-weight:700;">C (Central Region)</span>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Focus: Primary motor and sensory processing. Cz is the most common reference or vertex point for educational montages.</p>
                            </div>
                        </div>

                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--teal-l); font-weight:700;">T (Temporal Region)</span>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Focus: Auditory processing, language comprehension (Wernicke's area), and memory formation. Sensitive to jaw artifacts.</p>
                            </div>
                        </div>

                        <div class="expand-card" onclick="this.classList.toggle('active')" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="color:var(--pink-l); font-weight:700;">O (Occipital Region)</span>
                                <span class="expand-icon">▼</span>
                            </div>
                            <div class="expand-detail">
                                <p style="color:var(--text3); font-size:0.85rem;">Focus: Visual processing center. Electrodes O1/O2 show the cleanest Alpha waves when a subject's eyes are closed.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="detail-card key-points" style="border-left:4px solid #7C3AED; background:rgba(124, 58, 237, 0.03);">
                <div class="panel-title"><span>🔑</span> Advanced Analysis & Key Points</div>
                <div class="key-item"><span class="key-dot">•</span> <strong>Fourier Transform (FFT):</strong> The primary mathematical tool used to convert raw EEG (time domain) into frequency bands (Delta, Alpha, etc.).</div>
                <div class="key-item"><span class="key-dot">•</span> <strong>Evoked Potentials (EP):</strong> Brain responses recorded in relation to a specific stimulus (Flash of light, Click sound).</div>
                <div class="key-item"><span class="key-dot">•</span> <strong>Impedance Gold Standard:</strong> Always strive for &lt; 5kΩ per electrode to minimize thermal noise floor.</div>
                <div class="key-item"><span class="key-dot">•</span> EEG provides unmatched temporal resolution (milliseconds) but low spatial resolution compared to fMRI.</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 10px 25px rgba(37,99,235,0.3); text-decoration:none;">
                    Next: <?= $modules[$m['next']]['title'] ?> <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'emg'): // ═══ EMG DETAIL ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px;">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(20,184,166,0.2); border-color:#14B8A6;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dumbbell"><path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.767-2.828-2.828 1.767-1.767a2 2 0 1 1-2.828-2.829l1.767-1.767L9.11 8.405l-1.767 1.767a2 2 0 1 1-2.829-2.828l1.767-1.767-2.828-2.828 1.767-1.768a2 2 0 1 1-2.828-2.828l2.828 2.828 1.768-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.828 2.829l1.767-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.829 2.828l-1.768-1.767-2.828-2.828 1.767-1.767a2 2 0 1 1 2.828 2.829l-1.767 1.767 2.828 2.828-1.767 1.767Z"/></svg></div>
            <div class="hero-text">
                <h2>EMG</h2>
                <p style="color:#2DD4BF;">Electromyography — Muscle electrical signals</p>
            </div>
        </div>

        <div style="max-width:860px;">
            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-lightbulb"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A5 5 0 0 0 8 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg></span> <?= htmlspecialchars($dynContent['intro']['title'] ?? 'What is EMG?') ?></div>
                <div class="text-small">
                    <?= nl2br(htmlspecialchars($dynContent['intro']['content'] ?? "Electromyography (EMG) measures the electrical activity produced by skeletal muscles. When muscles are active, they produce electrical signals that are proportional to the level of muscle contraction. EMG is essential for diagnosing neuromuscular disorders and assessing muscle function.")) ?>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span> EMG Signal Patterns (Click to explore)</div>
                
                <div style="background:#0D1224;border-radius:12px;margin-bottom:24px;border:1px solid rgba(20,184,166,0.3);overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.5);">
                    <img src="images/emg_signal.png" alt="EMG Signal Visualization"
                         style="width:100%;height:auto;display:block;"
                         onerror="this.style.display='none'">
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#2DD4BF;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">At Rest</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Minimal electrical activity — Baseline noise only</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Healthy muscles at rest should show almost no electrical activity. Spontaneous activity during rest (like fibrillations) can be a sign of nerve damage or muscle disease.</p>
                        </div>
                    </div>
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#10B981;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">Light Contraction</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Few motor units firing — Low amplitude spikes</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">As the muscle begins to contract, individual Motor Unit Action Potentials (MUAPs) can be identified. This is where clinicians analyze the shape and duration of individual pulses.</p>
                        </div>
                    </div>
                </div>

                <div class="wave-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="wave-dot" style="background:#EF4444;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div class="wave-title">Maximum Contraction</div>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="wave-desc">Dense interference pattern — Full recruitment</div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">During full effort, so many motor units fire that the individual pulses blur into a "Full Interference Pattern." The amplitude here is proportional to the total muscle strength output.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span>🏥</span> Educational Uses & Applications (Click for details)</div>
                
                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#14B8A6;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Neuromuscular Diagnosis</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Essential for identifying conditions like Carpal Tunnel Syndrome, ALS (Lou Gehrig's disease), and Muscular Dystrophy.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#14B8A6;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Prosthetics Control</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Modern bionic limbs use surface EMG (sEMG) to detect a user's intent to move their hand or arm, allowing for intuitive control.</p>
                        </div>
                    </div>
                </div>

                <div class="educational-item expand-card" onclick="this.classList.toggle('active')">
                    <div class="educational-dot" style="background:#14B8A6;"></div>
                    <div style="flex:1;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <strong>Sports Science</strong>
                            <span class="expand-icon">▼</span>
                        </div>
                        <div class="expand-detail">
                            <p style="font-size:0.85rem; color:var(--text3);">Athletes use EMG to analyze muscle fatigue and ensure they are recruiting the correct muscles during complex movements like squats or sprints.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="detail-card">
                <div class="panel-title"><span>⚡</span> Recording Parameters</div>
                <div class="param-grid">
                    <div class="param-box"><div class="param-label">Frequency Range</div><div class="param-value">10 - 500 Hz</div></div>
                    <div class="param-box"><div class="param-label">Amplitude</div><div class="param-value">50 μV - 5 mV</div></div>
                    <div class="param-box"><div class="param-label">Sample Rate</div><div class="param-value">2 - 4 kHz</div></div>
                    <div class="param-box"><div class="param-label">Electrode Type</div><div class="param-value">Surface / Needle</div></div>
                </div>
            </div>

            <div class="detail-card key-points" style="border-left:4px solid #14B8A6; background:rgba(20,184,166,0.05);">
                <div class="panel-title"><span>🔑</span> Key Technical Points</div>
                <div class="key-item"><span class="key-dot" style="color:#14B8A6;">•</span> <strong>sEMG vs. iEMG:</strong> Surface EMG is for global analysis, while Intramuscular (Needle) EMG is for deep, specific motor unit study.</div>
                <div class="key-item"><span class="key-dot" style="color:#14B8A6;">•</span> <strong>Common Mode Rejection:</strong> Bipolar setups are mandatory to cancel the noise from 60Hz power lines and nearby muscles (crosstalk).</div>
                <div class="key-item"><span class="key-dot" style="color:#14B8A6;">•</span> <strong>RMS Calculation:</strong> Root Mean Square is the standard mathematical way to quantify the "power" or intensity of an EMG signal.</div>
                <div class="key-item"><span class="key-dot" style="color:#14B8A6;">•</span> High-fidelity recording requires skin impedance to be reduced through thorough cleaning.</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 10px 25px rgba(37,99,235,0.3); text-decoration:none; background:var(--g-teal);">
                    Next: <?= $modules[$m['next']]['title'] ?> <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'electrode_placement'): // ═══ ELECTRODE PLACEMENT GUIDE ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(20,184,166,0.1), rgba(37,99,235,0.1)); border:1px solid rgba(20,184,166,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(20,184,166,0.2); border-color:#14B8A6;">📍</div>
            <div class="hero-text">
                <h2>Electrode Placement Guide</h2>
                <p style="color:#C4B5FD;">Standardized protocols for signal acquisition</p>
            </div>
        </div>

        <div style="max-width:900px;">
            <div class="detail-card">
                <div class="panel-title"><span>📍</span> <?= htmlspecialchars($dynContent['intro']['title'] ?? 'Electrode Placement Guide') ?></div>
                <div class="text-small">
                    <?= nl2br(htmlspecialchars($dynContent['intro']['content'] ?? "Standardized protocols for signal acquisition ensure consistency and reliability in biomedical signal recording. Proper placement is the foundation of accurate data collection.")) ?>
                </div>
            </div>

            <div class="detail-card">
                <div class="panel-title"><span>🎨</span> Select Recording Area</div>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px; margin-top:15px;">
                    <button class="btn btn-outline tabs-btn active" onclick="showPlacement('ecg-p')">❤️ ECG</button>
                    <button class="btn btn-outline tabs-btn" onclick="showPlacement('eeg-p')">🧠 EEG</button>
                    <button class="btn btn-outline tabs-btn" onclick="showPlacement('emg-p')">💪 EMG</button>
                </div>
            </div>

            <!-- ECG PLACEMENT -->
            <div id="ecg-p" class="placement-tab">
                <div class="detail-card">
                    <div class="panel-title"><span>🫀</span> Standard 12-Lead ECG (Limb & Precordial)</div>
                    
                    <div style="background:#0D1224; border-radius:15px; margin:20px 0; border:1px solid rgba(229,57,53,0.3); overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,0.6);">
                        <img src="images/electrode_placement.png" alt="ECG Placement Diagram" style="width:100%; height:auto; display:block;">
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:20px;">
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>RA / LA:</strong> Right & Left Arms</div>
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>RL / LL:</strong> Right (Ground) & Left Legs</div>
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>V1:</strong> 4th ICS, Right sternal border</div>
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>V2:</strong> 4th ICS, Left sternal border</div>
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>V3:</strong> Midway between V2 and V4</div>
                        <div class="educational-item" style="background:rgba(255,255,255,0.03); padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.05);"><strong>V4:</strong> 5th ICS, Midclavicular line</div>
                    </div>
                </div>
            </div>

            <!-- EEG PLACEMENT -->
            <div id="eeg-p" class="placement-tab" style="display:none;">
                <div class="detail-card">
                    <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-activity"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></span> International 10-20 System</div>
                    
                    <div style="background:#0D1224; border-radius:15px; margin:20px 0; border:1px solid rgba(123,47,254,0.3); overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,0.6);">
                        <img src="images/eeg_anatomy.png" alt="EEG 10-20 System Diagram" style="width:100%; height:auto; display:block;">
                    </div>

                    <div style="background:rgba(37,99,235,0.05); padding:20px; border-radius:15px; border:1px solid rgba(37,99,235,0.1); margin-bottom:20px;">
                        <p class="text-small">The 10-20 system ensures standardized electrode placement by using anatomical landmarks: Nasion (bridge of nose) and Inion (bump at back of skull).</p>
                    </div>
                    <div class="educational-item"><strong>Fp:</strong> Frontal Pole (Forehead)</div>
                    <div class="educational-item"><strong>F / C / P / O:</strong> Frontal, Central, Parietal, Occipital</div>
                    <div class="educational-item"><strong>T:</strong> Temporal (Auditoy/Language)</div>
                    <div class="educational-item" style="color:var(--purple-l);"><strong>Z:</strong> Midline electrodes (Fz, Cz, Pz)</div>
                </div>
            </div>

            <!-- EMG PLACEMENT -->
            <div id="emg-p" class="placement-tab" style="display:none;">
                <div class="detail-card">
                    <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dumbbell"><path d="M14.4 14.4 9.6 9.6"/><path d="M18.657 21.485a2 2 0 1 1-2.829-2.828l-1.767 1.767-2.828-2.828 1.767-1.767a2 2 0 1 1-2.828-2.829l1.767-1.767L9.11 8.405l-1.767 1.767a2 2 0 1 1-2.829-2.828l1.767-1.767-2.828-2.828 1.767-1.768a2 2 0 1 1-2.828-2.828l2.828 2.828 1.768-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.828 2.829l1.767-1.767 2.828 2.828-1.767 1.767a2 2 0 1 1 2.829 2.828l-1.768-1.767-2.828-2.828 1.767-1.767a2 2 0 1 1 2.828 2.829l-1.767 1.767 2.828 2.828-1.767 1.767Z"/></svg></span> Surface EMG (sEMG) Configuration</div>
                    
                    <div style="background:#0D1224; border-radius:15px; margin:20px 0; border:1px solid rgba(20,184,166,0.3); overflow:hidden; box-shadow:0 15px 40px rgba(0,0,0,0.6);">
                        <img src="images/bipolar_monopolar.png" alt="EMG Bipolar Recording Diagram" style="width:100%; height:auto; display:block;">
                    </div>

                    <div class="educational-item"><strong>Muscle Belly:</strong> Place active electrodes over the meat of the muscle.</div>
                    <div class="educational-item"><strong>Alignment:</strong> Electrodes should be parallel to muscle fiber direction.</div>
                    <div class="educational-item"><strong>Ground:</strong> Always place over a bony prominence (ankles, elbows).</div>
                </div>
            </div>

            <div class="detail-card key-points" style="background:rgba(255,158,11,0.05); border-left:4px solid var(--orange);">
                <div class="panel-title" style="color:var(--orange-l);"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-flashlight"><path d="M18 6h.01"/><path d="M7 21l-4-4"/><path d="M15 13l4-4"/><path d="M9 7l1.5-1.5"/><path d="M11 11l1.5-1.5"/><path d="M13 15l1.5-1.5"/><path d="M15 19l1.5-1.5"/><path d="M17 17l1.5-1.5"/><path d="M19 15l1.5-1.5"/><path d="m21 9-9-9-9 9 9 9 9-9z"/></svg></span> Pro Tips for Quality Signals</div>
                <div class="key-item" style="color:#FFCDD2;"><span class="key-dot" style="color:var(--orange);">•</span> Clean skin with 70% Alcohol to remove oils and dead skin.</div>
                <div class="key-item" style="color:#FFCDD2;"><span class="key-dot" style="color:var(--orange);">•</span> Light abrasion (if necessary) to lower skin-electrode impedance.</div>
                <div class="key-item" style="color:#FFCDD2;"><span class="key-dot" style="color:var(--orange);">•</span> Ensure conductive gel is fresh (for wet electrodes).</div>
            </div>

            <div class="detail-card check-list" style="background:rgba(16,185,129,0.05); border-left:4px solid #10B981;">
                <div class="panel-title" style="color:#10B981;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-check-circle-2"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg></span> Pre-Recording Checklist</div>
                <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div>Skin properly cleaned and prepared?</div>
                <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div>Electrodes in correct anatomical position?</div>
                <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div>Impedance checked and within acceptable range (< 5kΩ)?</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 10px 25px rgba(37,99,235,0.3); text-decoration:none; background:var(--g-purple);">
                    Next: Recording Techniques <span>→</span>
                </a>
            </div>
        </div>

        <script>
            function showPlacement(tabId) {
                document.querySelectorAll('.placement-tab').forEach(t => t.style.display = 'none');
                document.querySelectorAll('.tabs-btn').forEach(b => b.classList.remove('active'));
                document.getElementById(tabId).style.display = 'block';
                event.currentTarget.classList.add('active');
            }
        </script>
        <style>
            .tabs-btn { border: 1px solid rgba(255,255,255,0.1); color: var(--text2); background: transparent; transition: 0.3s; }
            .tabs-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); box-shadow: 0 4px 15px rgba(37,99,235,0.2); }
            .tabs-btn:hover:not(.active) { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); }
        </style>

    <?php elseif ($page === 'recording_techniques'): // ═══ RECORDING TECHNIQUES (MONOPOLAR vs BIPOLAR) ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(37,99,235,0.1), rgba(124,58,237,0.1)); border:1px solid rgba(37,99,235,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(37,99,235,0.2); border-color:#2563EB;"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cpu"><rect width="16" height="16" x="4" y="4" rx="2"/><rect width="6" height="6" x="9" y="9" rx="1"/><path d="M15 2v2"/><path d="M15 20v2"/><path d="M2 15h2"/><path d="M2 9h2"/><path d="M20 15h2"/><path d="M20 9h2"/><path d="M9 2v2"/><path d="M9 20v2"/></svg></div>
            <div class="hero-text">
                <h2>Recording Techniques</h2>
                <p style="color:#93C5FD;">Bipolar vs Monopolar Electrode Analysis</p>
            </div>
        </div>

        <div style="max-width:960px;">
            <!-- Comparison Visualization -->
            <div class="detail-card">
                <div class="panel-title"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bar-chart-3"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg></span> Technical Comparison Diagram</div>
                <div style="background:#0D1224; border-radius:20px; margin:20px 0; border:1px solid rgba(37,99,235,0.3); overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.7);">
                    <img src="images/bipolar_monopolar.png" alt="Bipolar vs Monopolar Diagram" style="width:100%; height:auto; display:block;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-top:20px;">
                <!-- Bipolar Section -->
                <div class="detail-card" style="border-top:4px solid #3B82F6;">
                    <div class="panel-title" style="color:#60A5FA;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-dna"><path d="m8 8 8 8"/><path d="m8 16 8-8"/><path d="m13 3 3 3"/><path d="m9 19 3 3"/><path d="m18 8 3 3"/><path d="m2 13 3 3"/><path d="m21 13-3 3"/><path d="m5 8-3 3"/><path d="m13 21 3-3"/><path d="m9 5 3-3"/></svg></span> Bipolar Recording</div>
                    <p class="text-small" style="margin-bottom:15px;">Measures the difference between two active electrodes placed close together above the signal source.</p>
                    
                    <div class="check-list">
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Noise Rejection:</strong> Superior CMRR (Common Mode Rejection Ratio).</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Resolution:</strong> High spatial resolution for localizing activity.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#EF4444;"></div><strong>Complexity:</strong> Requires precise placement.</div>
                    </div>
                </div>

                <!-- Monopolar Section -->
                <div class="detail-card" style="border-top:4px solid #14B8A6;">
                    <div class="panel-title" style="color:#2DD4BF;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-radio"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9"/><path d="M7.8 16.2c-2.3-2.3-2.3-6.1 0-8.5"/><circle cx="12" cy="12" r="2"/><path d="M16.2 7.8c2.3 2.3 2.3 6.1 0 8.5"/><path d="M19.1 4.9C23 8.8 23 15.2 19.1 19.1"/></svg></span> Monopolar Recording</div>
                    <p class="text-small" style="margin-bottom:15px;">Measures signal from one active electrode against a distant, neutral reference electrode.</p>
                    
                    <div class="check-list">
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Amplitude:</strong> Higher signal amplitude (absolute potential).</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Setup:</strong> Simple configuration, faster application.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#EF4444;"></div><strong>Interference:</strong> Sensitive to ambient noise and artifacts.</div>
                    </div>
                </div>
            </div>

            <!-- Decision Guide -->
            <div class="detail-card key-points" style="background:linear-gradient(to right, rgba(37,99,235,0.1), rgba(124,58,237,0.1)); border-left:4px solid #7C3AED;">
                <div class="panel-title" style="color:#C4B5FD;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-compass"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg></span> Decision Guide</div>
                <div class="key-item"><strong>Use BIPOLAR when:</strong> Precision and noise rejection are critical (e.g., individual muscle unit EMG or focal seizure EEG).</div>
                <div class="key-item"><strong>Use MONOPOLAR when:</strong> A broad field of view or absolute signal amplitude is needed (e.g., standard educational EEG montage).</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 20px 40px rgba(124,58,237,0.3); text-decoration:none; background:linear-gradient(135deg, #7C3AED, #2563EB);">
                    Next: Bipolar Recording <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'bipolar_recording'): // ═══ BIPOLAR RECORDING DETAIL ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(37,99,235,0.1), rgba(124,58,237,0.1)); border:1px solid rgba(37,99,235,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(37,99,235,0.2); border-color:#2563EB;">🧬</div>
            <div class="hero-text">
                <h2>Bipolar Recording</h2>
                <p style="color:#93C5FD;">Deep dive into Differential Electrode Configuration</p>
            </div>
        </div>

        <div style="max-width:960px;">
            <!-- Advantages Section -->
            <div class="detail-card" style="border-left:4px solid #10B981;">
                <div class="panel-title" style="color:#10B981;"><span>✅</span> Educational Advantages</div>
                <div class="check-list">
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#10B981;"></div>
                        <strong>Superior Noise Rejection:</strong> Common-mode rejection eliminates ambient electrical noise.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#10B981;"></div>
                        <strong>High Spatial Resolution:</strong> Precise activity localization directly between the two electrodes.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#10B981;"></div>
                        <strong>Cleaner Waveforms:</strong> Minimal baseline drift and reduction in motion artifacts.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#10B981;"></div>
                        <strong>High Analytical Accuracy:</strong> ~94% accuracy rate for precise signal localization.
                    </div>
                </div>
            </div>

            <!-- Disadvantages Section -->
            <div class="detail-card" style="border-left:4px solid #EF4444; background:rgba(239, 68, 68, 0.03);">
                <div class="panel-title" style="color:#F87171;"><span>❌</span> Technical Limitations</div>
                <div class="check-list">
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#EF4444;"></div>
                        <strong>Complex Setup:</strong> Requires extremely precise electrode positioning and inter-electrode spacing.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#EF4444;"></div>
                        <strong>Lower Signal Amplitude:</strong> Since only the difference is recorded, the resultant signal is smaller than monopolar.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#EF4444;"></div>
                        <strong>Higher Cost:</strong> More expensive equipment and specialized sensors are required.
                    </div>
                </div>
            </div>

            <!-- Educational Visualizations -->
            <div class="detail-card" style="border:none; background:transparent; padding:0;">
                <div class="panel-title" style="color:var(--blue-l); margin-bottom:20px;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg></span> Educational Visualizations</div>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:30px;">
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden; transition:0.3s;" class="image-zoom">
                        <img src="images/img_bipolar_placement.png" alt="Bipolar Placement" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Bipolar Placement</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden; transition:0.3s;" class="image-zoom">
                        <img src="images/bipolar_monopolar.png" alt="Comparison" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Comparison View</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden; transition:0.3s;" class="image-zoom">
                        <img src="images/img_monopolar_placement.png" alt="Monopolar Placement" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Monopolar Reference</div>
                    </div>
                </div>
            </div>

            <!-- AI Insight for Bipolar -->
            <div class="detail-card key-points" style="background:rgba(124,58,237,0.05); border:1px solid rgba(124,58,237,0.2);">
                <div class="panel-title" style="color:#A78BFA;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-brain"><path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 4.44-2.54Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-4.44-2.54Z"/></svg></span> AI Educational Recommendation</div>
                <div class="key-item"><strong>Recommendation:</strong> Use Bipolar configurations in high-EMI environments like Operating Rooms (OR) or ICUs where noise rejection is the top priority.</div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 20px 40px rgba(37,99,235,0.3); text-decoration:none; background:var(--g-purple);">
                    Next: Monopolar Recording <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'monopolar_recording'): // ═══ MONOPOLAR RECORDING DETAIL ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Topics</a>

        <div class="module-hero" style="margin-top:12px; border-radius:24px; background:linear-gradient(135deg, rgba(8, 145, 178, 0.1), rgba(37, 99, 235, 0.1)); border:1px solid rgba(8, 145, 178, 0.2);">
            <div class="hero-icon-box" style="background:rgba(8, 145, 178, 0.2); border-color:#0891B2;">📡</div>
            <div class="hero-text">
                <h2>Monopolar Recording</h2>
                <p style="color:#A5F3FC;">Absolute Potential & Referential Configuration</p>
            </div>
        </div>

        <div style="max-width:960px;">
            <div class="detail-card" style="border-left:4px solid #0891B2;">
                <div class="panel-title" style="color:#22D3EE;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span> What is Monopolar Recording?</div>
                <p style="color:#94A3B8; line-height:1.6; margin-bottom:20px;">
                    Monopolar electrode recording measures the <strong>absolute electrical potential</strong> at a single active electrode relative to a distant, inactive reference electrode.
                </p>
                <div style="background:rgba(8, 145, 178, 0.1); border:1px solid rgba(8, 145, 178, 0.2); border-radius:16px; padding:20px;">
                    <strong style="color:#fff; display:block; margin-bottom:10px;">⚡ Key Concept:</strong>
                    <p style="color:#A5F3FC; margin:0; font-family:'JetBrains Mono', monospace;">The recorded signal = V_active - V_reference</p>
                    <small style="color:#67E8F9; display:block; margin-top:8px;">where V_reference is ideally at zero potential or constant (electrically neutral).</small>
                </div>
            </div>

            <div class="detail-card" style="border-left:4px solid #F59E0B;">
                <div class="panel-title" style="color:#FBBF24;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings-2"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg></span> Key Characteristics</div>
                <div class="check-list">
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#FBBF24;"></div>
                        One active electrode placed near the signal source.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#FBBF24;"></div>
                        Distant reference electrode in an electrically neutral location.
                    </div>
                    <div class="educational-item">
                        <div class="educational-dot" style="background:#FBBF24;"></div>
                        Broader spatial coverage capturing signals from a larger tissue volume.
                    </div>
                </div>
            </div>

            <!-- Educational Visualizations -->
            <div class="detail-card" style="border:none; background:transparent; padding:0;">
                <div class="panel-title" style="color:var(--blue-l); margin-bottom:20px;"><span><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg></span> Educational Visualizations</div>
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:30px;">
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;" class="image-zoom">
                        <img src="images/img_monopolar_placement.png" alt="Monopolar Placement" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Ref. Placement</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;" class="image-zoom">
                        <img src="images/bipolar_monopolar.png" alt="Comparison View" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Comparison View</div>
                    </div>
                    <div style="background:rgba(255,255,255,0.03); border-radius:20px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;" class="image-zoom">
                        <img src="images/eeg_waveform.png" alt="Signal Analysis" style="width:100%; height:180px; object-fit:cover;">
                        <div style="padding:12px; font-size:0.8rem; text-align:center; color:#94A3B8;">Signal Analysis</div>
                    </div>
                </div>
            </div>

            <!-- Next Button Section -->
            <div style="margin-top:40px; border-top:1px solid rgba(255,255,255,0.1); padding-top:30px; display:flex; justify-content:flex-end;">
                <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; display:flex; align-items:center; gap:12px; box-shadow:0 20px 40px rgba(37,99,235,0.3); text-decoration:none; background:var(--g-blue);">
                    Proceed to Quiz <span>→</span>
                </a>
            </div>
        </div>

    <?php elseif ($page === 'edit_profile'): // ═══ EDIT PROFILE (VIBRANT WEB MODEL) ═══ ?>
    
        <a href="dashboard.php?page=profile" class="back-btn mb-16" style="display:inline-flex;align-items:center;gap:12px; transition:0.3s; color:var(--blue-l);">
            <div style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:rgba(37,99,235,0.2);border:1px solid rgba(37,99,235,0.3);border-radius:10px;">←</div>
            <span style="font-weight:600; font-size:1.1rem;">Back to Profile</span>
        </a>

        <div style="max-width:1100px; margin:0 auto; perspective:1200px;">
            <!-- Website style layout: Ultra Vibrant Colorful Panel -->
            <div class="glass-panel" style="padding:60px; border-radius:40px; margin-bottom:40px; border:1px solid rgba(255,255,255,0.2); position:relative; overflow:hidden; background:linear-gradient(135deg, rgba(13,18,36,0.95), rgba(21,30,53,0.95)); box-shadow:0 25px 50px rgba(0,0,0,0.5), 0 0 60px rgba(37,99,235,0.15);">
                
                <!-- Animated background gradients -->
                <div style="position:absolute; top:-150px; left:-150px; width:450px; height:450px; background:radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%); pointer-events:none; filter:blur(40px);"></div>
                <div style="position:absolute; bottom:-150px; right:-150px; width:450px; height:450px; background:radial-gradient(circle, rgba(219,39,119,0.15) 0%, transparent 70%); pointer-events:none; filter:blur(40px);"></div>
                <div style="position:absolute; top:20%; right:5%; width:300px; height:300px; background:radial-gradient(circle, rgba(5,150,105,0.1) 0%, transparent 70%); pointer-events:none; filter:blur(40px);"></div>

                <div style="display:flex; gap:50px; align-items:flex-start; flex-wrap:wrap; position:relative; z-index:1;">
                    
                    <!-- Form Column -->
                    <div style="flex:1; min-width:300px;">
                        <div style="display:flex; align-items:center; gap:16px; margin-bottom:12px;">
                            <div style="padding:8px; background:var(--g-multi); border-radius:12px; font-size:20px; line-height:1;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-settings"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.1a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg></div>
                            <h2 style="margin:0; background:var(--g-multi); -webkit-background-clip:text; -webkit-text-fill-color:transparent; font-size:2.5rem; font-weight:800; font-family:'Space Grotesk', sans-serif;">Account Settings</h2>
                        </div>
                        <p style="color:var(--text2); margin-bottom:40px; font-size:1.1rem;">Manage your digital identity and core preferences.</p>
                        
                        <form action="api/update_profile_api.php" method="POST" enctype="multipart/form-data" id="profileForm">
                            
                            <!-- Profile Photo section with Glowing Border -->
                            <div style="background:rgba(255,255,255,0.02); padding:30px; border-radius:24px; margin-bottom:40px; display:flex; align-items:center; gap:30px; border:1px solid rgba(255,255,255,0.06); box-shadow:inset 0 0 20px rgba(255,255,255,0.02);">
                                <div style="position:relative;">
                                    <div id="imagePreview" style="width:120px; height:120px; border-radius:50%; border:4px solid transparent; background:linear-gradient(var(--bg2), var(--bg2)) padding-box, var(--g-multi) border-box; overflow:hidden; display:flex; align-items:center; justify-content:center; font-size:40px; font-weight:bold; color:#fff; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
                                        <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <?= $userInitial ?>
                                        <?php endif; ?>
                                    </div>
                                    <label for="profile_image" style="position:absolute; bottom:5px; right:5px; width:40px; height:40px; background:var(--g-blue); border:2px solid #fff; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 6px 15px rgba(0,0,0,0.4); transition:0.3s;" title="Upload New Photo" onmouseover="this.style.transform='scale(1.1) rotate(15deg)'" onmouseout="this.style.transform='scale(1) rotate(0deg)'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                                        <input type="file" name="profile_image" id="profile_image" accept="image/png, image/jpeg, image/jpg" style="display:none;">
                                    </label>
                                </div>
                                <div>
                                    <div style="font-weight:700; color:#fff; font-size:1.2rem; margin-bottom:6px; letter-spacing:0.5px;">Custom Profile Icon</div>
                                    <div style="padding:4px 12px; background:rgba(37,99,235,0.15); color:var(--blue-l); border-radius:20px; font-size:0.8rem; display:inline-block; font-weight:600;">PNG, JPG &middot; MAX 2MB</div>
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:30px;">
                                <div class="input-group">
                                    <label style="color:var(--blue-l); font-weight:700; font-size:0.95rem; margin-bottom:10px; display:block; text-transform:uppercase; letter-spacing:1px;">User Full Name</label>
                                    <input type="text" name="full_name" class="input-field" value="<?= $userName ?>" required style="background:rgba(255,255,255,0.04); border:1px solid rgba(37,99,235,0.2); padding:16px 20px; border-radius:14px; transition:0.3s; color:#fff; font-size:1rem; width:100%;" onfocus="this.style.borderColor='var(--blue)'; this.style.boxShadow='0 0 15px rgba(37,99,235,0.15)'">
                                </div>
                                <div class="input-group">
                                    <label style="color:var(--teal-l); font-weight:700; font-size:0.95rem; margin-bottom:10px; display:block; text-transform:uppercase; letter-spacing:1px;">Email Connectivity</label>
                                    <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required style="background:rgba(255,255,255,0.04); border:1px solid rgba(5,150,105,0.2); padding:16px 20px; border-radius:14px; transition:0.3s; color:#fff; font-size:1rem; width:100%;" onfocus="this.style.borderColor='var(--teal)'; this.style.boxShadow='0 0 15px rgba(5,150,105,0.15)'">
                                </div>
                            </div>

                            <div class="input-group" style="margin-bottom:30px;">
                                <label style="color:var(--purple-l); font-weight:700; font-size:0.95rem; margin-bottom:10px; display:block; text-transform:uppercase; letter-spacing:1px;">Professional Role</label>
                                <input type="text" name="role" class="input-field" value="<?= $userRole ?>" style="background:rgba(255,255,255,0.04); border:1px solid rgba(124,58,237,0.2); padding:16px 20px; border-radius:14px; transition:0.3s; color:#fff; font-size:1rem; width:100%;" onfocus="this.style.borderColor='var(--purple)'; this.style.boxShadow='0 0 15px rgba(124,58,237,0.15)'">
                            </div>

                            <div class="input-group" style="margin-bottom:40px;">
                                <label style="color:var(--orange-l); font-weight:700; font-size:0.95rem; margin-bottom:10px; display:block; text-transform:uppercase; letter-spacing:1px;">Bio & Background</label>
                                <textarea name="bio" class="input-field" style="min-height:140px; background:rgba(255,255,255,0.04); border:1px solid rgba(234,88,12,0.2); padding:16px 20px; border-radius:14px; transition:0.3s; color:#fff; font-size:1rem; width:100%; resize:vertical;" onfocus="this.style.borderColor='var(--orange)'; this.style.boxShadow='0 0 15px rgba(234,88,12,0.15)'"><?= htmlspecialchars($_SESSION['user_bio'] ?? '') ?></textarea>
                            </div>

                            <!-- Password Section with colorful gradient header -->
                            <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:40px; margin-bottom:40px;">
                                <label class="cp-card" style="display:inline-flex; align-items:center; gap:16px; cursor:pointer; padding:12px 24px; background:rgba(255,255,255,0.03); border-radius:16px; border:1px solid rgba(255,255,255,0.07); transition:0.3s;" onmouseover="this.style.borderColor='var(--pink-l)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'">
                                    <input type="checkbox" id="togglePasswordFields" style="width:20px; height:20px; accent-color:var(--pink);">
                                    <span style="color:#fff; font-weight:700; font-size:1.1rem; background:var(--g-pink); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">Security Update Needed?</span>
                                </label>
                                
                                <div id="passwordFields" style="display:none; margin-top:30px; padding:30px; background:rgba(0,0,0,0.3); border-radius:24px; border:2px dashed rgba(219,39,119,0.3); animation: slideIn 0.4s ease-out;">
                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                                        <div>
                                            <input type="password" name="new_password" class="input-field" placeholder="Create New Key" style="background:rgba(219,39,119,0.05); border:1px solid rgba(219,39,119,0.2); color:#fff; padding:16px; width:100%; border-radius:12px;">
                                        </div>
                                        <div>
                                            <input type="password" name="confirm_password" class="input-field" placeholder="Validate Key" style="background:rgba(219,39,119,0.05); border:1px solid rgba(219,39,119,0.2); color:#fff; padding:16px; width:100%; border-radius:12px;">
                                        </div>
                                    </div>
                                    <p style="color:var(--pink-l); font-size:0.8rem; margin-top:12px; margin-bottom:0; font-weight:600;">&bull; Use a strong combination of keys for safety.</p>
                                </div>
                            </div>

                            <div style="display:flex; gap:20px; justify-content:flex-end; margin-top:20px;">
                                <a href="dashboard.php?page=profile" class="btn" style="background:rgba(255,255,255,0.05); color:#fff; border:1px solid rgba(255,255,255,0.15); padding:16px 36px; border-radius:18px; text-decoration:none; font-weight:600; font-size:1.1rem; transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">Discard</a>
                                <button type="submit" class="btn btn-primary" style="padding:16px 48px; border-radius:18px; font-weight:800; font-size:1.1rem; box-shadow:0 10px 25px rgba(37,99,235,0.3); border:none; color:#fff; cursor:pointer;">Commit Changes</button>
                            </div>

                        </form>
                    </div>

                </div>
            </div>
            
            <div class="glass-panel" style="padding:30px; border-radius:24px; border:1px solid rgba(16,185,129,0.3); background:linear-gradient(90deg, rgba(16,185,129,0.08), transparent); display:flex; align-items:center; gap:20px; box-shadow:0 10px 30px rgba(0,0,0,0.1);">
                <div style="width:50px; height:50px; background:rgba(16,185,129,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px;">🛡️</div>
                <div>
                    <div style="color:#10B981; font-weight:800; font-size:1.1rem; margin-bottom:4px;">Advanced Privacy Protocol</div>
                    <p style="margin:0; font-size:0.95rem; color:#A7F3D0; opacity:0.8;">Your personal dataset is encrypted and remains strictly confidential within this workspace.</p>
                </div>
            </div>
        </div>

        <style>
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }
        </style>

        <script>
            // Live Image Preview Refresh
            document.getElementById('profile_image').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const preview = document.getElementById('imagePreview');
                        preview.innerHTML = `<img src="${event.target.result}" style="width:100%; height:100%; object-fit:cover; animation: fadeIn 0.5s ease;">`;
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Colorful Password Toggle
            document.getElementById('togglePasswordFields').addEventListener('change', function() {
                const fields = document.getElementById('passwordFields');
                fields.style.display = this.checked ? 'block' : 'none';
                fields.querySelectorAll('input').forEach(input => {
                    input.required = this.checked;
                });
            });
        </script>

    <?php elseif ($page === 'compare'): // ═══ COMPARISON GUIDE (TABBED) ═══ ?>

        <a href="dashboard.php?page=learn" class="back-btn mb-16">← Back to Learn Hub</a>

        <div class="module-hero" style="margin-top:12px; border-radius:30px; background:linear-gradient(135deg, rgba(37,99,235,0.15), rgba(5,150,105,0.15)); border:1px solid rgba(255,255,255,0.1);">
            <div class="hero-icon-box" style="background:var(--g-blue); border:none; box-shadow:0 10px 25px rgba(37,99,235,0.4);">⚖️</div>
            <div class="hero-text">
                <h2 style="font-weight:800; letter-spacing:-1px;">Bipolar vs Monopolar</h2>
                <p style="color:var(--blue-l); font-weight:500;">Complete technical & educational comparison guide</p>
            </div>
        </div>

        <div style="max-width:1000px;">
            <!-- Modern Tab Navigation -->
            <div style="display:flex; gap:12px; margin-bottom:30px; background:rgba(255,255,255,0.03); padding:8px; border-radius:20px; border:1px solid rgba(255,255,255,0.06);">
                <button class="tabs-btn active" onclick="switchCompareTab('tech')" style="flex:1; padding:14px; border-radius:14px; border:none; font-weight:700; cursor:pointer; transition:0.3s; background:transparent; color:rgba(255,255,255,0.5);">⚡ Technical</button>
                <button class="tabs-btn" onclick="switchCompareTab('clin')" style="flex:1; padding:14px; border-radius:14px; border:none; font-weight:700; cursor:pointer; transition:0.3s; background:transparent; color:rgba(255,255,255,0.5);">🏥 Educational</button>
                <button class="tabs-btn" onclick="switchCompareTab('prac')" style="flex:1; padding:14px; border-radius:14px; border:none; font-weight:700; cursor:pointer; transition:0.3s; background:transparent; color:rgba(255,255,255,0.5);">⚙️ Practical</button>
            </div>

            <!-- Header Quick View -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:30px;">
                <div class="detail-card" style="margin:0; background:linear-gradient(135deg, rgba(37,99,235,0.1), rgba(37,99,235,0.02)); border-top:4px solid #3B82F6;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                        <span style="font-size:1.5rem;">🧬</span>
                        <strong style="color:#3B82F6; font-size:1.2rem;">Bipolar Mode</strong>
                    </div>
                    <p style="font-size:0.85rem; color:#94A3B8;">Measures the difference between two active electrodes. Focuses on local precision.</p>
                </div>
                <div class="detail-card" style="margin:0; background:linear-gradient(135deg, rgba(20,184,166,0.1), rgba(20,184,166,0.02)); border-top:4px solid #14B8A6;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                        <span style="font-size:1.5rem;">📡</span>
                        <strong style="color:#14B8A6; font-size:1.2rem;">Monopolar Mode</strong>
                    </div>
                    <p style="font-size:0.85rem; color:#94A3B8;">Measures signal from one active electrode against a neutral reference. Broad field detection.</p>
                </div>
            </div>

            <!-- TECHNICAL CONTENT -->
            <div id="tech-content" class="compare-content-tab">
                <div class="detail-card">
                    <div class="panel-title" style="color:#fff;"><span>⚡</span> Technical Metrics</div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
                        <!-- SNR Metric -->
                        <div style="padding:20px; background:rgba(255,255,255,0.02); border-radius:16px; border:1px solid rgba(255,255,255,0.05);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                                <strong style="color:var(--blue-l);">Bipolar SNR</strong>
                                <span style="color:#10B981; font-weight:700;">85% Efficient</span>
                            </div>
                            <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                                <div style="width:85%; height:100%; background:var(--g-blue);"></div>
                            </div>
                        </div>
                        <div style="padding:20px; background:rgba(255,255,255,0.02); border-radius:16px; border:1px solid rgba(255,255,255,0.05);">
                            <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
                                <strong style="color:var(--teal-l);">Monopolar SNR</strong>
                                <span style="color:#F59E0B; font-weight:700;">55% Efficient</span>
                            </div>
                            <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                                <div style="width:55%; height:100%; background:var(--g-teal);"></div>
                            </div>
                        </div>
                    </div>

                    <table style="width:100%; margin-top:30px; border-collapse:separate; border-spacing:0 12px;">
                        <tr style="background:rgba(255,255,255,0.05);">
                            <th style="padding:15px; border-radius:12px 0 0 12px; text-align:left; color:#94A3B8; font-size:0.8rem; text-transform:uppercase;">Parameter</th>
                            <th style="padding:15px; text-align:left; color:#3B82F6;">Bipolar</th>
                            <th style="padding:15px; border-radius:0 12px 12px 0; text-align:left; color:#14B8A6;">Monopolar</th>
                        </tr>
                        <tr>
                            <td style="padding:15px;"><strong>Noise Rejection</strong></td>
                            <td style="padding:15px; color:#10B981;">✅ Excellent (CMRR > 90dB)</td>
                            <td style="padding:15px; color:#EF4444;">❌ Sensitive (Artifacts)</td>
                        </tr>
                        <tr>
                            <td style="padding:15px;"><strong>Spatial Resolution</strong></td>
                            <td style="padding:15px; color:var(--blue-l);">High (Focused)</td>
                            <td style="padding:15px; color:var(--teal-l);">Broad (Tissue Volume)</td>
                        </tr>
                        <tr>
                            <td style="padding:15px;"><strong>Signal Amplitude</strong></td>
                            <td style="padding:15px;">Lower (Differential)</td>
                            <td style="padding:15px; font-weight:700;">Higher (Absolute)</td>
                        </tr>
                        <tr>
                            <td style="padding:15px;"><strong>Freq. Capture</strong></td>
                            <td style="padding:15px;">0.1 - 10 kHz (Fast)</td>
                            <td style="padding:15px;">0.01 - 100 Hz (Slow)</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- CLINICAL CONTENT -->
            <div id="clin-content" class="compare-content-tab" style="display:none;">
                <div class="detail-card">
                    <div class="panel-title" style="color:#fff;"><span>🏥</span> Educational Efficacy</div>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div style="background:rgba(37,99,235,0.05); padding:25px; border-radius:20px; border:1px solid rgba(37,99,235,0.15);">
                            <h4 style="color:#60A5FA; margin-bottom:15px; display:flex; align-items:center; gap:10px;">🩺 Best for:</h4>
                            <ul style="list-style:none; padding:0; margin:0;">
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Deep Brain Stimulation (DBS)</li>
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Nerve Conduction Studies</li>
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Intramuscular EMG</li>
                                <li style="color:#E0E0E0;">• Focal Epilepsy EEG</li>
                            </ul>
                        </div>
                        <div style="background:rgba(20,184,166,0.05); padding:25px; border-radius:20px; border:1px solid rgba(20,184,166,0.15);">
                            <h4 style="color:#2DD4BF; margin-bottom:15px; display:flex; align-items:center; gap:10px;">🏥 Best for:</h4>
                            <ul style="list-style:none; padding:0; margin:0;">
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Standard Educational EEG</li>
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Routine ECG Screenings</li>
                                <li style="margin-bottom:10px; color:#E0E0E0;">• Sleep Studies (PSG)</li>
                                <li style="color:#E0E0E0;">• Generalized Activity Analysis</li>
                            </ul>
                        </div>
                    </div>

                    <div style="margin-top:30px; background:rgba(255,255,255,0.02); padding:25px; border-radius:20px; border:1px solid rgba(255,255,255,0.07);">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="text-align:center; padding:15px;">
                                <div style="font-size:2rem; font-weight:800; color:#10B981;">94%</div>
                                <div style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase;">Bipolar Accuracy</div>
                            </div>
                            <div style="width:2px; height:60px; background:rgba(255,255,255,0.1);"></div>
                            <div style="text-align:center; padding:15px;">
                                <div style="font-size:2rem; font-weight:800; color:#F59E0B;">78%</div>
                                <div style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase;">Monopolar Accuracy</div>
                            </div>
                            <div style="width:2px; height:60px; background:rgba(255,255,255,0.1);"></div>
                            <div style="text-align:center; padding:15px;">
                                <div style="font-size:1.5rem; font-weight:700; color:#fff;">Noisy OR</div>
                                <div style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase;">Ideal Environment</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRACTICAL CONTENT -->
            <div id="prac-content" class="compare-content-tab" style="display:none;">
                <div class="detail-card">
                    <div class="panel-title" style="color:#fff;"><span>⚙️</span> Practical Execution</div>
                    
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                        <div style="padding:20px; border-radius:16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);">
                            <div style="color:var(--blue-l); font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">💰 Equipment Cost</div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:1.1rem; color:#fff;">Bipolar: <span style="color:#3B82F6;">$$$</span> (High)</span>
                                <span style="font-size:1.1rem; color:#fff;">Mono: <span style="color:#14B8A6;">$$</span> (Std)</span>
                            </div>
                        </div>
                        <div style="padding:20px; border-radius:16px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06);">
                            <div style="color:var(--purple-l); font-weight:700; margin-bottom:8px; display:flex; align-items:center; gap:8px;">⌛ Setup Complexity</div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:0.9rem; color:#E0E0E0;">Bipolar: 20-30m (Complex)</span>
                                <span style="font-size:0.9rem; color:#E0E0E0;">Mono: 5-15m (Simple)</span>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:24px; padding:20px; background:rgba(124,58,237,0.05); border-radius:16px; border-left:4px solid var(--purple);">
                        <strong style="color:var(--purple-l); display:block; margin-bottom:8px;">🎓 Technician Training Requirements</strong>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                            <div>
                                <small style="color:rgba(255,255,255,0.5); text-transform:uppercase; font-size:0.65rem;">Bipolar Protocol</small>
                                <div style="color:#fff; font-weight:600;">Advanced (3-6 Months)</div>
                            </div>
                            <div>
                                <small style="color:rgba(255,255,255,0.5); text-transform:uppercase; font-size:0.65rem;">Monopolar Protocol</small>
                                <div style="color:#fff; font-weight:600;">Basic (1-2 Weeks)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI SELECTION GUIDE -->
            <div style="margin-top:40px; background:linear-gradient(135deg, rgba(8,145,178,0.2), rgba(124,58,237,0.2)); border:1px solid rgba(255,255,255,0.1); border-radius:24px; padding:30px; display:flex; align-items:center; gap:25px; position:relative; overflow:hidden;">
                <div style="font-size:3rem; filter:drop-shadow(0 0 10px rgba(8,145,178,0.5)); transform:rotate(-5deg);">🤖</div>
                <div style="flex:1;">
                    <h3 style="margin:0 0 8px 0; color:#fff; font-weight:800; font-family:'Space Grotesk', sans-serif; letter-spacing:-0.5px;">AI Selection Guide</h3>
                    <p style="margin:0; color:#A5F3FC; line-height:1.6; font-size:0.95rem;">Choose <strong>Bipolar</strong> for high-precision focal detection in noisy EMI environments. Choose <strong>Monopolar</strong> for general educational mapping and broader spatial coverage in quiet settings.</p>
                </div>
            </div>

            <!-- DEEP DIVE ANALYSIS TOOLS -->
            <div style="margin-top:40px;">
                <h3 style="color:#fff; font-size:1.2rem; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                    <span style="padding:8px; background:rgba(37,99,235,0.2); border-radius:10px;">🔍</span> 
                    Advanced Analysis Tools
                </h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <a href="dashboard.php?page=pros_cons" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding:20px; background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.05)); border:1px solid rgba(16,185,129,0.2); border-radius:20px; transition:0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='rgba(16,185,129,0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(16,185,129,0.2)'">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div style="width:40px; height:40px; background:var(--g-teal); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.2rem;">⚖️</div>
                            <div style="color:#fff; font-weight:600;">Detailed Pros & Cons</div>
                        </div>
                        <span style="color:var(--teal-l);">→</span>
                    </a>
                    <a href="dashboard.php?page=decision_guide" style="text-decoration:none; display:flex; align-items:center; justify-content:space-between; padding:20px; background:linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.05)); border:1px solid rgba(245,158,11,0.2); border-radius:20px; transition:0.3s;" onmouseover="this.style.transform='translateY(-3px)'; this.style.borderColor='rgba(245,158,11,0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(245,158,11,0.2)'">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <div style="width:40px; height:40px; background:var(--g-orange); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.2rem;">🧭</div>
                            <div style="color:#fff; font-weight:600;">Decision Guide Tool</div>
                        </div>
                        <span style="color:var(--orange-l);">→</span>
                    </a>
                </div>
            </div>

        </div>

        <script>
            function switchCompareTab(tabId) {
                // Hide all tabs
                document.querySelectorAll('.compare-content-tab').forEach(tab => tab.style.display = 'none');
                
                // Show selected tab
                document.getElementById(tabId + '-content').style.display = 'block';
                
                // Update active button state
                document.querySelectorAll('.tabs-btn').forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.background = 'transparent';
                    btn.style.color = 'rgba(255,255,255,0.5)';
                });
                
                const activeBtn = event.currentTarget;
                activeBtn.classList.add('active');
                activeBtn.style.background = 'rgba(255,255,255,0.1)';
                activeBtn.style.color = '#fff';
            }
            
            // Initial state set
            window.onload = function() {
                const firstTabBtn = document.querySelector('.tabs-btn');
                if(firstTabBtn) {
                  firstTabBtn.style.background = 'rgba(255,255,255,0.1)';
                  firstTabBtn.style.color = '#fff';
                }
            };
        </script>

        <style>
            .compare-content-tab { animation: fadeIn 0.4s ease-out; }
            .tabs-btn:hover { background: rgba(255,255,255,0.05) !important; color: #fff !important; }
            .tabs-btn.active { box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important; }
        </style>


    <?php elseif ($page === 'ai'): // ═══ AI ANALYSIS SYSTEM ═══ ?>

        <div id="ai-wrapper">
            <!-- 1. Configuration View -->
            <div id="ai-config-view">
                <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(142,36,170,0.15), rgba(123,31,162,0.1)); border:1px solid rgba(142,36,170,0.3); border-radius:24px;">
                    <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(142,36,170,0.2); border-color:#8E24AA;">🧠</div>
                    <div class="hero-text">
                        <h2>Advanced AI Analysis</h2>
                        <p style="color:#CE93D8;">Educational-grade signal classification & configuration optimization</p>
                    </div>
                </div>

                <div style="max-width:960px; margin-top:30px;">
                    
                    <!-- Electrode Selection -->
                    <div class="detail-card">
                        <div class="panel-title" style="color:#CE93D8;"><span>1️⃣</span> Electrode Configuration</div>
                        <p class="text-small" style="margin-bottom:20px;">Select the recording technique for the AI to optimize its analysis parameters.</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%;">
                            <div class="electrode-card active" id="aiCardBipolar" onclick="setAiElectrode('Bipolar')" style="background:rgba(255,255,255,0.03); border:2px solid rgba(255,255,255,0.1); border-radius:16px; padding:20px; text-align:center; cursor:pointer; position:relative; transition:0.3s;">
                                <div style="font-size:2.5rem; margin-bottom:15px;">⚡</div>
                                <h4 style="color:#fff; margin:0 0 8px 0; font-size:1.1rem;">Bipolar Mode</h4>
                                <p style="margin:0; font-size:0.8rem; color:#94A3B8;">Differential recording for high precision.</p>
                                <div class="selection-indicator" style="position:absolute; top:12px; right:12px; background:#8E24AA; color:#fff; font-size:0.6rem; font-weight:800; padding:4px 8px; border-radius:12px; opacity:0; transform:scale(0.8); transition:0.3s;">SELECTED</div>
                            </div>
                            <div class="electrode-card" id="aiCardMonopolar" onclick="setAiElectrode('Monopolar')" style="background:rgba(255,255,255,0.03); border:2px solid rgba(255,255,255,0.1); border-radius:16px; padding:20px; text-align:center; cursor:pointer; position:relative; transition:0.3s;">
                                <div style="font-size:2.5rem; margin-bottom:15px;">📡</div>
                                <h4 style="color:#fff; margin:0 0 8px 0; font-size:1.1rem;">Monopolar Mode</h4>
                                <p style="margin:0; font-size:0.8rem; color:#94A3B8;">Absolute potential for broader focus.</p>
                                <div class="selection-indicator" style="position:absolute; top:12px; right:12px; background:#8E24AA; color:#fff; font-size:0.6rem; font-weight:800; padding:4px 8px; border-radius:12px; opacity:0; transform:scale(0.8); transition:0.3s;">SELECTED</div>
                            </div>
                        </div>
                    </div>

                    <!-- Environment Noise Level Grid -->
                    <div class="detail-card">
                        <div class="panel-title" style="color:#CE93D8;"><span>2️⃣</span> Environment Noise Level</div>
                        <p class="text-small" style="margin-bottom:20px;">Indicate the educational environment to adjust noise filtering algorithms.</p>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; width: 100%;">
                            <div class="noise-card" id="aiNoiseLow" onclick="setAiNoise('Low')" style="background:rgba(255,255,255,0.03); border:2px solid rgba(255,255,255,0.1); border-radius:16px; padding:15px; text-align:center; cursor:pointer; position:relative; transition:0.3s;">
                                <div style="font-size:1.5rem; margin-bottom:8px;">🍃</div>
                                <div style="font-weight:700; color:#22C55E; margin-bottom:4px;">Low Noise</div>
                                <div style="font-size:0.75rem; color:#94A3B8;">Quiet Lab</div>
                                <div class="selection-indicator" style="position:absolute; top:8px; right:8px; font-weight:800; font-size:0.55rem; color:#fff; padding:2px 6px; border-radius:10px; opacity:0;">ACTIVE</div>
                            </div>
                            <div class="noise-card active" id="aiNoiseMedium" onclick="setAiNoise('Medium')" style="background:rgba(255,255,255,0.03); border:2px solid rgba(255,255,255,0.1); border-radius:16px; padding:15px; text-align:center; cursor:pointer; position:relative; transition:0.3s;">
                                <div style="font-size:1.5rem; margin-bottom:8px;">🏢</div>
                                <div style="font-weight:700; color:#F59E0B; margin-bottom:4px;">Medium</div>
                                <div style="font-size:0.75rem; color:#94A3B8;">Standard Clinic</div>
                                <div class="selection-indicator" style="position:absolute; top:8px; right:8px; font-weight:800; font-size:0.55rem; color:#fff; padding:2px 6px; border-radius:10px; opacity:0;">ACTIVE</div>
                            </div>
                            <div class="noise-card" id="aiNoiseHigh" onclick="setAiNoise('High')" style="background:rgba(255,255,255,0.03); border:2px solid rgba(255,255,255,0.1); border-radius:16px; padding:15px; text-align:center; cursor:pointer; position:relative; transition:0.3s;">
                                <div style="font-size:1.5rem; margin-bottom:8px;">🏭</div>
                                <div style="font-weight:700; color:#EF4444; margin-bottom:4px;">High Noise</div>
                                <div style="font-size:0.75rem; color:#94A3B8;">Op. Room</div>
                                <div class="selection-indicator" style="position:absolute; top:8px; right:8px; font-weight:800; font-size:0.55rem; color:#fff; padding:2px 6px; border-radius:10px; opacity:0;">ACTIVE</div>
                            </div>
                        </div>
                    </div>

                    <!-- Application & Dataset Section -->
                    <div class="detail-card">
                        <div class="panel-title" style="color:#CE93D8;"><span>3️⃣</span> Data Source & Application</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-top:15px;">
                            <div>
                                <h3 style="color:#fff; margin-bottom:10px; font-size:0.95rem;">Application Type</h3>
                                <select id="aiAppSelector" onchange="autoFillDataset()" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px; width:100%; color:#fff; cursor:pointer; transition:0.3s;" onmouseover="this.style.borderColor='rgba(142,36,170,0.5)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'">
                                    <option value="EEG" selected>EEG (Brain Waves)</option>
                                    <option value="ECG">ECG (Heart Rhythm)</option>
                                    <option value="EMG">EMG (Muscle Activity)</option>
                                    <option value="DBS">Deep Brain Stimulation</option>
                                    <option value="Nerve">Nerve Conduction</option>
                                    <option value="Custom">Custom User Study</option>
                                </select>
                            </div>
                            <div>
                                <h3 style="color:#fff; margin-bottom:10px; font-size:0.95rem;">Analysis Data Source</h3>
                                <div id="aiFileSelected" style="padding:12px; background:rgba(142,36,170,0.1); border:1px solid rgba(142,36,170,0.3); border-radius:12px; font-size:0.85rem; color:#fff; min-height:46px; display:flex; align-items:center; justify-content:center; text-align:center;">
                                    Loading configuration...
                                </div>
                            </div>
                        </div>

                        <!-- Premium Upload Zone -->
                        <div id="aiUploadArea" onclick="document.getElementById('aiFileInput').click()" style="margin-top:30px; border:2px dashed rgba(142,36,170,0.3); border-radius:20px; padding:40px; text-align:center; cursor:pointer; background:rgba(255,255,255,0.02); transition:0.3s;" onmouseover="this.style.borderColor='#8E24AA'; this.style.background='rgba(142,36,170,0.05)';" onmouseout="this.style.borderColor='rgba(142,36,170,0.3)'; this.style.background='rgba(255,255,255,0.02)';">
                            <div style="font-size:3rem; margin-bottom:15px;">📄</div>
                            <h4 style="color:#fff; margin:0 0 8px 0; font-size:1.2rem;">Upload Your Custom Dataset</h4>
                            <p style="color:#94A3B8; margin:0 0 15px 0; font-size:0.9rem;">Drop CSV or EDF files here for specialized AI training</p>
                            <span class="btn btn-primary" style="background:var(--g-purple);">Browse Files</span>
                        </div>
                        <input type="file" id="aiFileInput" style="display:none;" onchange="handleAiFileUpload(this)">
                    </div>

                    <div style="margin-top:30px; margin-bottom:60px; text-align:right;">
                        <button class="btn btn-primary" onclick="runAiAnalysis()" style="background:var(--g-purple); padding:16px 40px; font-size:1.1rem; box-shadow:0 10px 25px rgba(106, 27, 154, 0.4);">
                            <span>⚡</span> Run AI Expert Analysis
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Progress View -->
            <div id="ai-progress-view" style="display:none; text-align:center; padding:80px 20px;">
                <div style="width:100px; height:100px; margin:0 auto; background:rgba(142,36,170,0.1); border:2px solid #8E24AA; border-radius:50%; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
                    <div style="font-size:3rem; filter:drop-shadow(0 0 10px #CE93D8);">🧠</div>
                    <div style="position:absolute; top:0; left:0; width:100%; height:8px; background:rgba(206,147,216,0.8); box-shadow:0 0 15px #CE93D8; filter:blur(2px); animation:aiScan 2s infinite ease-in-out;"></div>
                </div>
                <h2 style="color:#fff; margin-top:30px; font-weight:600;" id="ai-progress-title">Uploading to AI Engine...</h2>
                <p style="color:#A0AEC0; max-width:400px; margin:15px auto;" id="ai-progress-desc">Initializing analysis pipeline for selected configuration.</p>
                
                <div style="max-width:300px; margin: 30px auto 0;">
                    <div style="height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden;">
                        <div id="ai-progress-bar" style="height:100%; width:0%; background:var(--g-purple); border-radius:3px; transition:width 0.4s ease;"></div>
                    </div>
                </div>
            </div>

            <!-- 3. Results View -->
            <div id="ai-results-view" style="display:none; padding-bottom:40px; max-width:960px;">
                <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(34,197,94,0.15), rgba(34,197,94,0.05)); border:1px solid rgba(34,197,94,0.3); padding:30px; border-radius:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; gap:20px; align-items:center;">
                            <div style="font-size:3rem; background:rgba(34,197,94,0.2); border-radius:16px; width:70px; height:70px; display:flex; align-items:center; justify-content:center;">✅</div>
                            <div>
                                <h1 style="margin:0; font-size:1.8rem; color:#fff;">Analysis Complete</h1>
                                <p style="margin:5px 0 0 0; color:#86EFAC; font-size:1rem;" id="ai-res-subtitle">Reference: Bio-AI-2026</p>
                            </div>
                        </div>
                        <button class="btn btn-outline" onclick="resetAiSystem()" style="color:#fff; border-color:rgba(255,255,255,0.2);">← Start Over</button>
                    </div>
                </div>

                <!-- NEW: Integrated Quality Score Card -->
                <div id="ai-fidelity-card" style="display:none; background:linear-gradient(135deg, #10B981, #059669); padding:25px; border-radius:24px; margin-top:20px; display:flex; align-items:center; gap:20px; box-shadow:0 15px 35px rgba(16,185,129,0.3); animation: slideUp 0.5s ease-out;">
                    <div id="ai-fidelity-score-bubble" style="width:70px; height:70px; border:4px solid rgba(255,255,255,0.3); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.5rem; color:#fff;">92</div>
                    <div>
                        <h4 style="color:#fff; margin:0 0 5px 0;">Signal Fidelity Score</h4>
                        <p id="ai-fidelity-desc" style="color:rgba(255,255,255,0.9); font-size:0.9rem; margin:0;">Analyzing signal purity...</p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px; margin-top:20px;">
                    <!-- Recommendation Card -->
                    <div class="detail-card" style="text-align:center;">
                        <div id="ai-res-icon" style="font-size:3.5rem; margin-bottom:15px;">⚡</div>
                        <h2 style="color:#fff; margin:0 0 10px 0; font-size:1.2rem;" id="ai-res-rec-title">Recommended: Bipolar</h2>
                        <p style="color:#94A3B8; font-size:0.85rem; line-height:1.5; margin:0 auto 20px;" id="ai-res-rec-desc">Based on your configuration, Bipolar recording is optimal.</p>
                        
                        <div style="background:rgba(255,255,255,0.02); border-radius:12px; padding:20px; border:1px solid rgba(255,255,255,0.05); margin-bottom:20px;">
                            <div style="font-size:0.75rem; color:#94A3B8; text-transform:uppercase; font-weight:bold; letter-spacing:1px; margin-bottom:10px;">Confidence Score</div>
                            <div style="font-size:2.5rem; font-weight:800; color:#CE93D8;" id="ai-res-confidence">96%</div>
                            <div style="height:6px; background:rgba(255,255,255,0.1); border-radius:3px; overflow:hidden; margin-top:10px;">
                                <div id="ai-res-conf-bar" style="height:100%; width:96%; background:var(--g-purple);"></div>
                            </div>
                            <div style="font-size:0.75rem; color:#64748B; margin-top:10px;" id="ai-res-source">📊 Based on uploaded dataset</div>
                        </div>
                    </div>

                    <!-- Metrics -->
                    <div class="detail-card">
                        <div class="panel-title" style="color:#fff;"><span>📊</span> Performance Metrics (<span id="ai-res-type">Bipolar</span>)</div>
                        <div id="ai-res-bars" style="display:flex; flex-direction:column; gap:15px; margin-bottom:25px;">
                            <!-- Injected by JS -->
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div style="background:rgba(34,197,94,0.05); padding:15px; border-radius:12px; border:1px solid rgba(34,197,94,0.15);">
                                <h4 style="color:#22C55E; margin-bottom:10px; font-size:0.9rem;">✓ Key Advantages</h4>
                                <div class="why-container" id="ai-why-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                            </div>
                            <div style="background:rgba(245,158,11,0.05); padding:15px; border-radius:12px; border:1px solid rgba(245,158,11,0.15);">
                                <h4 style="color:#F59E0B; margin-bottom:10px; font-size:0.9rem;">⚠️ Considerations</h4>
                                <div class="cons-container" id="ai-cons-container" style="display:flex; flex-direction:column; gap:8px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Uploaded Signal Graph -->
                <div class="detail-card" style="margin-top:20px; display:none;" id="ai-graph-container">
                    <div class="panel-title" style="color:#fff;"><span>📈</span> Signal Visualization</div>
                    <div style="background:#0A0E21; border-radius:12px; padding:10px; border:1px solid rgba(255,255,255,0.06);">
                        <canvas id="aiSignalCanvas" width="900" height="150" style="width:100%; display:block;"></canvas>
                    </div>
                </div>

                <!-- Signal Quality Analysis -->
                <div class="detail-card" style="margin-top:20px;">
                    <div class="panel-title" style="color:#fff;"><span>📡</span> Signal Quality Analysis</div>
                    <div id="ai-quality-analysis">
                        <p style="color:#94A3B8; font-size:0.9rem;">Processing signal characteristics...</p>
                    </div>
                </div>

                <!-- Navigation & Detailed Modules -->
                <div style="margin-top:30px; display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <!-- Option 1: Run Another Analysis -->
                    <div class="detail-card" style="text-align:center; padding:25px; cursor:pointer; transition:0.3s; background:rgba(255,255,255,0.02); border:1px solid rgba(255,255,255,0.05);" onmouseover="this.style.borderColor='rgba(142,36,170,0.5)'; this.style.background='rgba(142,36,170,0.05)';" onmouseout="this.style.borderColor='rgba(255,255,255,0.05)'; this.style.background='rgba(255,255,255,0.02)';" onclick="resetAiSystem()">
                        <div style="font-size:2rem; margin-bottom:12px;">↺</div>
                        <h3 style="color:#fff; margin:0 0 5px 0; font-size:1rem;">New Analysis</h3>
                        <p style="color:#94A3B8; font-size:0.8rem; margin:0;">Test different parameters</p>
                    </div>

                    <!-- Option 2: Deep Dive into Signal Quality Page -->
                    <div class="detail-card" style="text-align:center; padding:25px; cursor:pointer; transition:0.3s; background:linear-gradient(135deg, rgba(20,184,166,0.1), rgba(16,185,129,0.05)); border:1px solid rgba(20,184,166,0.2);" onmouseover="this.style.transform='translateY(-5px)'; this.style.borderColor='rgba(20,184,166,0.5)'; this.style.boxShadow='0 12px 25px rgba(20,184,166,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(20,184,166,0.2)'; this.style.boxShadow='none';" onclick="window.location.href='dashboard.php?page=signal_quality'">
                        <div style="font-size:2rem; margin-bottom:12px; filter: drop-shadow(0 0 8px rgba(20,184,166,0.4));">📡</div>
                        <h3 style="color:#fff; margin:0 0 5px 0; font-size:1rem;">Advanced Quality</h3>
                        <p style="color:#99F6E4; font-size:0.8rem; margin:0;">Waveform analysis module</p>
                    </div>
                </div>

                <!-- NEW: Premium Wide Back Home Card -->
                <div class="detail-card" style="margin-top:20px; text-align:center; padding:30px; cursor:pointer; transition:0.4s; background:linear-gradient(135deg, rgba(37,99,235,0.15), rgba(79,70,229,0.1)); border:1px solid rgba(37,99,235,0.3); display:flex; align-items:center; justify-content:center; gap:25px;" onmouseover="this.style.transform='scale(1.02)'; this.style.borderColor='rgba(37,99,235,0.8)'; this.style.background='rgba(37,99,235,0.2)';" onmouseout="this.style.transform='scale(1)'; this.style.borderColor='rgba(37,99,235,0.3)'; this.style.background='linear-gradient(135deg, rgba(37,99,235,0.15), rgba(79,70,229,0.1))';" onclick="window.location.href='dashboard.php'">
                    <div style="font-size:2.8rem; filter: drop-shadow(0 0 15px rgba(37,99,235,0.6));">🏠</div>
                    <div style="text-align:left;">
                        <h3 style="color:#fff; margin:0 0 5px 0; font-size:1.3rem; font-weight:800; letter-spacing:-0.5px;">Return to Main Dashboard</h3>
                        <p style="color:#93C5FD; font-size:0.95rem; margin:0;">Exit AI Research and view core analyticals</p>
                    </div>
                    <div style="margin-left:auto; font-size:1.5rem; color:rgba(255,255,255,0.3);">❯</div>
                </div>
            </div>
        </div>

        <style>
            #ai-wrapper { animation: fadeIn 0.4s ease; }
            @keyframes aiScan { 0% { top:-10%; } 50% { top:110%; } 100% { top:-10%; } }
            
            /* Electrode Card interaction */
            .electrode-card:hover { border-color: rgba(142,36,170,0.5) !important; background: rgba(142,36,170,0.05) !important; }
            .electrode-card.active { border-color: #8E24AA !important; background: rgba(142,36,170,0.15) !important; box-shadow: 0 10px 25px rgba(142,36,170,0.2) !important; }
            .electrode-card.active .selection-indicator { opacity: 1 !important; transform: scale(1) !important; }

            /* Noise Card interaction */
            .noise-card.active#aiNoiseLow { border-color: #22C55E !important; background: rgba(34,197,94,0.1) !important; box-shadow: 0 10px 25px rgba(34,197,94,0.2) !important; }
            .noise-card.active#aiNoiseMedium { border-color: #F59E0B !important; background: rgba(245,158,11,0.1) !important; box-shadow: 0 10px 25px rgba(245,158,11,0.2) !important; }
            .noise-card.active#aiNoiseHigh { border-color: #EF4444 !important; background: rgba(239,68,68,0.1) !important; box-shadow: 0 10px 25px rgba(239,68,68,0.2) !important; }
            .noise-card.active .selection-indicator { opacity: 1 !important; }
            .noise-card.active#aiNoiseLow .selection-indicator { background: #22C55E !important; }
            .noise-card.active#aiNoiseMedium .selection-indicator { background: #F59E0B !important; }
            .noise-card.active#aiNoiseHigh .selection-indicator { background: #EF4444 !important; }
            
            .ai-list-item { font-size:0.85rem; color:#E2E8F0; }
        </style>

        <script>
            let aiState = {
                electrode: 'Bipolar',
                appInfo: 'EEG (Brain Waves)',
                noise: 'Medium',
                isAutoFill: true,
                filename: ''
            };

            function setAiElectrode(type) {
                // Clear any manual file upload when switching back to configuration modes
                const fileInput = document.getElementById('aiFileInput');
                if(fileInput) fileInput.value = '';
                
                aiState.electrode = type;
                document.querySelectorAll('.electrode-card').forEach(c => c.classList.remove('active'));
                document.getElementById('aiCard' + type).classList.add('active');
                autoFillDataset();
            }

            function setAiNoise(level) {
                // Clear any manual file upload
                const fileInput = document.getElementById('aiFileInput');
                if(fileInput) fileInput.value = '';

                aiState.noise = level;
                document.querySelectorAll('.noise-card').forEach(c => c.classList.remove('active'));
                document.getElementById('aiNoise' + level).classList.add('active');
                autoFillDataset();
            }

            function handleAiFileUpload(input) {
                if(input.files && input.files[0]) {
                    const name = input.files[0].name;
                    aiState.filename = name;
                    aiState.isAutoFill = false;
                    const display = document.getElementById('aiFileSelected');
                    display.innerHTML = `📁 User Dataset: <span style="color:#22C55E;">${name}</span>`;
                    display.style.background = "rgba(34,197,94,0.1)";
                    display.style.borderColor = "rgba(34,197,94,0.3)";
                    display.style.color = "#22C55E";
                }
            }

            function autoFillDataset() {
                const appType = document.getElementById('aiAppSelector').value;
                if(appType) {
                    aiState.appInfo = document.getElementById('aiAppSelector').options[document.getElementById('aiAppSelector').selectedIndex].text;
                }
                
                // If a manual file is uploaded, prioritize it and exit
                const fileInput = document.getElementById('aiFileInput');
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    return; 
                }
                
                aiState.isAutoFill = true;
                if(appType === 'Custom') {
                    aiState.filename = `simulated_generic_${aiState.electrode.toLowerCase()}_data.csv`;
                } else {
                    aiState.filename = `simulated_${aiState.electrode.toLowerCase()}_${appType.toLowerCase()}_${aiState.noise.toLowerCase()}.csv`;
                }
                
                const display = document.getElementById('aiFileSelected');
                display.innerHTML = `🤖 Neural-Sync: <span style="color:#fff; margin-left:5px;">${aiState.filename}</span>`;
                display.style.background = "rgba(142,36,170,0.15)";
                display.style.borderColor = "rgba(142,36,170,0.5)";
                display.style.color = "#CE93D8";
            }

            // Reliable initialization
            setTimeout(autoFillDataset, 200);
            window.addEventListener('load', autoFillDataset);
            document.addEventListener('DOMContentLoaded', autoFillDataset);

            async function runAiAnalysis() {
                const appType = document.getElementById('aiAppSelector').value;
                if(!appType) {
                    alert("Please select an Application Type.");
                    return;
                }
                
                // Check if a file is actually uploaded
                const fileInput = document.getElementById('aiFileInput');
                let isUploading = false;

                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    aiState.filename = fileInput.files[0].name;
                    aiState.isAutoFill = false;
                    isUploading = true;
                } else {
                    // If no file, ensure we have an auto-generated one
                    autoFillDataset();
                }

                if(!aiState.filename) {
                    // Extremely unlikely fallback
                    aiState.filename = "simulated_default_dataset.csv";
                    aiState.isAutoFill = true;
                }

                // Show progress
                document.getElementById('ai-config-view').style.display = 'none';
                document.getElementById('ai-progress-view').style.display = 'block';

                const steps = [
                    { t: "Uploading dataset to AI Backend...", d: "Connecting to secure processing node.", p: 10, delay: 200 },
                    { t: "Extracting Features...", d: "Analyzing frequency components and baseline wander.", p: 35, delay: 400 },
                    { t: "Analyzing Noise Profile...", d: "Detecting 50/60 Hz power-line interference and muscle artifact bursts.", p: 65, delay: 400 },
                    { t: "Generating Educational Recommendations...", d: "Comparing SNR and CMRR projections against educational benchmarks.", p: 90, delay: 400 }
                ];

                let currentDelay = 0;
                steps.forEach((step, index) => {
                    setTimeout(() => {
                        document.getElementById('ai-progress-title').innerText = step.t;
                        document.getElementById('ai-progress-desc').innerText = step.d;
                        document.getElementById('ai-progress-bar').style.width = step.p + "%";
                    }, currentDelay);
                    currentDelay += step.delay;
                });

                const analyzeBtn = document.querySelector('button[onclick="runAiAnalysis()"]');
                if(analyzeBtn) {
                    analyzeBtn.disabled = true;
                    analyzeBtn.innerHTML = '<span>⏳</span> Processing AI Data...';
                }

                const formData = new FormData();
                if (isUploading && fileInput.files.length > 0) {
                    formData.append('dataset', fileInput.files[0]);
                }
                formData.append('appType', appType);
                formData.append('electrode', aiState.electrode);
                formData.append('noise', aiState.noise);

                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        body: formData
                    });
                    const textContent = await response.text();
                    try {
                        aiState.apiData = JSON.parse(textContent);
                    } catch(jsonErr) {
                        console.error("Malformed AI JSON:", textContent);
                        aiState.apiData = { status: 'error', message: "AI Backend returned invalid data." };
                    }
                } catch (e) {
                    aiState.apiData = { status: 'error', error: e.message };
                    console.error("AI Analysis network failure:", e);
                }

                // Restore button
                if(analyzeBtn) {
                    analyzeBtn.disabled = false;
                    analyzeBtn.innerHTML = '<span>⚡</span> Run AI Expert Analysis';
                }

                // Finish
                setTimeout(() => {
                    showAiResults(appType);
                }, currentDelay + 200);
            }

            function showAiResults(appType) {
                // Hide progress, show results
                document.getElementById('ai-progress-view').style.display = 'none';
                document.getElementById('ai-results-view').style.display = 'block';
                document.getElementById('ai-progress-bar').style.width = '100%';

                const electrode = aiState.electrode;
                const noise = aiState.noise;
                const isBipolar = electrode === 'Bipolar';

                // --- Generate or use API data ---
                let snrNum, confidence, noiseReduction, stability, spatialRes, amplitudes, cleanSignal;

                if (aiState.apiData && aiState.apiData.status === 'success') {
                    // Real API response from the Python Backend Simulation Math / Real Analysis
                    snrNum = aiState.apiData.actual_snr || 12;
                    amplitudes = aiState.apiData.amplitudes || [];
                    cleanSignal = aiState.apiData.clean_signal || amplitudes;
                    confidence = aiState.apiData.confidence || 92;
                    noiseReduction = aiState.apiData.noise_reduction || 60;
                    stability = aiState.apiData.stability || 85;
                    spatialRes = aiState.apiData.spatial_resolution || 78;
                } else {
                    // Fail-safe if API fails to hit Python
                    snrNum = isBipolar ? 15 : 6;
                    confidence = isBipolar ? 92 : 65;
                    noiseReduction = isBipolar ? 60 : 25;
                    stability = isBipolar ? 85 : 50;
                    spatialRes = isBipolar ? 78 : 60;
                    amplitudes = Array(300).fill(0).map(() => Math.random());
                    cleanSignal = Array(300).fill(0).map(() => Math.random());
                }

                const actualSnr = snrNum.toFixed ? snrNum.toFixed(1) + ':1' : snrNum + ':1';

                // --- Populate Results UI ---
                // Subtitle
                document.getElementById('ai-res-subtitle').innerText =
                    `${aiState.appInfo} • ${electrode} • ${noise} Noise • Ref: Bio-AI-${new Date().getFullYear()}`;

                // Recommendation
                const appIcons = { 'EEG': '🧠', 'ECG': '❤️', 'EMG': '💪', 'DBS': '⚡', 'Nerve': '🔬', 'Custom': '📊' };
                document.getElementById('ai-res-icon').innerText = appIcons[appType] || '⚡';
                document.getElementById('ai-res-rec-title').innerText = `Recommended: ${electrode}`;

                const recDescs = {
                    'EEG': isBipolar
                        ? 'Bipolar montage provides superior artifact rejection for EEG, ideal for localizing epileptiform discharges and phase reversals.'
                        : 'Monopolar (referential) EEG captures absolute scalp potentials, useful for amplitude mapping and broad spatial surveys.',
                    'ECG': isBipolar
                        ? 'Bipolar ECG leads (e.g., Lead I, II, III) provide clean cardiac waveforms with excellent CMRR for precise P-QRS-T morphology analysis.'
                        : 'Monopolar (unipolar) ECG leads (aVR, aVL, aVF) capture absolute cardiac potentials for augmented limb lead analyticals.',
                    'EMG': isBipolar
                        ? 'Bipolar surface EMG with differential amplification rejects cross-talk from adjacent muscles, yielding focused motor unit data.'
                        : 'Monopolar needle EMG captures absolute motor unit action potentials with higher amplitude for individual fiber analysis.',
                    'DBS': isBipolar
                        ? 'Bipolar DBS electrode configurations deliver focused stimulation with minimal current spread, reducing side effects.'
                        : 'Monopolar DBS provides wider stimulation fields, useful for initial parameter optimization and larger target coverage.',
                    'Nerve': isBipolar
                        ? 'Bipolar nerve recording isolates compound action potentials with minimal far-field contamination for precise conduction studies.'
                        : 'Monopolar nerve recording captures broader field potentials, useful for detecting distributed neural generators.',
                    'Custom': isBipolar
                        ? 'Bipolar recording provides superior noise rejection and precise signal localization for your custom dataset.'
                        : 'Monopolar recording offers higher amplitude capture and broader spatial coverage for your custom dataset.'
                };
                document.getElementById('ai-res-rec-desc').innerText = recDescs[appType] || recDescs['Custom'];

                document.getElementById('ai-res-confidence').innerText = confidence + '%';
                document.getElementById('ai-res-conf-bar').style.width = confidence + '%';
                document.getElementById('ai-res-source').innerText = aiState.isAutoFill
                    ? '🤖 Based on auto-generated simulation dataset'
                    : '📁 Based on uploaded dataset: ' + aiState.filename;
                document.getElementById('ai-res-type').innerText = electrode;

                // Metrics bars — application-aware labels
                const barsContainer = document.getElementById('ai-res-bars');
                const freqBand = (aiState.apiData && aiState.apiData.freq_band) || 'N/A';
                const ampRange = (aiState.apiData && aiState.apiData.amp_range) || 'N/A';
                const sigLabel = (aiState.apiData && aiState.apiData.signal_label) || appType;
                const metrics = [
                    { label: 'Signal-to-Noise Ratio (SNR)', value: Math.min(100, snrNum * 5), display: actualSnr, color: '#8E24AA' },
                    { label: 'Noise Reduction Efficiency', value: noiseReduction, display: noiseReduction + '%', color: '#22C55E' },
                    { label: 'Signal Stability', value: stability, display: stability + '%', color: '#3B82F6' },
                    { label: 'Spatial Resolution', value: spatialRes, display: spatialRes + '%', color: '#F59E0B' },
                    { label: 'Freq. Band (' + sigLabel + ')', value: 100, display: freqBand, color: '#06B6D4' },
                    { label: 'Amplitude Range', value: 100, display: ampRange, color: '#EC4899' }
                ];
                barsContainer.innerHTML = metrics.map(m => `
                    <div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <span style="color:#E2E8F0; font-size:0.85rem;">${m.label}</span>
                            <span style="color:${m.color}; font-weight:700; font-size:0.85rem;">${m.display}</span>
                        </div>
                        <div style="height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden;">
                            <div style="height:100%; width:${Math.min(100, m.value)}%; background:${m.color}; border-radius:3px; transition:width 1.2s ease-out;"></div>
                        </div>
                    </div>
                `).join('');

                // Application-specific advantages
                const advantageMap = {
                    'EEG': isBipolar
                        ? ['Phase reversal detection for epileptic foci localization', 'Superior artifact rejection for long-term monitoring', 'Clean alpha/beta/theta band separation', `SNR of ${actualSnr} exceeds EEG analytical threshold`]
                        : ['True amplitude mapping across scalp regions', 'Simpler montage setup for routine screening', 'Full voltage capture for qEEG analysis', 'Broader spatial coverage per channel'],
                    'ECG': isBipolar
                        ? ['Clean P-QRS-T morphology with minimal baseline wander', 'Power-line interference elimination via CMRR', 'High analytical accuracy for arrhythmia detection (~96%)', `SNR of ${actualSnr} exceeds cardiac monitoring threshold`]
                        : ['Higher R-wave amplitude for reliable QRS detection', 'Augmented limb leads for frontal plane axis', 'Simpler 3-lead setup for continuous monitoring', 'Broader cardiac field coverage'],
                    'EMG': isBipolar
                        ? ['Cross-talk rejection from adjacent muscle groups', 'Precise motor unit action potential isolation', 'Ideal for conduction velocity studies', `SNR of ${actualSnr} supports educational EMG analysis`]
                        : ['Higher MUAP amplitude for needle EMG', 'Individual motor unit fiber analysis', 'Broader muscle activity coverage', 'Cost-effective for screening studies'],
                    'DBS': isBipolar
                        ? ['Focused stimulation with minimal current spread', 'Reduced side effects from off-target activation', 'Precise therapeutic window control', `SNR of ${actualSnr} for stable stimulation delivery`]
                        : ['Wider stimulation field for initial programming', 'Lower impedance for power efficiency', 'Broader neural target coverage', 'Simpler pulse generator configuration'],
                    'Nerve': isBipolar
                        ? ['Clean compound action potential isolation', 'Minimal far-field contamination', 'Precise conduction velocity measurement', `SNR of ${actualSnr} for reliable NCV calculations`]
                        : ['Higher absolute nerve potential amplitude', 'Broader neural generator detection', 'Simpler electrode placement', 'Suitable for screening NCS protocols']
                };
                const advantages = advantageMap[appType] || (isBipolar
                    ? ['Superior Common Mode Rejection (CMRR)', 'Clean waveforms with minimal baseline drift', 'High analytical accuracy', `SNR of ${actualSnr} exceeds educational threshold`]
                    : ['Simpler electrode setup & application', 'Higher absolute signal amplitude', 'Broader spatial coverage', 'Cost-effective standard equipment']);
                document.getElementById('ai-why-container').innerHTML = advantages.map(a =>
                    `<div class="ai-list-item" style="display:flex; gap:8px; align-items:flex-start;">
                        <span style="color:#22C55E; flex-shrink:0;">✓</span><span>${a}</span>
                    </div>`
                ).join('');

                // Application-specific considerations
                const consMap = {
                    'EEG': isBipolar
                        ? ['Reduced amplitude may miss low-voltage fast activity', 'End-of-chain electrodes lack differential pair', 'Requires precise 10-20 system placement', 'More complex interpretation for trainees']
                        : ['Susceptible to eye blink and EMG artifacts', `Lower SNR (${actualSnr}) in ICU/OR environments`, 'Reference contamination can distort topography', 'Greater baseline drift during long recordings'],
                    'ECG': isBipolar
                        ? ['Lower R-wave amplitude than unipolar leads', 'Limited to specific lead pair combinations', 'Requires precise limb lead positioning', 'Cannot provide precordial (V1-V6) data alone']
                        : ['Susceptible to 50/60Hz power-line interference', `Lower SNR (${actualSnr}) in ambulatory settings`, 'Motion artifacts during subject movement', 'Baseline wander during respiration'],
                    'EMG': isBipolar
                        ? ['Signal cancellation with closely spaced electrodes', 'Lower amplitude requires higher gain settings', 'Orientation-dependent signal capture', 'More complex electrode placement protocol']
                        : ['Cross-talk from adjacent muscles', `Lower SNR (${actualSnr}) in dynamic assessments`, 'Susceptible to movement artifacts', 'Reduced spatial selectivity'],
                    'DBS': isBipolar
                        ? ['Smaller therapeutic window requires precise targeting', 'Higher impedance increases battery drain', 'Limited volume of tissue activated', 'More sensitive to lead migration']
                        : ['Greater current spread may cause side effects', 'Higher stimulation thresholds possible', 'Less focal activation of target nucleus', 'More challenging parameter optimization'],
                    'Nerve': isBipolar
                        ? ['Short inter-electrode distance reduces amplitude', 'Precise placement along nerve path required', 'Temperature-sensitive conduction velocity', 'May miss slow C-fiber potentials']
                        : ['Far-field contamination from volume conduction', `Lower SNR (${actualSnr}) may obscure small CMAPs`, 'Stimulus artifact overlap at short distances', 'Reference electrode position-dependent']
                };
                const considerations = consMap[appType] || (isBipolar
                    ? ['Requires precise electrode placement', 'Lower absolute signal amplitude', 'Higher equipment cost', 'More complex setup protocol']
                    : ['Susceptible to electromagnetic interference', `Lower SNR (${actualSnr}) in noisy environments`, 'Greater baseline drift & motion artifacts', 'Reduced spatial precision']);
                document.getElementById('ai-cons-container').innerHTML = considerations.map(c =>
                    `<div class="ai-list-item" style="display:flex; gap:8px; align-items:flex-start;">
                        <span style="color:#F59E0B; flex-shrink:0;">⚠</span><span>${c}</span>
                    </div>`
                ).join('');

                // Signal Quality Analysis section
                const qualityDiv = document.getElementById('ai-quality-analysis');
                if (qualityDiv) {
                    const qualityLevel = snrNum > 15 ? 'Excellent' : (snrNum > 8 ? 'Good' : (snrNum > 4 ? 'Fair' : 'Poor'));
                    const qualityColor = snrNum > 15 ? '#22C55E' : (snrNum > 8 ? '#3B82F6' : (snrNum > 4 ? '#F59E0B' : '#EF4444'));
                    
                    let conditionHtml = '';
                    if (aiState.apiData && aiState.apiData.clinical_condition) {
                        const conditionObj = aiState.apiData.clinical_condition;
                        conditionHtml = `
                            <div style="margin-top:15px; padding:20px; background:rgba(255,255,255,0.02); border-radius:14px; border:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; gap:20px; border-left: 4px solid ${conditionObj.color};">
                                <div style="width:16px; height:16px; border-radius:50%; background:${conditionObj.color}; box-shadow:0 0 12px ${conditionObj.color};"></div>
                                <div>
                                    <h4 style="color:#fff; margin:0 0 5px 0; font-size:1.1rem;">${conditionObj.title}</h4>
                                    <p style="color:#94A3B8; margin:0; font-size:0.9rem;">${conditionObj.desc}</p>
                                </div>
                            </div>
                        `;
                    }

                    let morphHtml = '';
                    if (aiState.apiData && aiState.apiData.morphological_features) {
                        const m = aiState.apiData.morphological_features;
                        const features = [
                            { label: 'MAV', value: m.mav, icon: '📏' },
                            { label: 'RMS', value: m.rms, icon: '📶' },
                            { label: 'Std Dev', value: m.std_dev, icon: '📉' },
                            { label: 'Energy', value: m.energy, icon: '🧨' },
                            { label: 'Peak-to-Peak', value: m.peak_to_peak, icon: '↕️' },
                            { label: 'Zero Crossing', value: m.zcr, icon: '🔀' }
                        ];
                        morphHtml = `
                            <div style="margin-top:20px;">
                                <h4 style="color:#fff; font-size:0.9rem; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                                    <span style="color:#CE93D8;">🧬</span> Morphological Features
                                </h4>
                                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
                                    ${features.map(f => `
                                        <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.05); padding:12px; border-radius:10px; text-align:center;">
                                            <div style="font-size:0.65rem; color:#94A3B8; text-transform:uppercase; margin-bottom:4px;">${f.icon} ${f.label}</div>
                                            <div style="font-size:1rem; font-weight:700; color:#fff;">${f.value}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    qualityDiv.innerHTML = `
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-top:15px;">
                            <div style="text-align:center; padding:20px; background:rgba(255,255,255,0.02); border-radius:14px; border:1px solid rgba(255,255,255,0.06);">
                                <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-bottom:8px;">Quality</div>
                                <div style="font-size:1.4rem; font-weight:800; color:${qualityColor};">${qualityLevel}</div>
                            </div>
                            <div style="text-align:center; padding:20px; background:rgba(255,255,255,0.02); border-radius:14px; border:1px solid rgba(255,255,255,0.06);">
                                <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-bottom:8px;">SNR</div>
                                <div style="font-size:1.4rem; font-weight:800; color:#CE93D8;">${actualSnr}</div>
                            </div>
                            <div style="text-align:center; padding:20px; background:rgba(255,255,255,0.02); border-radius:14px; border:1px solid rgba(255,255,255,0.06);">
                                <div style="font-size:0.7rem; color:#94A3B8; text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-bottom:8px;">Technique</div>
                                <div style="font-size:1.4rem; font-weight:800; color:#fff;">${electrode}</div>
                            </div>
                        </div>
                        ${conditionHtml}
                        ${morphHtml}
                    `;
                }

                // Fidelity card
                const fidelityCard = document.getElementById('ai-fidelity-card');
                if (fidelityCard) {
                    fidelityCard.style.display = 'flex';
                    let fScore = Math.round(Math.min(99, snrNum * 5));
                    if (snrNum > 15) fScore = Math.round(85 + (snrNum / 30) * 14);
                    document.getElementById('ai-fidelity-score-bubble').innerText = fScore;
                    document.getElementById('ai-fidelity-desc').innerText =
                        snrNum < 5 ? 'Low-quality signal detected. Consider adjusting parameters.' :
                        snrNum < 15 ? 'Moderate signal fidelity. Acceptable for general analyticals.' :
                        'High-quality differential signal detected. SNR is within optimal analytical parameters.';
                }

                // Draw signal on canvas (Animated!)
                const graphContainer = document.getElementById('ai-graph-container');
                if (graphContainer && amplitudes.length > 0) {
                    graphContainer.style.display = 'block';
                    const canvas = document.getElementById('aiSignalCanvas');
                    if (canvas) {
                        const ctx = canvas.getContext('2d');
                        let offset = 0;
                        function animateAiSignal() {
                            if (document.getElementById('ai-results-view').style.display === 'none') return;
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            
                            const step = canvas.width / 150;
                            const min = Math.min(...amplitudes);
                            const max = Math.max(...amplitudes);
                            const range = (max - min) || 1;

                            // Draw Clean Trace
                            ctx.beginPath();
                            ctx.strokeStyle = isBipolar ? '#8E24AA' : '#F59E0B';
                            ctx.lineWidth = 2;
                            for (let i = 0; i < 150; i++) {
                                const idx = (i + offset) % cleanSignal.length;
                                const x = i * step;
                                const y = canvas.height - ((cleanSignal[idx] - min) / range) * (canvas.height - 40) - 20;
                                i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                            }
                            ctx.stroke();

                            // Draw Raw/Noisy Overlay
                            ctx.beginPath();
                            ctx.strokeStyle = 'rgba(239,68,68,0.2)';
                            ctx.lineWidth = 1;
                            for (let i = 0; i < 150; i++) {
                                const idx = (i + offset) % amplitudes.length;
                                const x = i * step;
                                const y = canvas.height - ((amplitudes[idx] - min) / range) * (canvas.height - 40) - 20;
                                i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                            }
                            ctx.stroke();

                            offset = (offset + 1) % cleanSignal.length;
                            requestAnimationFrame(animateAiSignal);
                        }
                        animateAiSignal();
                    }
                }

                // SAVE to local storage for persistence across page reloads
                localStorage.setItem('last_ai_analysis', JSON.stringify({
                    apiData: aiState.apiData,
                    electrode: aiState.electrode,
                    noise: aiState.noise,
                    appInfo: aiState.appInfo,
                    actualSnr: actualSnr
                }));

                // Update Signal Quality page elements
                syncSignalQualityData();
            }



            function resetAiSystem() {
                document.getElementById('ai-results-view').style.display = 'none';
                document.getElementById('ai-progress-view').style.display = 'none';
                document.getElementById('ai-config-view').style.display = 'block';
                document.getElementById('ai-progress-bar').style.width = "0%";
                
                // Keep the generated or uploaded dataset, just resent view
            }

            // Automatically initialize the dataset on load
            document.addEventListener('DOMContentLoaded', () => {
                if(document.getElementById('aiAppSelector')) {
                    autoFillDataset();
                }
                // SYNC Signal Quality page data if user just navigated here
                syncSignalQualityData();
            });
        </script>


    <?php elseif ($page === 'simulator'): // ═══ INTERACTIVE SIMULATOR ═══ ?>

        <!-- Hero Header -->
        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(255,152,0,0.12), rgba(255,87,34,0.08)); border:1px solid rgba(255,152,0,0.25);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(255,152,0,0.2); border-color:#FF9800;">🖥️</div>
            <div class="hero-text">
                <h2>Interactive Signal Simulator</h2>
                <p style="color:#FFCC80;">Experiment with electrode parameters and visualise real-time signal output</p>
            </div>
        </div>

        <div style="max-width:1100px;">

        <!-- ── Recording Type Selector ── -->
        <div class="detail-card" style="margin-bottom:24px;">
            <div class="panel-title" style="color:#FFCC80; margin-bottom:16px;">⚡ Recording Type</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" id="recTypeGrid">

                <div id="btnBipolar" onclick="selectRecordingType('bipolar')" style="cursor:pointer; border:2px solid #1976D2; background:rgba(25,118,210,0.1); border-radius:16px; padding:20px; text-align:center; transition:0.3s;">
                    <div style="font-size:2rem; margin-bottom:8px;">🧬</div>
                    <div style="font-weight:800; color:#64B5F6; font-size:1rem;">Bipolar</div>
                    <div style="font-size:0.75rem; color:#90CAF9; margin-top:4px;">Better Noise Rejection</div>
                </div>

                <div id="btnMonopolar" onclick="selectRecordingType('monopolar')" style="cursor:pointer; border:2px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); border-radius:16px; padding:20px; text-align:center; transition:0.3s;">
                    <div style="font-size:2rem; margin-bottom:8px;">📡</div>
                    <div style="font-weight:800; color:#94A3B8; font-size:1rem;">Monopolar</div>
                    <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">Ample Signal Data</div>
                </div>
            </div>
        </div>

        <!-- ── Simulation Parameters ── -->
        <div class="detail-card" style="margin-bottom:24px;">
            <div class="panel-title" style="color:#FFCC80; margin-bottom:20px;">🔧 Simulation Parameters</div>

            <!-- Noise Level -->
            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:#fff; font-weight:700; font-size:0.9rem;">Noise Level</span>
                    <span id="noiseVal" style="color:#FF9800; font-weight:800; font-size:0.9rem;">20%</span>
                </div>
                <input type="range" id="sliderNoise" min="0" max="100" value="20" oninput="onSliderChange()" style="width:100%; --track-color:#FF9800;">
                <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">Simulates environmental electrical interference from nearby devices</div>
            </div>

            <!-- Electrode Distance -->
            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:#fff; font-weight:700; font-size:0.9rem;">Electrode Distance</span>
                    <span id="distVal" style="color:#00897B; font-weight:800; font-size:0.9rem;">50 mm</span>
                </div>
                <input type="range" id="sliderDist" min="10" max="100" value="50" oninput="onSliderChange()" style="width:100%; --track-color:#00897B;">
                <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">Distance between electrodes affects signal amplitude and resolution</div>
            </div>

            <!-- Skin Impedance -->
            <div style="margin-bottom:20px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:#fff; font-weight:700; font-size:0.9rem;">Skin Impedance</span>
                    <span id="impVal" style="color:#7B1FA2; font-weight:800; font-size:0.9rem;">10 kΩ</span>
                </div>
                <input type="range" id="sliderImp" min="1" max="50" value="10" oninput="onSliderChange()" style="width:100%; --track-color:#7B1FA2;">
                <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">Higher skin impedance degrades signal quality and increases noise</div>
            </div>

            <!-- Muscle Artifact -->
            <div style="margin-bottom:24px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <span style="color:#fff; font-weight:700; font-size:0.9rem;">Muscle Artifact</span>
                    <span id="artVal" style="color:#D32F2F; font-weight:800; font-size:0.9rem;">10%</span>
                </div>
                <input type="range" id="sliderArt" min="0" max="100" value="10" oninput="onSliderChange()" style="width:100%; --track-color:#D32F2F;">
                <div style="font-size:0.75rem; color:#64748B; margin-top:4px;">Simulates high-frequency EMG interference from muscle movement</div>
            </div>

            <!-- Quick Presets -->
            <div style="margin-bottom:8px;">
                <div style="font-size:0.8rem; color:#94A3B8; font-weight:700; margin-bottom:10px; text-transform:uppercase; letter-spacing:1px;">Quick Presets</div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
                    <button onclick="applyPreset('ideal')" style="padding:10px; border-radius:12px; border:none; background:rgba(46,125,50,0.15); color:#4CAF50; font-weight:700; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='rgba(46,125,50,0.3)'" onmouseout="this.style.background='rgba(46,125,50,0.15)'">✅ Ideal</button>
                    <button onclick="applyPreset('moderate')" style="padding:10px; border-radius:12px; border:none; background:rgba(245,127,23,0.15); color:#FF9800; font-weight:700; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='rgba(245,127,23,0.3)'" onmouseout="this.style.background='rgba(245,127,23,0.15)'">⚠️ Moderate</button>
                    <button onclick="applyPreset('challenging')" style="padding:10px; border-radius:12px; border:none; background:rgba(198,40,40,0.15); color:#EF5350; font-weight:700; cursor:pointer; transition:0.2s;" onmouseover="this.style.background='rgba(198,40,40,0.3)'" onmouseout="this.style.background='rgba(198,40,40,0.15)'">🔴 Challenging</button>
                </div>
            </div>
        </div>

        <!-- ── Signal Output (Canvas Graph) ── -->
        <div class="detail-card" style="margin-bottom:24px;">
            <div class="panel-title" style="color:#FFCC80; margin-bottom:16px;">📈 Signal Output</div>
            <div style="position:relative; background:#0A0E21; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.06);">
                <canvas id="signalCanvas" width="900" height="180" style="width:100%; display:block;"></canvas>
                <!-- Overlay: recording type badge -->
                <div id="recBadge" style="position:absolute; top:10px; right:14px; background:rgba(0,0,0,0.6); color:#fff; font-size:9px; font-weight:800; padding:3px 9px; border-radius:20px; letter-spacing:1px; text-transform:uppercase; display:none;"></div>
                <!-- Overlay: connecting spinner -->
                <div id="connectOverlay" style="display:none; position:absolute; inset:0; background:rgba(0,0,0,0.7); display:none; align-items:center; justify-content:center; flex-direction:column; gap:12px;">
                    <div style="width:36px; height:36px; border:3px solid rgba(255,255,255,0.1); border-top-color:#4FC3F7; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                    <div style="color:#fff; font-size:12px;">Connecting to cloud computing...</div>
                </div>
            </div>

            <!-- Stats Row -->
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-top:16px;">
                <div id="statSNR" style="background:rgba(25,118,210,0.08); border:1px solid rgba(25,118,210,0.2); border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:0.7rem; color:#1565C0; font-weight:800; text-transform:uppercase; letter-spacing:1px;">SNR</div>
                    <div id="snrVal" style="font-size:1.3rem; font-weight:800; color:#0D47A1; margin-top:4px;">12.0:1</div>
                </div>
                <div id="statQuality" style="background:rgba(27,94,32,0.08); border:1px solid rgba(27,94,32,0.2); border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:0.7rem; color:#2E7D32; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Quality</div>
                    <div id="qualVal" style="font-size:1.3rem; font-weight:800; color:#1B5E20; margin-top:4px;">High</div>
                </div>
                <div id="statAmp" style="background:rgba(74,20,140,0.08); border:1px solid rgba(74,20,140,0.2); border-radius:12px; padding:12px; text-align:center;">
                    <div style="font-size:0.7rem; color:#6A1B9A; font-weight:800; text-transform:uppercase; letter-spacing:1px;">Amplitude</div>
                    <div id="ampVal" style="font-size:1.3rem; font-weight:800; color:#4A148C; margin-top:4px;">50µV</div>
                </div>
            </div>
        </div>

        <!-- ── Action Buttons ── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
            <button onclick="runSimulation()" style="padding:18px; border-radius:20px; border:none; background:linear-gradient(135deg,#FF9800,#F57C00); color:#fff; font-weight:800; font-size:1rem; cursor:pointer; box-shadow:0 10px 25px rgba(255,152,0,0.3); transition:0.3s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                ▶ Run Simulation
            </button>
            <button onclick="resetSimulation()" style="padding:18px; border-radius:20px; border:2px solid rgba(255,255,255,0.15); background:rgba(255,255,255,0.04); color:#fff; font-weight:800; font-size:1rem; cursor:pointer; transition:0.3s;" onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='rgba(255,255,255,0.04)'">
                ↺ Reset Parameters
            </button>
        </div>

        <!-- ── AI Analysis ── -->
        <div class="detail-card" style="margin-bottom:24px; background:linear-gradient(135deg, rgba(123,31,162,0.15), rgba(74,20,140,0.1)); border:1px solid rgba(123,31,162,0.3);">
            <div style="display:flex; gap:16px; align-items:flex-start;">
                <div style="font-size:2rem; flex-shrink:0;">🤖</div>
                <div>
                    <div style="font-weight:800; color:#fff; margin-bottom:8px; font-size:1rem;">AI Analysis</div>
                    <div id="aiAnalysisText" style="color:#E1BEE7; font-size:0.9rem; line-height:1.6;">Excellent conditions! Bipolar configuration with low noise provides the highest quality SNR (12.0:1). Ideal for precise measurements.</div>
                </div>
            </div>
        </div>

        <!-- ── Performance Comparison ── -->
        <div class="detail-card" style="margin-bottom:24px;">
            <div class="panel-title" style="color:#FFCC80; margin-bottom:20px;">📊 Performance Comparison</div>

            <div style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <span style="color:#fff; font-size:0.9rem;">Noise Rejection (CMRR)</span>
                    <span id="noiseRejVal" style="color:#fff; font-weight:700; font-size:0.9rem;">100+</span>
                </div>
                <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                    <div id="pbNoiseRej" style="height:100%; width:90%; background:#4CAF50; border-radius:10px; transition:width 0.8s ease, background 0.5s ease;"></div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <span style="color:#fff; font-size:0.9rem;">Spatial Resolution</span>
                    <span id="spatResVal" style="color:#fff; font-weight:700; font-size:0.9rem;">High</span>
                </div>
                <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                    <div id="pbSpatRes" style="height:100%; width:85%; background:#1976D2; border-radius:10px; transition:width 0.8s ease, background 0.5s ease;"></div>
                </div>
            </div>

            <div>
                <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                    <span style="color:#fff; font-size:0.9rem;">Signal Amplitude</span>
                    <span id="sigAmpVal" style="color:#fff; font-weight:700; font-size:0.9rem;">Lower</span>
                </div>
                <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                    <div id="pbSigAmp" style="height:100%; width:35%; background:#1976D2; border-radius:10px; transition:width 0.8s ease, background 0.5s ease;"></div>
                </div>
            </div>
        </div>

        <!-- ── ECG Learning Resources ── -->
        <div class="detail-card" style="margin-bottom:24px; background:linear-gradient(135deg, rgba(0,137,123,0.12), rgba(0,121,107,0.08)); border:1px solid rgba(0,137,123,0.25);">
            <div class="panel-title" style="color:#80CBC4; margin-bottom:16px;">📚 ECG Learning Resources</div>

            <div style="display:grid; gap:12px;">
                <div style="background:rgba(15,23,42,0.8); border-radius:14px; padding:16px; border:1px solid rgba(255,255,255,0.06);">
                    <div style="font-weight:700; color:#fff; font-size:0.9rem; margin-bottom:8px;">ECG Guide: Sinus Rhythm</div>
                    <div style="background:rgba(46,125,50,0.15); color:#81C784; font-size:0.75rem; padding:8px 12px; border-radius:8px; margin-bottom:8px;">Features Heart Rhythm Guide, ECG waveform analysis, educational interpretation</div>
                    <div style="color:#64748B; font-size:0.75rem;">Normal heart rhythm at 60 BPM with standard PQRST morphology and sinus node origin</div>
                </div>

                <div style="background:rgba(15,23,42,0.8); border-radius:14px; padding:16px; border:1px solid rgba(255,255,255,0.06);">
                    <div style="font-weight:700; color:#fff; font-size:0.9rem; margin-bottom:8px;">DART Sim Pro – Medical Simulator</div>
                    <div style="background:rgba(0,105,92,0.15); color:#4DB6AC; font-size:0.75rem; padding:8px 12px; border-radius:8px; margin-bottom:8px;">Features 12-lead ECG, BP, SpO₂, drug interactions, multi-subject scenarios</div>
                    <div style="color:#64748B; font-size:0.75rem;">Professional-grade simulator for educational training and emergency medicine practice</div>
                </div>

                <div style="background:rgba(15,23,42,0.8); border-radius:14px; padding:16px; border:1px solid rgba(255,255,255,0.06);">
                    <div style="font-weight:700; color:#fff; font-size:0.9rem; margin-bottom:8px;">ECG Learning App</div>
                    <div style="background:rgba(130,119,23,0.15); color:#DCE775; font-size:0.75rem; padding:8px 12px; border-radius:8px; margin-bottom:8px;">Features interactive learning modules, rhythm recognition, case studies</div>
                    <div style="color:#64748B; font-size:0.75rem;">Mobile platform for mastering cardiac electrophysiology from beginner to advanced</div>
                </div>
            </div>
        </div>

        <!-- ── Learning Tip ── -->
        <div style="background:rgba(13,71,161,0.08); border:1px solid rgba(13,71,161,0.25); border-radius:16px; padding:16px; display:flex; gap:12px; align-items:flex-start; margin-bottom:32px;">
            <div style="font-size:1.3rem; flex-shrink:0;">💡</div>
            <div>
                <div style="font-weight:800; color:#1565C0; font-size:0.85rem; margin-bottom:4px;">Learning Tip</div>
                <div id="learningTip" style="color:#1976D2; font-size:0.85rem; line-height:1.5;">These core simulation principles show how bipolar and monopolar recordings are applied in educational settings.</div>
            </div>
        </div>

        </div><!-- /max-width wrapper -->

        <!-- ═══ SIMULATOR STYLES ═══ -->
        <style>
            input[type=range] {
                -webkit-appearance: none;
                appearance: none;
                width: 100%;
                height: 6px;
                border-radius: 6px;
                background: rgba(255,255,255,0.08);
                outline: none;
                cursor: pointer;
            }
            input[type=range]::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 18px;
                height: 18px;
                border-radius: 50%;
                background: var(--track-color, #FF9800);
                box-shadow: 0 0 8px var(--track-color, #FF9800);
                cursor: pointer;
                transition: 0.2s;
            }
            input[type=range]::-webkit-slider-thumb:hover { transform: scale(1.2); }
            input[type=range]::-webkit-slider-runnable-track {
                background: linear-gradient(to right, var(--track-color, #FF9800) var(--val, 0%), rgba(255,255,255,0.08) var(--val, 0%));
                border-radius: 6px;
                height: 6px;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
        </style>

        <!-- ═══ SIMULATOR JAVASCRIPT ═══ -->
        <script>
        (function(){
            // ── State ──────────────────────────────────────────
            let isBipolar = true;
            let phase = 0;
            let animFrame;
            let simRunning = false;
            let simParams = { noise: 0.2, freq: 1.25, artifact: 0.1 };

            // ── Canvas setup ───────────────────────────────────
            const canvas = document.getElementById('signalCanvas');
            const ctx    = canvas.getContext('2d');

            function resizeCanvas() {
                const ratio = window.devicePixelRatio || 1;
                const rect  = canvas.getBoundingClientRect();
                canvas.width  = rect.width  * ratio;
                canvas.height = rect.height * ratio;
                ctx.scale(ratio, ratio);
            }
            window.addEventListener('resize', () => { resizeCanvas(); });
            resizeCanvas();

            // ── Draw waveform ──────────────────────────────────
            function drawSignal() {
                const rect = canvas.getBoundingClientRect();
                const W = rect.width, H = rect.height;
                ctx.clearRect(0, 0, W, H);

                // Grid
                ctx.strokeStyle = 'rgba(255,255,255,0.04)';
                ctx.lineWidth = 1;
                for (let x = 0; x < W; x += 40) { ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,H); ctx.stroke(); }
                for (let y = 0; y < H; y += 30) { ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(W,y); ctx.stroke(); }

                const cy = H / 2;
                const { noise, freq, artifact } = simParams;

                if (isBipolar) {
                    drawWave(ctx, W, H, cy, '#4FC3F7', phase, noise, freq, artifact, 80, false);
                } else {
                    drawWave(ctx, W, H, cy, '#FF5722', phase,          noise, freq, artifact, 90, true);
                    drawWave(ctx, W, H, cy, '#00BFA5', phase + Math.PI/1.5, noise * 0.5, freq, artifact * 0.5, 75, true, true);
                }

                phase += 0.04;
                if (simRunning) { animFrame = requestAnimationFrame(drawSignal); }
            }

            function drawWave(ctx, W, H, cy, color, ph, noise, freq, artifact, amp, monopolar, secondary = false) {
                ctx.beginPath();
                ctx.strokeStyle = color;
                ctx.lineWidth = secondary ? 2 : 3;
                ctx.shadowColor = color;
                ctx.shadowBlur  = secondary ? 4 : 8;

                const noiseMultiplier = monopolar ? (secondary ? 1.0 : 2.5) : 1.0;
                const artMultiplier   = monopolar ? (secondary ? 0.5 : 2.0) : 1.0;

                for (let i = 0; i <= W; i += 3) {
                    const x = i;
                    let y = cy + amp * Math.sin((x / W) * 20 * Math.PI * freq + ph);

                    if (noise > 0) {
                        y += 30 * noiseMultiplier * Math.sin((x / W) * 5 * Math.PI + ph * 0.5) * noise;
                        y += (Math.random() - 0.5) * 15 * noise * noiseMultiplier;
                    }
                    if (artifact > 0) {
                        y += (Math.random() - 0.5) * 80 * artifact * artMultiplier;
                    }

                    y = Math.max(5, Math.min(H - 5, y));
                    i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
                }
                ctx.stroke();
                ctx.shadowBlur = 0;
            }

            // ── Start/Stop animation ──────────────────────────
            function startAnimation() {
                if (simRunning) return;
                simRunning = true;
                drawSignal();
            }
            function stopAnimation() {
                simRunning = false;
                cancelAnimationFrame(animFrame);
            }

            // ── Recording type selection ──────────────────────
            window.selectRecordingType = function(type) {
                isBipolar = (type === 'bipolar');
                const bipolarBtn   = document.getElementById('btnBipolar');
                const monopolarBtn = document.getElementById('btnMonopolar');
                if (isBipolar) {
                    bipolarBtn.style.border   = '2px solid #1976D2';
                    bipolarBtn.style.background = 'rgba(25,118,210,0.15)';
                    bipolarBtn.querySelector('div:nth-child(2)').style.color = '#64B5F6';
                    bipolarBtn.querySelector('div:nth-child(3)').style.color = '#90CAF9';
                    monopolarBtn.style.border   = '2px solid rgba(255,255,255,0.1)';
                    monopolarBtn.style.background = 'rgba(255,255,255,0.03)';
                    monopolarBtn.querySelector('div:nth-child(2)').style.color = '#94A3B8';
                    monopolarBtn.querySelector('div:nth-child(3)').style.color = '#64748B';
                    applyBipolarDefaults();
                } else {
                    monopolarBtn.style.border   = '2px solid #00897B';
                    monopolarBtn.style.background = 'rgba(0,137,123,0.15)';
                    monopolarBtn.querySelector('div:nth-child(2)').style.color = '#4DB6AC';
                    monopolarBtn.querySelector('div:nth-child(3)').style.color = '#80CBC4';
                    bipolarBtn.style.border   = '2px solid rgba(255,255,255,0.1)';
                    bipolarBtn.style.background = 'rgba(255,255,255,0.03)';
                    bipolarBtn.querySelector('div:nth-child(2)').style.color = '#94A3B8';
                    bipolarBtn.querySelector('div:nth-child(3)').style.color = '#64748B';
                    applyMonopolarDefaults();
                }
                updateSliders();
                updateStats();
                updatePerformanceBars();
                updateAI();
            };

            function applyBipolarDefaults() {
                setSliderVals(20, 50, 10, 10);
            }
            function applyMonopolarDefaults() {
                setSliderVals(80, 20, 35, 60);
            }
            function setSliderVals(noise, dist, imp, art) {
                document.getElementById('sliderNoise').value = noise;
                document.getElementById('sliderDist').value  = dist;
                document.getElementById('sliderImp').value   = imp;
                document.getElementById('sliderArt').value   = art;
            }

            // ── Slider change ─────────────────────────────────
            window.onSliderChange = function() {
                updateSliders();
                updateStats();
                updatePerformanceBars();
                updateAI();
            };

            function updateSliders() {
                const noise = +document.getElementById('sliderNoise').value;
                const dist  = +document.getElementById('sliderDist').value;
                const imp   = +document.getElementById('sliderImp').value;
                const art   = +document.getElementById('sliderArt').value;

                document.getElementById('noiseVal').textContent = noise + '%';
                document.getElementById('distVal').textContent  = dist + ' mm';
                document.getElementById('impVal').textContent   = imp + ' kΩ';
                document.getElementById('artVal').textContent   = art + '%';

                const noiseFactor = isBipolar ? (noise / 100) * 0.4 : noise / 100;
                const freqFactor  = 1.0 + (dist / 200);
                const artFactor   = art / 100;

                simParams = { noise: noiseFactor, freq: freqFactor, artifact: artFactor };
            }

            // ── Stats update ───────────────────────────────────
            function updateStats() {
                const noise = +document.getElementById('sliderNoise').value;
                const dist  = +document.getElementById('sliderDist').value;
                const imp   = +document.getElementById('sliderImp').value;
                const art   = +document.getElementById('sliderArt').value;

                const base = isBipolar ? 120 : 100;
                const impPenalty = isBipolar ? imp * 0.1 : imp * 0.5;
                const snrLevel = base - noise - impPenalty - art * 0.4;
                const snr = Math.max(1, snrLevel / 10).toFixed(1);
                document.getElementById('snrVal').textContent = snr + ':1';

                const qualEl = document.getElementById('qualVal');
                const statQ  = document.getElementById('statQuality');
                if (snrLevel > 80) {
                    qualEl.textContent = 'High';
                    qualEl.style.color = '#1B5E20';
                    statQ.style.background = 'rgba(27,94,32,0.08)';
                    statQ.style.borderColor = 'rgba(27,94,32,0.25)';
                } else if (snrLevel > 40) {
                    qualEl.textContent = 'Medium';
                    qualEl.style.color = '#E65100';
                    statQ.style.background = 'rgba(245,127,23,0.08)';
                    statQ.style.borderColor = 'rgba(245,127,23,0.25)';
                } else {
                    qualEl.textContent = 'Low';
                    qualEl.style.color = '#B71C1C';
                    statQ.style.background = 'rgba(183,28,28,0.08)';
                    statQ.style.borderColor = 'rgba(183,28,28,0.25)';
                }
                document.getElementById('ampVal').textContent = (dist + (isBipolar ? 0 : 10)) + 'µV';
            }

            // ── Performance bars ───────────────────────────────
            function updatePerformanceBars() {
                const noise = +document.getElementById('sliderNoise').value;
                const noiseRej  = isBipolar ? 90 : Math.max(10, 100 - noise);
                const spatRes   = isBipolar ? 85 : 20;
                const sigAmp    = isBipolar ? 35 : 80;

                document.getElementById('pbNoiseRej').style.width  = noiseRej + '%';
                document.getElementById('pbSpatRes').style.width   = spatRes + '%';
                document.getElementById('pbSigAmp').style.width    = sigAmp + '%';
                document.getElementById('pbNoiseRej').style.background = isBipolar ? '#4CAF50' : '#FF9800';

                document.getElementById('noiseRejVal').textContent = isBipolar ? '100+ CMRR' : noiseRej + ' CMRR';
                document.getElementById('spatResVal').textContent  = isBipolar ? 'High' : 'Low';
                document.getElementById('sigAmpVal').textContent   = isBipolar ? 'Lower' : 'Higher';
            }

            // ── AI Analysis text ───────────────────────────────
            function updateAI() {
                const noise = +document.getElementById('sliderNoise').value;
                const el = document.getElementById('aiAnalysisText');
                const tip = document.getElementById('learningTip');
                if (isBipolar) {
                    if (noise < 30) {
                        el.textContent = 'Excellent conditions! Bipolar configuration with low noise provides the highest quality SNR. Ideal for precise measurements.';
                    } else if (noise < 60) {
                        el.textContent = 'Good setup. Bipolar recording handles moderate noise well through common-mode rejection. Consider reducing impedance for better clarity.';
                    } else {
                        el.textContent = '⚠️ High noise detected but bipolar CMRR is helping significantly. Lower noise source or increase inter-electrode distance.';
                    }
                    tip.textContent = 'These core simulation principles show how bipolar recordings are applied in educational settings with superior noise rejection.';
                } else {
                    if (noise < 30) {
                        el.textContent = 'Monopolar recording in quiet conditions. Amplitude is higher than bipolar. Suitable for controlled lab environments.';
                    } else {
                        el.textContent = '⚠️ Challenging conditions! High noise significantly affects monopolar recording. Consider switching to bipolar for better noise rejection.';
                    }
                    tip.textContent = 'Please note that in most educational setups, monopolar recordings are susceptible to environmental noise. Use in shielded environments.';
                }
            }

            // ── Presets ────────────────────────────────────────
            window.applyPreset = function(preset) {
                if (preset === 'ideal') {
                    setSliderVals(5, 30, 5, 0);
                } else if (preset === 'moderate') {
                    setSliderVals(40, 60, 20, 30);
                } else {
                    setSliderVals(80, 90, 40, 80);
                }
                onSliderChange();
            };

            // ── Run Simulation ────────────────────────────────
            window.runSimulation = function() {
                const overlay = document.getElementById('connectOverlay');
                const badge   = document.getElementById('recBadge');
                overlay.style.display = 'flex';
                stopAnimation();
                setTimeout(() => {
                    overlay.style.display = 'none';
                    badge.textContent = isBipolar ? 'BIPOLAR RECORDING' : 'MONOPOLAR RECORDING';
                    badge.style.display = 'block';
                    startAnimation();
                }, 2000);
            };

            // ── Reset ─────────────────────────────────────────
            window.resetSimulation = function() {
                if (isBipolar) applyBipolarDefaults(); else applyMonopolarDefaults();
                document.getElementById('recBadge').style.display = 'none';
                onSliderChange();
                stopAnimation();
                // Static single frame
                updateSliders();
                drawSignal();
                simRunning = false;
            };

            // ── Init ──────────────────────────────────────────
            updateSliders();
            updateStats();
            updatePerformanceBars();
            updateAI();
            // Draw a static frame on load
            drawSignal();
            simRunning = false;

        })();
        </script>

    <?php elseif ($page === 'pros_cons'): // ═══ PROS & CONS ANALYSIS ═══ ?>


        <a href="dashboard.php?page=compare" class="back-btn mb-16">← Back to Comparison</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(5,150,105,0.1), rgba(37,99,235,0.1)); border:1px solid rgba(5,150,105,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(5,150,105,0.2); border-color:#10B981;">⚖️</div>
            <div class="hero-text">
                <h2>Pros & Cons Analysis</h2>
                <p style="color:#A7F3D0;">Detailed advantages & disadvantages of recording modes</p>
            </div>
        </div>

        <div style="max-width:1000px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px;">
                
                <!-- Bipolar Pros & Cons -->
                <div class="detail-card" style="border-left:5px solid #3B82F6; background:rgba(37,99,235,0.02);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div>
                            <h3 style="color:#60A5FA; margin:0; font-size:1.4rem;">Bipolar Recording</h3>
                            <span style="font-size:0.8rem; color:#94A3B8;">Differential Technique</span>
                        </div>
                        <div style="padding:10px; background:rgba(37,99,235,0.1); border-radius:12px;">🧬</div>
                    </div>

                    <div style="background:#0D1224; border-radius:15px; margin-bottom:20px; border:1px solid rgba(37,99,235,0.2); overflow:hidden;">
                        <img src="images/img_bipolar_placement.png" alt="Bipolar Setup" style="width:100%; height:150px; object-fit:cover;">
                    </div>

                    <div style="margin-bottom:25px;">
                        <h4 style="color:#10B981; font-size:1rem; margin-bottom:12px; display:flex; align-items:center; gap:8px;">✅ Advantages</h4>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Superior Noise Rejection:</strong> CMRR eliminates ambient noise.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>High Resolution:</strong> Precise activity localization.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Cleaner Waveforms:</strong> Minimal baseline drift.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Accuracy:</strong> ~94% analytical success rate.</div>
                    </div>

                    <div>
                        <h4 style="color:#EF4444; font-size:1rem; margin-bottom:12px; display:flex; align-items:center; gap:8px;">❌ Disadvantages</h4>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Complex setup & precise placement required.</div>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Lower signal amplitude (differential).</div>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Higher equipment & training costs.</div>
                    </div>
                </div>

                <!-- Monopolar Pros & Cons -->
                <div class="detail-card" style="border-left:5px solid #14B8A6; background:rgba(20,184,166,0.02);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div>
                            <h3 style="color:#2DD4BF; margin:0; font-size:1.4rem;">Monopolar Recording</h3>
                            <span style="font-size:0.8rem; color:#94A3B8;">Referential Technique</span>
                        </div>
                        <div style="padding:10px; background:rgba(20,184,166,0.1); border-radius:12px;">📡</div>
                    </div>

                    <div style="background:#0D1224; border-radius:15px; margin-bottom:20px; border:1px solid rgba(20,184,166,0.2); overflow:hidden;">
                        <img src="images/img_monopolar_placement.png" alt="Monopolar Setup" style="width:100%; height:150px; object-fit:cover;">
                    </div>

                    <div style="margin-bottom:25px;">
                        <h4 style="color:#10B981; font-size:1rem; margin-bottom:12px; display:flex; align-items:center; gap:8px;">✅ Advantages</h4>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Simple Setup:</strong> Fast application with one active.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>High Amplitude:</strong> Full absolute potential capture.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Broad Coverage:</strong> Comprehensive spatial capture.</div>
                        <div class="educational-item"><div class="educational-dot" style="background:#10B981;"></div><strong>Cost Effective:</strong> Affordable standard equipment.</div>
                    </div>

                    <div>
                        <h4 style="color:#EF4444; font-size:1rem; margin-bottom:12px; display:flex; align-items:center; gap:8px;">❌ Disadvantages</h4>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Poor noise rejection (susceptible to EMI).</div>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Lower accuracy (~78%) in noisy settings.</div>
                        <div class="educational-item" style="opacity:0.8;"><div class="educational-dot" style="background:#EF4444;"></div>Artifact heavy (motion & eye blinks).</div>
                    </div>
                </div>
            </div>

            <!-- Decision Summary -->
            <div class="detail-card" style="margin-top:30px; background:linear-gradient(135deg, rgba(124,58,237,0.1), rgba(219,39,119,0.1)); border:none; text-align:center; padding:40px;">
                <div style="font-size:2.5rem; margin-bottom:15px;">🧭</div>
                <h3 style="color:#fff; margin-bottom:10px;">Quick Selection Guide</h3>
                <p style="color:#C4B5FD; max-width:600px; margin:0 auto; line-height:1.6;">Use <strong>Bipolar</strong> for high precision in noisy environments (OR/ICU). Use <strong>Monopolar</strong> for general screenings in controlled, quiet laboratories.</p>
                <div style="margin-top:30px;">
                    <a href="dashboard.php?page=compare" class="btn btn-outline" style="padding:12px 30px; border-radius:12px;">Return to Compare</a>
                </div>
            </div>
        </div>

    <?php elseif ($page === 'decision_guide'): // ═══ DECISION GUIDE TOOL ═══ ?>

        <a href="dashboard.php?page=pros_cons" class="back-btn mb-16">← Back to Pros & Cons</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(124,58,237,0.1), rgba(219,39,119,0.1)); border:1px solid rgba(124,58,237,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(124,58,237,0.2); border-color:#7C3AED;">🧭</div>
            <div class="hero-text">
                <h2>Decision Guide</h2>
                <p style="color:#C4B5FD;">Find the right recording method for your needs</p>
            </div>
        </div>

        <div style="max-width:800px;">
            <!-- How it Works -->
            <div style="background:linear-gradient(135deg, #2563EB, #1D4ED8); padding:20px; border-radius:20px; margin-bottom:30px; display:flex; gap:15px; align-items:flex-start; box-shadow:0 10px 25px rgba(37,99,235,0.2);">
                <div style="font-size:1.5rem;">ℹ️</div>
                <div>
                    <h4 style="color:#fff; margin:0 0 5px 0;">How it Works</h4>
                    <p style="color:rgba(255,255,255,0.9); font-size:0.9rem; line-height:1.5; margin:0;">Answer 4 quick questions about your recording environment, priorities, and constraints to receive a personalized technique recommendation.</p>
                </div>
            </div>

            <!-- Question 1 -->
            <div class="detail-card quiz-card" data-step="1">
                <div style="font-size:0.8rem; color:var(--blue-l); text-transform:uppercase; font-weight:700; margin-bottom:10px;">Question 1 of 4</div>
                <h3 style="color:#fff; margin-bottom:20px;">What is your recording environment?</h3>
                <div style="display:grid; gap:12px;">
                    <button class="option-btn" onclick="selectOption(1, 'noisy', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Noisy (OR, ICU, Emergency)</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Multiple devices, potential electrical interference.</div>
                        </div>
                    </button>
                    <button class="option-btn" onclick="selectOption(1, 'quiet', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Quiet (Lab, Clinic)</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Controlled environment, minimal interference.</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="detail-card quiz-card" data-step="2" style="display:none;">
                <div style="font-size:0.8rem; color:var(--blue-l); text-transform:uppercase; font-weight:700; margin-bottom:10px;">Question 2 of 4</div>
                <h3 style="color:#fff; margin-bottom:20px;">What is your main priority?</h3>
                <div style="display:grid; gap:12px;">
                    <button class="option-btn" onclick="selectOption(2, 'precision', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Precision & Accuracy</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Exact localization is critical for analysis.</div>
                        </div>
                    </button>
                    <button class="option-btn" onclick="selectOption(2, 'speed', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Speed & Simplicity</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Quick setup and screening is preferred.</div>
                        </div>
                    </button>
                    <button class="option-btn" onclick="selectOption(2, 'coverage', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Broad Coverage</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Wide area monitoring needed.</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="detail-card quiz-card" data-step="3" style="display:none;">
                <div style="font-size:0.8rem; color:var(--blue-l); text-transform:uppercase; font-weight:700; margin-bottom:10px;">Question 3 of 4</div>
                <h3 style="color:#fff; margin-bottom:20px;">What is your budget level?</h3>
                <div style="display:grid; gap:12px;">
                    <button class="option-btn" onclick="selectOption(3, 'high', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">High Budget</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Premium specialized equipment is acceptable.</div>
                        </div>
                    </button>
                    <button class="option-btn" onclick="selectOption(3, 'limited', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Limited Budget</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Cost-effective solution is needed.</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Question 4 -->
            <div class="detail-card quiz-card" data-step="4" style="display:none;">
                <div style="font-size:0.8rem; color:var(--blue-l); text-transform:uppercase; font-weight:700; margin-bottom:10px;">Question 4 of 4</div>
                <h3 style="color:#fff; margin-bottom:20px;">Technician Skill Level?</h3>
                <div style="display:grid; gap:12px;">
                    <button class="option-btn" onclick="selectOption(4, 'advanced', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Advanced / Specialized</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Trained in complex electrode placement protocols.</div>
                        </div>
                    </button>
                    <button class="option-btn" onclick="selectOption(4, 'basic', this)">
                        <div style="text-align:left;">
                            <div style="font-weight:700;">Basic / General</div>
                            <div style="font-size:0.8rem; opacity:0.7;">Standard educational training.</div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Recommendation Alert -->
            <div id="recommendation-box" style="display:none; margin-top:30px; background:linear-gradient(135deg, rgba(16,185,129,0.2), rgba(5,150,105,0.2)); border:2px solid #10B981; border-radius:24px; padding:30px; animation: bounceIn 0.6s ease;">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div id="rec-icon" style="font-size:3rem;">🧬</div>
                    <div style="flex:1;">
                        <h3 style="color:#fff; margin:0 0 8px 0;">Our Recommendation</h3>
                        <p id="rec-text" style="color:#A7F3D0; margin:0; line-height:1.6; font-size:1.1rem; font-weight:600;"></p>
                    </div>
                </div>
                
                <div style="margin-top:30px; display:flex; justify-content:center;">
                    <a href="dashboard.php?page=<?= $m['next'] ?>" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; background:linear-gradient(135deg, #10B981, #059669); box-shadow: 0 10px 20px rgba(16,185,129,0.3);">
                        Continue to Next Module <span>→</span>
                    </a>
                </div>
            </div>

            <!-- Bottom Note -->
            <div id="info-status" style="margin-top:20px; text-align:center; color:rgba(255,255,255,0.4); font-size:0.85rem;">
                <span id="answers-needed">Answer all 4 questions to receive a recommendation</span>
            </div>
        </div>

        <script>
            let selections = {};
            
            function selectOption(step, val, btn) {
                // Remove active class from sibling buttons
                const parent = btn.parentElement;
                parent.querySelectorAll('.option-btn').forEach(b => b.classList.remove('active'));
                
                // Add active class to current button
                btn.classList.add('active');
                
                // Save selection
                selections[step] = val;
                
                // Smoothly show next question
                if(step < 4) {
                    setTimeout(() => {
                        const nextCard = document.querySelector(`.quiz-card[data-step="${step + 1}"]`);
                        if(nextCard) {
                            nextCard.style.display = 'block';
                            nextCard.style.animation = 'fadeInUp 0.6s ease forwards';
                            nextCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 150);
                } else {
                    generateRecommendation();
                }
            }
            
            function generateRecommendation() {
                const recBox = document.getElementById('recommendation-box');
                const recText = document.getElementById('rec-text');
                const recIcon = document.getElementById('rec-icon');
                const infoStatus = document.getElementById('info-status');
                
                let recommendation = "";
                let icon = "";
                
                // Simple logic for Bipolar vs Monopolar
                if (selections[1] === 'noisy' || selections[2] === 'precision' || selections[4] === 'advanced') {
                    recommendation = "BIPOLAR RECORDING is highly recommended for your specific application. Its differential configuration provides superior noise rejection and focal precision needed for analytical accuracy.";
                    icon = "🧬";
                } else {
                    recommendation = "MONOPOLAR RECORDING is suitable for your requirements. It offers a simpler setup and broader coverage, perfect for standard educational screenings in controlled environments.";
                    icon = "📡";
                }
                
                recText.textContent = recommendation;
                recIcon.textContent = icon;
                recBox.style.display = 'block';
                infoStatus.style.display = 'none';
                
                // Scroll to recommendation
                setTimeout(() => {
                    recBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        </script>

        <style>
            .quiz-card { margin-bottom:20px; border:1px solid rgba(255,255,255,0.08); transition:0.3s; }
            .option-btn { width:100%; padding:18px; border-radius:16px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); color:#fff; cursor:pointer; transition:0.3s; }
            .option-btn:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); transform: translateX(5px); }
            .option-btn.active { background: linear-gradient(135deg, #3B82F6, #2563EB); border-color: #3B82F6; box-shadow: 0 4px 15px rgba(37,99,235,0.3); }
            
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes bounceIn {
                0% { opacity: 0; transform: scale(0.9); }
                50% { opacity: 1; transform: scale(1.02); }
                100% { opacity: 1; transform: scale(1); }
            }
        </style>

    <?php elseif ($page === 'signal_quality'): // ═══ SIGNAL QUALITY ANALYSIS ═══ ?>

        <a href="dashboard.php?page=compare" class="back-btn mb-16">← Back to Comparison</a>

        <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(20,184,166,0.1), rgba(16,185,129,0.1)); border:1px solid rgba(20,184,166,0.2);">
            <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(20,184,166,0.2); border-color:#14B8A6;">📡</div>
            <div class="hero-text">
                <h2>Signal Quality</h2>
                <p style="color:#99F6E4;">Waveform clarity & interference analysis</p>
            </div>
        </div>

        <div style="max-width:900px;">
            <!-- Application Type Badge (dynamic) -->
            <div id="sq-app-badge" style="display:none; margin-bottom:20px; padding:14px 24px; border-radius:16px; background:linear-gradient(135deg, rgba(142,36,170,0.15), rgba(124,58,237,0.1)); border:1px solid rgba(142,36,170,0.3); display:flex; align-items:center; gap:14px;">
                <span id="sq-app-icon" style="font-size:1.8rem;">🧠</span>
                <div>
                    <div style="color:#CE93D8; font-size:0.7rem; text-transform:uppercase; font-weight:800; letter-spacing:1px;">Active Analysis Dataset</div>
                    <div id="sq-app-label" style="color:#fff; font-weight:700; font-size:1.05rem;">EEG (Brain Waves)</div>
                </div>
                <div style="margin-left:auto; display:flex; gap:10px;">
                    <span id="sq-mode-badge" style="padding:4px 12px; border-radius:10px; font-size:0.7rem; font-weight:800; background:rgba(59,130,246,0.2); color:#60A5FA; border:1px solid rgba(59,130,246,0.3);">Bipolar</span>
                    <span id="sq-noise-badge" style="padding:4px 12px; border-radius:10px; font-size:0.7rem; font-weight:800; background:rgba(245,158,11,0.2); color:#FCD34D; border:1px solid rgba(245,158,11,0.3);">Medium Noise</span>
                </div>
            </div>

            <!-- Quality score card -->
            <div style="background:linear-gradient(135deg, #10B981, #059669); padding:25px; border-radius:24px; margin-bottom:20px; display:flex; align-items:center; gap:20px; box-shadow:0 15px 35px rgba(16,185,129,0.3);">
                <div id="fidelity-score" style="width:70px; height:70px; border:4px solid rgba(255,255,255,0.3); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.5rem; color:#fff;">92</div>
                <div>
                    <h4 style="color:#fff; margin:0 0 5px 0;">Signal Fidelity Score</h4>
                    <p id="fidelity-desc" style="color:rgba(255,255,255,0.9); font-size:0.9rem; margin:0;">High-quality differential signal detected. SNR is within optimal analytical parameters.</p>
                </div>
            </div>

            <!-- Clinical Condition Placeholder -->
            <div id="sq-clinical-condition-container" style="display:none; margin-bottom:30px;"></div>

            <!-- Signal Comparison Grid -->
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:30px;">
                <!-- Bipolar Signal -->
                <div class="detail-card" style="border-top:4px solid #3B82F6;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <span style="font-size:1.2rem;">🧬</span>
                        <h4 style="color:#fff; margin:0;">Bipolar Signal</h4>
                    </div>
                    
                    <div style="height:120px; background:#0D1224; border-radius:15px; border:1px solid rgba(59,130,246,0.2); margin-bottom:20px; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
                        <canvas id="bipolarCanvas" width="400" height="100" style="width:100%; height:100%;"></canvas>
                        <div id="bipolar-trace-label" style="position:absolute; bottom:10px; right:10px; font-size:0.6rem; color:#3B82F6; font-weight:700; text-transform:uppercase;">Clean Trace</div>
                    </div>

                    <div style="display:grid; gap:10px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">Amplitude</span>
                            <span style="color:#fff; font-weight:700;" id="bipolar-amp">50 μV</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">Noise Level</span>
                            <span style="color:#10B981; font-weight:700;" id="bipolar-noise">Low (5 μV)</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">SNR</span>
                            <span style="color:#3B82F6; font-weight:700;" id="bipolar-snr">10:1</span>
                        </div>
                    </div>
                </div>

                <!-- Monopolar Signal -->
                <div class="detail-card" style="border-top:4px solid #F59E0B;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:15px;">
                        <span style="font-size:1.2rem;">📡</span>
                        <h4 style="color:#fff; margin:0;">Monopolar Signal</h4>
                    </div>

                    <div style="height:120px; background:#0D1224; border-radius:15px; border:1px solid rgba(245,158,11,0.2); margin-bottom:20px; display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
                        <canvas id="monopolarCanvas" width="400" height="100" style="width:100%; height:100%;"></canvas>
                        <div id="monopolar-trace-label" style="position:absolute; bottom:10px; right:10px; font-size:0.6rem; color:#F59E0B; font-weight:700; text-transform:uppercase;">Artifact Heavy</div>
                    </div>

                    <div style="display:grid; gap:10px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">Amplitude</span>
                            <span style="color:#fff; font-weight:700;" id="monopolar-amp">120 μV</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">Noise Level</span>
                            <span style="color:#F59E0B; font-weight:700;" id="monopolar-noise">Higher (20 μV)</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:0.85rem;">
                            <span style="color:#94A3B8;">SNR</span>
                            <span style="color:#3B82F6; font-weight:700;" id="monopolar-snr">6:1</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quality progress indicators -->
            <div class="detail-card" style="background:linear-gradient(135deg, rgba(20,184,166,0.1), rgba(16,185,129,0.05)); border:1px solid rgba(20,184,166,0.2);">
                <div class="panel-title" style="color:#fff; margin-bottom:20px;"><span>📊</span> Clarity Metrics</div>
                
                <div style="margin-bottom:20px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="color:#fff; font-size:0.9rem; font-weight:600;">Bipolar Clarity (Noise-Free)</span>
                        <span id="bipolar-clarity-val" style="color:#10B981; font-weight:800;">92%</span>
                    </div>
                    <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                        <div id="bipolar-clarity-bar" style="width:92%; height:100%; background:linear-gradient(90deg, #3B82F6, #10B981); border-radius:10px; transition: width 1s ease-out;"></div>
                    </div>
                </div>

                <div style="margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span style="color:#fff; font-size:0.9rem; font-weight:600;">Monopolar Clarity (Noise-Free)</span>
                        <span id="monopolar-clarity-val" style="color:#F59E0B; font-weight:800;">68%</span>
                    </div>
                    <div style="height:8px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                        <div id="monopolar-clarity-bar" style="width:68%; height:100%; background:linear-gradient(90deg, #F59E0B, #EF4444); border-radius:10px; transition: width 1s ease-out;"></div>
                    </div>
                </div>
            </div>

            <!-- AI Insights -->
            <div id="sq-ai-insights" style="margin-top:30px; background:linear-gradient(135deg, rgba(124,58,237,0.15), rgba(79,70,229,0.15)); border-radius:24px; padding:30px; border:1px solid rgba(124,58,237,0.2); display:flex; gap:25px; align-items:center;">
                <div style="font-size:3rem;">🧠</div>
                <div style="flex:1;">
                    <h3 id="sq-insight-title" style="color:#fff; margin:0 0 10px 0;">AI Signal Analysis</h3>
                    <p id="sq-insight-text" style="color:#C4B5FD; line-height:1.6; margin:0;">Bipolar recordings show superior Common Mode Rejection (CMRR), making them the gold standard for precision analyticals in EMI-susceptible environments like intensive care units.</p>
                </div>
            </div>

            <!-- NEW: Morphological Features on Quality Page -->
            <div id="sq-morph-container" style="display:none; margin-top:20px; animation: slideUp 0.6s ease-out;">
                <div class="detail-card" style="background:rgba(255,255,255,0.02); border:1px solid rgba(142,36,170,0.2);">
                    <div class="panel-title" style="color:#CE93D8; margin-bottom:20px;"><span>🧬</span> Expert Morphological Features</div>
                    <div id="sq-morph-grid" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px;">
                        <!-- Injected by JS -->
                    </div>
                    <div style="margin-top:20px; padding:15px; background:rgba(142,36,170,0.05); border-radius:12px; border:1px solid rgba(142,36,170,0.1); font-size:0.85rem; color:#CE93D8; text-align:center;">
                        Analytical features extracted using BioElectrode Heuristic PHP Engine v2.0
                    </div>
                </div>
            </div>

            <div style="margin-top:40px; display:flex; justify-content:center;">
                <a href="dashboard.php?page=visualize" class="btn btn-primary" style="padding:15px 40px; border-radius:15px; font-weight:700; background:linear-gradient(135deg, #7C3AED, #4F46E5); box-shadow: 0 10px 20px rgba(124,58,237,0.3);">
                    View Advanced Visualizations <span>📊</span>
                </a>
            </div>
        </div>

        <script>
        // ═══ DATASET-DRIVEN SIGNAL QUALITY PAGE ═══
        (function() {
            const bpCanvas = document.getElementById('bipolarCanvas');
            const mpCanvas = document.getElementById('monopolarCanvas');
            if (!bpCanvas || !mpCanvas) return;

            // ── Read AI Analysis data from localStorage ──
            const saved = localStorage.getItem('last_ai_analysis');
            let aiData = null;
            let appType = 'ECG'; // default
            let electrode = 'Bipolar';
            let noiseLevel = 'Medium';
            let snrNum = 10;

            if (saved) {
                try {
                    const parsed = JSON.parse(saved);
                    aiData = parsed.apiData || null;
                    electrode = parsed.electrode || 'Bipolar';
                    noiseLevel = parsed.noise || 'Medium';
                    if (aiData) {
                        appType = aiData.app_type || 'ECG';
                        snrNum = aiData.actual_snr || 10;
                    }
                } catch(e) { /* ignore parse errors */ }
            }

            // ── Update Application Badge ──
            const appIcons = { 'EEG':'🧠', 'ECG':'❤️', 'EMG':'💪', 'DBS':'⚡', 'Nerve':'🔬', 'Custom':'📊' };
            const appLabels = { 'EEG':'EEG (Brain Waves)', 'ECG':'ECG (Heart Rhythm)', 'EMG':'EMG (Muscle Activity)', 'DBS':'Deep Brain Stimulation', 'Nerve':'Nerve Conduction', 'Custom':'Custom User Study' };
            const badge = document.getElementById('sq-app-badge');
            if (saved && badge) {
                badge.style.display = 'flex';
                document.getElementById('sq-app-icon').innerText = appIcons[appType] || '📊';
                document.getElementById('sq-app-label').innerText = appLabels[appType] || appType;
                document.getElementById('sq-mode-badge').innerText = electrode;
                document.getElementById('sq-noise-badge').innerText = noiseLevel + ' Noise';
            }

            // ── Update Fidelity Score ──
            const fs = document.getElementById('fidelity-score');
            const fd = document.getElementById('fidelity-desc');
            if (aiData && fs && fd) {
                let fScore = Math.round(Math.min(99, snrNum * 5));
                if (snrNum > 15) fScore = Math.round(85 + (snrNum / 30) * 14);
                fs.innerText = fScore;

                const fidelityDescs = {
                    'EEG': snrNum > 12 ? 'High-quality EEG signal. Alpha/beta band separation is clean with minimal artifact contamination.' : (snrNum > 6 ? 'Moderate EEG fidelity. Some EMG artifact present; consider re-referencing.' : 'Low EEG quality. Significant artifact contamination detected.'),
                    'ECG': snrNum > 15 ? 'Excellent cardiac signal. P-QRS-T morphology is clear with optimal analytical quality.' : (snrNum > 8 ? 'Good ECG fidelity. Minor baseline wander detected; acceptable for monitoring.' : 'Poor ECG quality. Motion artifacts and power-line interference present.'),
                    'EMG': snrNum > 10 ? 'Strong EMG signal. Motor unit action potentials are well-defined for educational analysis.' : (snrNum > 5 ? 'Moderate EMG fidelity. Some cross-talk present from adjacent muscles.' : 'Weak EMG signal. Increase gain or reposition electrodes.'),
                    'DBS': snrNum > 15 ? 'Stable DBS stimulation. Pulse delivery is consistent with minimal impedance variation.' : (snrNum > 8 ? 'Acceptable DBS signal. Minor local field potential drift detected.' : 'Unstable DBS signal. Check electrode impedance and connections.'),
                    'Nerve': snrNum > 12 ? 'Strong nerve conduction signal. Compound action potentials are clearly resolved.' : (snrNum > 5 ? 'Moderate NCS fidelity. A-beta fiber responses may be attenuated.' : 'Weak NCS signal. Increase stimulation intensity or reposition electrodes.')
                };
                fd.innerText = fidelityDescs[appType] || (snrNum > 15 ? 'High-quality differential signal detected. SNR is within optimal analytical parameters.' : (snrNum > 8 ? 'Moderate signal fidelity. Acceptable for general analyticals.' : 'Low-quality signal. Consider adjusting parameters.'));
            }

            // ── Update Metrics (Amplitude, Noise, SNR, Clarity) ──
            const ampProfiles = {
                'EEG': { bpAmp: '10–100 μV', mpAmp: '20–200 μV', bpNoiseLbl: 'Low', mpNoiseLbl: 'Higher', bpNoiseVal: '2 μV', mpNoiseVal: '15 μV', bpClarity: 88, mpClarity: 55 },
                'ECG': { bpAmp: '0.5–5 mV', mpAmp: '1–10 mV', bpNoiseLbl: 'Low', mpNoiseLbl: 'Higher', bpNoiseVal: '50 μV', mpNoiseVal: '200 μV', bpClarity: 94, mpClarity: 65 },
                'EMG': { bpAmp: '50 μV–5 mV', mpAmp: '100 μV–30 mV', bpNoiseLbl: 'Moderate', mpNoiseLbl: 'High', bpNoiseVal: '20 μV', mpNoiseVal: '120 μV', bpClarity: 80, mpClarity: 48 },
                'DBS': { bpAmp: '1–10 V', mpAmp: '2–15 V', bpNoiseLbl: 'Low', mpNoiseLbl: 'Moderate', bpNoiseVal: '0.1 V', mpNoiseVal: '0.8 V', bpClarity: 92, mpClarity: 70 },
                'Nerve': { bpAmp: '5–80 μV', mpAmp: '10–120 μV', bpNoiseLbl: 'Low', mpNoiseLbl: 'Higher', bpNoiseVal: '3 μV', mpNoiseVal: '25 μV', bpClarity: 85, mpClarity: 52 },
                'Custom': { bpAmp: '50 μV', mpAmp: '120 μV', bpNoiseLbl: 'Low', mpNoiseLbl: 'Higher', bpNoiseVal: '5 μV', mpNoiseVal: '20 μV', bpClarity: 82, mpClarity: 58 }
            };
            const prof = ampProfiles[appType] || ampProfiles['Custom'];

            // Adjust clarity based on actual SNR
            const bpClarity = aiData ? Math.min(100, Math.round(snrNum / 20 * 100)) : prof.bpClarity;
            const mpSnrVal = aiData ? +(snrNum / 2.8).toFixed(1) : +(snrNum / 2).toFixed(1);
            const mpClarity = aiData ? Math.min(100, Math.round(mpSnrVal / 20 * 100)) : prof.mpClarity;

            document.getElementById('bipolar-amp').innerText = prof.bpAmp;
            document.getElementById('bipolar-noise').innerText = prof.bpNoiseLbl + ' (' + prof.bpNoiseVal + ')';
            document.getElementById('bipolar-snr').innerText = snrNum.toFixed(1) + ':1';
            document.getElementById('monopolar-amp').innerText = prof.mpAmp;
            document.getElementById('monopolar-noise').innerText = prof.mpNoiseLbl + ' (' + prof.mpNoiseVal + ')';
            document.getElementById('monopolar-snr').innerText = mpSnrVal.toFixed(1) + ':1';

            // Trace labels
            const bpLabel = document.getElementById('bipolar-trace-label');
            const mpLabel = document.getElementById('monopolar-trace-label');
            const traceLabels = {
                'EEG': { bp: 'Clean Alpha/Beta', mp: 'EMG Artifact' },
                'ECG': { bp: 'Clean Trace', mp: 'Baseline Wander' },
                'EMG': { bp: 'Filtered MUAP', mp: 'Cross-Talk Present' },
                'DBS': { bp: 'Stable Pulse', mp: 'Impedance Drift' },
                'Nerve': { bp: 'Clean CAP', mp: 'Far-Field Noise' }
            };
            const tl = traceLabels[appType] || { bp: 'Clean Trace', mp: 'Artifact Heavy' };
            if (bpLabel) bpLabel.innerText = tl.bp;
            if (mpLabel) mpLabel.innerText = tl.mp;

            // Clarity bars
            document.getElementById('bipolar-clarity-val').innerText = bpClarity + '%';
            document.getElementById('bipolar-clarity-bar').style.width = bpClarity + '%';
            document.getElementById('monopolar-clarity-val').innerText = mpClarity + '%';
            document.getElementById('monopolar-clarity-bar').style.width = mpClarity + '%';

            // ── Update AI Insights text ──
            const insightTexts = {
                'EEG': 'EEG analysis reveals distinct alpha (8–13Hz) and beta (13–30Hz) band activity. Bipolar montage effectively rejects common EMG and EOG artifacts, providing clean brain wave data ideal for epileptiform discharge detection and sleep staging.',
                'ECG': 'Cardiac signal analysis shows well-defined P-QRS-T morphology. Bipolar leads (I, II, III) provide superior common-mode rejection, effectively eliminating 50/60Hz power-line interference for reliable arrhythmia detection.',
                'EMG': 'EMG analysis detects motor unit action potential (MUAP) patterns during muscle contraction bursts. Bipolar differential recording minimizes cross-talk from adjacent muscle groups, critical for precise neuromuscular analyticals.',
                'DBS': 'Deep Brain Stimulation pulse analysis shows consistent high-frequency stimulation delivery at 130–185 Hz. Bipolar electrode configuration provides focused current delivery with minimal spread to surrounding neural tissue.',
                'Nerve': 'Nerve conduction analysis reveals compound action potential (CAP) responses with distinct A-alpha, A-beta, and C-fiber components. Bipolar recording isolates near-field potentials, essential for accurate conduction velocity calculations.',
                'Custom': 'Signal analysis shows the custom dataset characteristics. Bipolar differential recording provides superior noise rejection compared to monopolar, yielding cleaner waveforms for your specific application.'
            };
            const insightEl = document.getElementById('sq-insight-text');
            const titleEl = document.getElementById('sq-insight-title');
            if (insightEl) insightEl.innerText = insightTexts[appType] || insightTexts['Custom'];
            if (titleEl) titleEl.innerText = 'AI ' + (appLabels[appType] || appType) + ' Analysis';
            
            // ── Update Morphological Features ──
            const morphContainer = document.getElementById('sq-morph-container');
            const morphGrid = document.getElementById('sq-morph-grid');
            if (morphContainer && morphGrid && aiData && aiData.morphological_features) {
                morphContainer.style.display = 'block';
                const m = aiData.morphological_features;
                const features = [
                    { label: 'MAV', value: m.mav, icon: '📏' },
                    { label: 'RMS', value: m.rms, icon: '📶' },
                    { label: 'Std Dev', value: m.std_dev, icon: '📉' },
                    { label: 'Energy', value: m.energy, icon: '🧨' },
                    { label: 'Peak-to-Peak', value: m.peak_to_peak, icon: '↕️' },
                    { label: 'Zero Crossing', value: m.zcr, icon: '🔀' }
                ];
                morphGrid.innerHTML = features.map(f => `
                    <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08); padding:15px; border-radius:14px; text-align:center; transition:0.3s;" onmouseover="this.style.background='rgba(142,36,170,0.1)'; this.style.borderColor='rgba(142,36,170,0.3)';" onmouseout="this.style.background='rgba(255,255,255,0.04)'; this.style.borderColor='rgba(255,255,255,0.08)';">
                        <div style="font-size:0.65rem; color:#A0AEC0; text-transform:uppercase; margin-bottom:6px; font-weight:700; letter-spacing:0.5px;">${f.icon} ${f.label}</div>
                        <div style="font-size:1.1rem; font-weight:800; color:#fff;">${f.value}</div>
                    </div>
                `).join('');
            }

            // ═══ APPLICATION-SPECIFIC WAVEFORM GENERATORS ═══
            function setupCanvas(canvas) {
                const dpr = window.devicePixelRatio || 1;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * dpr;
                canvas.height = rect.height * dpr;
                const ctx = canvas.getContext('2d');
                ctx.scale(dpr, dpr);
                return { ctx, w: rect.width, h: rect.height };
            }

            const bp = setupCanvas(bpCanvas);
            const mp = setupCanvas(mpCanvas);

            // Waveform generator based on application type
            function generateWaveformSample(globalX, beatW) {
                const t = (globalX % beatW) / beatW;

                if (appType === 'EEG') {
                    const tSec = globalX / 80;
                    return 0.4 * Math.sin(2 * Math.PI * 10 * tSec)
                         + 0.15 * Math.sin(2 * Math.PI * 22 * tSec + 1.2)
                         + 0.25 * Math.sin(2 * Math.PI * 6 * tSec + 0.5)
                         + 0.1 * Math.sin(2 * Math.PI * 2.5 * tSec + 2.0);
                }
                if (appType === 'EMG') {
                    const tSec = globalX / 120;
                    const burstPhase = (globalX % 100) / 100;
                    let envelope = 0;
                    if (burstPhase >= 0.1 && burstPhase < 0.6) {
                        envelope = Math.sin(Math.PI * (burstPhase - 0.1) / 0.5);
                    }
                    const muap = Math.sin(2 * Math.PI * 85 * tSec) + 0.6 * Math.sin(2 * Math.PI * 130 * tSec + 0.8) + 0.3 * Math.sin(2 * Math.PI * 210 * tSec + 1.5);
                    return envelope * muap * 0.35 + 0.05 * Math.sin(2 * Math.PI * 45 * tSec);
                }
                if (appType === 'DBS') {
                    const pulseWidth = 5, pulsePeriod = 20;
                    const pulsePos = globalX % pulsePeriod;
                    let v = 0;
                    if (pulsePos < pulseWidth) v = 0.8;
                    else if (pulsePos < pulseWidth + 3) v = -0.3;
                    v += 0.08 * Math.sin(2 * Math.PI * 20 * (globalX / 200)) + 0.04 * Math.sin(2 * Math.PI * 4 * (globalX / 200));
                    return v;
                }
                if (appType === 'Nerve') {
                    const nt = (globalX % 120) / 120;
                    if (nt >= 0.02 && nt < 0.06) return 0.3 * Math.exp(-Math.pow((nt - 0.04) / 0.01, 2) * 20);
                    if (nt >= 0.15 && nt < 0.30) { const x=(nt-0.22)/0.05; return 0.7*Math.exp(-x*x*6)*Math.cos(2*Math.PI*2*(nt-0.15)/0.15); }
                    if (nt >= 0.35 && nt < 0.50) { const x=(nt-0.42)/0.05; return 0.35*Math.exp(-x*x*5)*Math.cos(2*Math.PI*2*(nt-0.35)/0.15); }
                    if (nt >= 0.65 && nt < 0.85) { const x=(nt-0.75)/0.08; return 0.15*Math.exp(-x*x*4); }
                    return 0;
                }
                // ECG (default) and Custom
                if (appType === 'Custom') {
                    const tSec = globalX / 100;
                    return 0.3*Math.sin(2*Math.PI*5*tSec)+0.2*Math.sin(2*Math.PI*12*tSec+0.7)+0.1*Math.sin(2*Math.PI*30*tSec+1.3);
                }
                // ECG: P-QRS-T complex
                if (t >= 0.0 && t < 0.12) { const x=(t-0.06)/0.06; return 0.12*Math.exp(-x*x*8); }
                if (t >= 0.12 && t < 0.18) return 0;
                if (t >= 0.18 && t < 0.22) { const x=(t-0.20)/0.02; return -0.08*Math.exp(-x*x*12); }
                if (t >= 0.22 && t < 0.30) { const x=(t-0.26)/0.03; return 0.85*Math.exp(-x*x*18); }
                if (t >= 0.30 && t < 0.36) { const x=(t-0.33)/0.03; return -0.15*Math.exp(-x*x*14); }
                if (t >= 0.36 && t < 0.48) return 0.01;
                if (t >= 0.48 && t < 0.65) { const x=(t-0.565)/0.07; return 0.22*Math.exp(-x*x*6); }
                return 0;
            }

            let scrollOffset = 0;
            const speed = 1.8;
            const beatWidth = (appType === 'EEG' || appType === 'EMG') ? 200 : (appType === 'DBS' ? 160 : (appType === 'Nerve' ? 240 : 180));
            let animId;

            function drawGrid(ctx, w, h, color) {
                ctx.strokeStyle = color;
                ctx.lineWidth = 0.5;
                for (let y = 0; y < h; y += h/5) { ctx.beginPath(); ctx.moveTo(0,y); ctx.lineTo(w,y); ctx.stroke(); }
                for (let x = 0; x < w; x += 40) { ctx.beginPath(); ctx.moveTo(x,0); ctx.lineTo(x,h); ctx.stroke(); }
            }

            function drawSignal(ctx, w, h, color, glowColor, isNoisy) {
                ctx.clearRect(0, 0, w, h);
                drawGrid(ctx, w, h, isNoisy ? 'rgba(245,158,11,0.06)' : 'rgba(59,130,246,0.06)');

                // Center line
                ctx.strokeStyle = isNoisy ? 'rgba(245,158,11,0.1)' : 'rgba(59,130,246,0.1)';
                ctx.lineWidth = 1;
                ctx.setLineDash([4, 4]);
                ctx.beginPath(); ctx.moveTo(0, h/2); ctx.lineTo(w, h/2); ctx.stroke();
                ctx.setLineDash([]);

                ctx.shadowColor = glowColor;
                ctx.shadowBlur = isNoisy ? 4 : 8;

                ctx.beginPath();
                ctx.strokeStyle = color;
                ctx.lineWidth = isNoisy ? 1.5 : 2;
                ctx.lineJoin = 'round';
                ctx.lineCap = 'round';

                const amplitude = h * 0.35;
                const baseline = h / 2;

                for (let px = 0; px < w; px++) {
                    const globalX = px + scrollOffset;
                    let val = generateWaveformSample(globalX, beatWidth);

                    if (isNoisy) {
                        val += (Math.random() - 0.5) * 0.18;
                        val += 0.06 * Math.sin(globalX * 0.35);
                        val += 0.08 * Math.sin(globalX * 0.008);
                        if (Math.random() < 0.003) val += (Math.random() - 0.5) * 0.5;
                    }

                    const y = baseline - val * amplitude;
                    if (px === 0) ctx.moveTo(px, y); else ctx.lineTo(px, y);
                }
                ctx.stroke();

                ctx.shadowColor = 'transparent';
                ctx.shadowBlur = 0;

                const scanX = w - 2;
                const grad = ctx.createLinearGradient(scanX - 30, 0, scanX, 0);
                grad.addColorStop(0, 'transparent');
                grad.addColorStop(1, isNoisy ? 'rgba(245,158,11,0.3)' : 'rgba(59,130,246,0.3)');
                ctx.fillStyle = grad;
                ctx.fillRect(scanX - 30, 0, 30, h);
            }

            function animate() {
                scrollOffset += speed;
                drawSignal(bp.ctx, bp.w, bp.h, '#3B82F6', 'rgba(59,130,246,0.6)', false);
                drawSignal(mp.ctx, mp.w, mp.h, '#F59E0B', 'rgba(245,158,11,0.4)', true);
                animId = requestAnimationFrame(animate);
            }

            function handleResize() {
                const bpNew = setupCanvas(bpCanvas);
                const mpNew = setupCanvas(mpCanvas);
                bp.ctx = bpNew.ctx; bp.w = bpNew.w; bp.h = bpNew.h;
                mp.ctx = mpNew.ctx; mp.w = mpNew.w; mp.h = mpNew.h;
            }

            window.addEventListener('resize', handleResize);
            animate();

            window.addEventListener('beforeunload', () => {
                cancelAnimationFrame(animId);
            });
        })();
        </script>

    <?php elseif ($page === 'report'): // ═══ COMPARISON REPORT ═══ ?>

        <a href="dashboard.php?page=compare" class="back-btn mb-16">← Back to Comparison</a>

        <div id="report-selection">
            <div class="module-hero" style="margin-top:12px; background:linear-gradient(135deg, rgba(8,145,178,0.1), rgba(6,182,212,0.1)); border:1px solid rgba(8,145,178,0.2);">
                <div class="hero-icon-box" style="font-size:2.8rem; background:rgba(8,145,178,0.2); border-color:#0891B2;">📄</div>
                <div class="hero-text">
                    <h2>Comparison Report</h2>
                    <p style="color:#A5F3FC;">Generate detailed analysis & recommendations</p>
                </div>
            </div>

            <div style="max-width:800px;">
                <div class="detail-card">
                    <h3 style="color:#fff; margin-bottom:20px;">Select Bio-signal Type</h3>
                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:15px; margin-bottom:20px;">
                        <div class="report-type-card active" onclick="selectReportType('ECG', this)">
                            <div style="font-size:1.5rem; margin-bottom:8px;">❤️</div>
                            <div style="font-weight:700;">ECG</div>
                            <div style="font-size:0.7rem; opacity:0.7;">Heart Activity</div>
                        </div>
                        <div class="report-type-card" onclick="selectReportType('EEG', this)">
                            <div style="font-size:1.5rem; margin-bottom:8px;">🧠</div>
                            <div style="font-weight:700;">EEG</div>
                            <div style="font-size:0.7rem; opacity:0.7;">Brain Waves</div>
                        </div>
                        <div class="report-type-card" onclick="selectReportType('EMG', this)">
                            <div style="font-size:1.5rem; margin-bottom:8px;">💪</div>
                            <div style="font-weight:700;">EMG</div>
                            <div style="font-size:0.7rem; opacity:0.7;">Muscle Signals</div>
                        </div>
                    </div>
                    <div class="report-type-card" style="width:100%;" onclick="selectReportType('Custom', this)">
                        <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
                            <span>📁</span>
                            <strong>Custom Dataset (CSV/JSON)</strong>
                        </div>
                    </div>
                </div>

                <div class="detail-card" style="background:rgba(8,145,178,0.05); border-left:4px solid #0891B2;">
                    <div style="display:flex; gap:15px; align-items:flex-start;">
                        <span style="font-size:1.2rem; color:#0891B2;">ℹ️</span>
                        <div>
                            <h4 style="color:#fff; margin:0 0 5px 0;">About This Report</h4>
                            <p style="color:#94A3B8; font-size:0.85rem; line-height:1.6; margin:0;">This comprehensive analysis compares Bipolar and Monopolar configurations across multiple metrics: SNR, noise reduction, stability, and spatial resolution.</p>
                        </div>
                    </div>
                </div>

                <div style="margin-top:30px;">
                    <button class="btn btn-primary" onclick="generateReport()" style="width:100%; padding:18px; border-radius:16px; background:linear-gradient(135deg, #0891B2, #0E7490); font-weight:700; font-size:1.1rem; box-shadow:0 10px 20px rgba(8,145,178,0.2);">
                        Generate Comparison Report <span>📊</span>
                    </button>
                </div>
            </div>
        </div>

        <div id="report-loading" style="display:none; text-align:center; padding:100px 20px;">
            <div class="loader-box">
                <div class="loader-circle"></div>
                <div style="font-size:3rem; position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);">📋</div>
            </div>
            <h3 style="color:#fff; margin-top:30px;">Analyzing Datasets...</h3>
            <p style="color:#94A3B8;">Applying differential signal algorithms and calculating SNR...</p>
        </div>

        <div id="report-result" style="display:none;">
            <div style="background:#1962A5; padding:30px; border-radius:30px; border-bottom-left-radius:0; border-bottom-right-radius:0; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="display:flex; align-items:center; gap:20px;">
                    <div style="width:60px; height:60px; background:rgba(255,255,255,0.2); border-radius:15px; display:flex; align-items:center; justify-content:center; font-size:2rem;">📋</div>
                    <div>
                        <h2 id="result-title" style="color:#fff; margin:0; font-size:1.8rem;">ECG Comparison Report</h2>
                        <span style="color:#B3E5FC;">Detailed Analysis & Technical Recommendation</span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:700; color:#fff;">Status: Complete</div>
                    <div style="font-size:0.8rem; color:#B3E5FC;">Ref: Bio-EP-2024</div>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px; max-width:1200px;">
                <!-- Sidebar -->
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <!-- Accuracy Card -->
                    <div class="detail-card" style="background:#0F172A; text-align:center; padding:30px; border:1px solid rgba(16,185,129,0.3);">
                        <div style="width:80px; height:80px; margin:0 auto 15px; border:6px solid #10B981; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.8rem; color:#10B981; font-weight:800;">A+</div>
                        <h4 style="color:#fff; margin-bottom:5px;">Analysis Complete</h4>
                        <p style="color:#10B981; font-size:0.8rem; font-weight:700;">HIGH RELIABILITY</p>
                    </div>

                    <!-- Executive Summary -->
                    <div class="detail-card" style="background:#1E293B;">
                        <h4 style="color:#fff; margin-bottom:12px;">Executive Summary</h4>
                        <p style="color:#94A3B8; font-size:0.85rem; line-height:1.6; margin:0;">
                            Based on the analysis of the <span id="summary-type">ECG</span> signal data, the Bipolar configuration demonstrates a significant advantage in noise suppression and signal clarity. 
                            The differential gain reduces power-line interference by approximately 60.3%.
                        </p>
                    </div>
                </div>

                <!-- Main Content -->
                <div style="display:flex; flex-direction:column; gap:20px;">
                    <!-- Clinical Condition Placeholder -->
                    <div id="report-clinical-condition-container" style="display:none; margin-bottom:25px;"></div>

                    <!-- Metrics Card -->
                    <div class="detail-card">
                        <h4 style="color:#fff; margin-bottom:20px;">Signal Comparison Metrics</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                            <div style="background:rgba(37,99,235,0.05); padding:15px; border-radius:15px; border:1px solid rgba(37,99,235,0.1);">
                                <div style="color:#3B82F6; font-weight:700; font-size:0.8rem; margin-bottom:10px;">BIPOLAR (Differential)</div>
                                <div style="margin-bottom:10px;"><span style="color:#94A3B8; font-size:0.75rem;">SNR:</span> <strong id="report-bp-snr" style="color:#fff;">15.2:1</strong></div>
                                <div style="margin-bottom:10px;"><span style="color:#94A3B8; font-size:0.75rem;">Noise:</span> <strong id="report-bp-noise" style="color:#fff;">17.4%</strong></div>
                                <div><span style="color:#94A3B8; font-size:0.75rem;">Stability:</span> <strong id="report-bp-stability" style="color:#fff;">89.9%</strong></div>
                            </div>
                            <div style="background:rgba(20,184,166,0.05); padding:15px; border-radius:15px; border:1px solid rgba(20,184,166,0.1);">
                                <div style="color:#14B8A6; font-weight:700; font-size:0.8rem; margin-bottom:10px;">MONOPOLAR (Referential)</div>
                                <div style="margin-bottom:10px;"><span style="color:#94A3B8; font-size:0.75rem;">SNR:</span> <strong id="report-mp-snr" style="color:#fff;">8.1:1</strong></div>
                                <div style="margin-bottom:10px;"><span style="color:#94A3B8; font-size:0.75rem;">Noise:</span> <strong id="report-mp-noise" style="color:#fff;">43.8%</strong></div>
                                <div><span style="color:#94A3B8; font-size:0.75rem;">Stability:</span> <strong id="report-mp-stability" style="color:#fff;">69.9%</strong></div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Stats -->
                    <div class="detail-card">
                        <div style="margin-bottom:20px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="color:#fff; font-size:0.85rem;">Signal Fidelity Improvement (Bipolar)</span>
                                <span id="report-fidelity-imp" style="color:#10B981; font-weight:700;">+86.8%</span>
                            </div>
                            <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                                <div id="report-fidelity-bar" style="width:86%; height:100%; background:#10B981;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                                <span style="color:#fff; font-size:0.85rem;">Stability Assessment</span>
                                <span id="report-stability-val" style="color:#3B82F6; font-weight:700;">+28.6%</span>
                            </div>
                            <div style="height:6px; background:rgba(255,255,255,0.05); border-radius:10px; overflow:hidden;">
                                <div id="report-stability-bar" style="width:72%; height:100%; background:#3B82F6;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- AI recommendation -->
                    <div style="background:linear-gradient(135deg, #4F46E5, #7C3AED); padding:25px; border-radius:20px; color:#fff;">
                        <h4 style="margin:0 0 10px 0;">Technique Recommendation</h4>
                        <p id="rec-body" style="font-size:0.95rem; margin:0; line-height:1.6; opacity:0.9;">
                            Based on the noise profile, <strong>Bipolar Recording</strong> is strongly recommended for this subject setting. It effectively handles the 50/60 Hz power-line interference noted in the dataset.
                        </p>
                    </div>
                </div>
            </div>

            <div style="margin-top:40px; display:flex; gap:15px; justify-content:center;">
                <button onclick="window.location.reload()" class="btn btn-outline" style="padding:15px 30px; border-radius:12px;">Generate New Report</button>
                <button onclick="window.print()" class="btn btn-primary" style="padding:15px 30px; border-radius:12px; background:#0891B2;">Export as PDF 📄</button>
            </div>
        </div>

        <script>
            let selectedType = 'ECG';
            
            function selectReportType(type, element) {
                selectedType = type;
                document.querySelectorAll('.report-type-card').forEach(c => c.classList.remove('active'));
                element.classList.add('active');
            }
            
            function generateReport() {
                document.getElementById('report-selection').style.display = 'none';
                document.getElementById('report-loading').style.display = 'block';
                
                // Update text based on type
                document.getElementById('result-title').textContent = selectedType + ' Comparison Report';
                document.getElementById('summary-type').textContent = selectedType;
                
                setTimeout(() => {
                    document.getElementById('report-loading').style.display = 'none';
                    document.getElementById('report-result').style.display = 'block';
                    document.getElementById('report-result').style.animation = 'fadeIn 0.8s ease';
                    
                    // Populate clinical condition in report
                    syncSignalQualityData();
                }, 400);
            }
        </script>

        <style>
            .report-type-card { 
                background: rgba(255,255,255,0.03); 
                border: 1px solid rgba(255,255,255,0.1); 
                border-radius: 20px; 
                padding: 20px; 
                text-align: center; 
                cursor: pointer; 
                transition: 0.3s;
                color: #fff;
            }
            .report-type-card:hover { background: rgba(255,255,255,0.06); transform: translateY(-3px); border-color: #0891B2; }
            .report-type-card.active { background: rgba(8,145,178,0.1); border-color: #0891B2; border-width: 2px; }
            
            .loader-box { position:relative; width:120px; height:120px; margin:0 auto; }
            .loader-circle { width:100%; height:100%; border:3px solid rgba(255,255,255,0.1); border-top-color:#0891B2; border-radius:50%; animation: spin 1s linear infinite; }
            @keyframes spin { to { transform: rotate(360deg); } }
        </style>


        <?php elseif ($page === 'visualize'): ?>
            <div class="module-header" style="background: linear-gradient(135deg, #0f172a, #1e1b4b); border-radius: 20px; padding: 40px; margin-bottom: 30px; border: 1px solid rgba(139, 92, 246, 0.4); box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.3); position: relative; overflow: hidden;">
                <!-- Decorative background elements -->
                <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(139,92,246,0.2) 0%, transparent 70%); border-radius: 50%;"></div>
                <div style="position: absolute; bottom: -30px; left: 20%; width: 150px; height: 150px; background: radial-gradient(circle, rgba(16,185,129,0.15) 0%, transparent 70%); border-radius: 50%;"></div>
                
                <div style="position:relative; z-index:1; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="display:inline-block; padding: 4px 12px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; font-size: 11px; color: #C4B5FD; font-weight: bold; letter-spacing: 1px; margin-bottom: 12px; text-transform: uppercase;">Interactive Educational Module</div>
                        <h2 id="viz-main-title" style="color:#fff; margin:0 0 8px 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">AI Recording Technique Analyzer</h2>
                        <p style="color: #A78BFA; margin: 0; font-size: 15px; max-width: 600px; line-height: 1.5;">Master the physical dynamics of biomedical signal acquisition. Adjust environmental interference in real-time to visually compare how Bipolar systems reject noise that obscure Monopolar recordings.</p>
                    </div>
                    <div style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(76, 29, 149, 0.4)); padding: 20px; border-radius: 20px; border: 1px solid rgba(139, 92, 246, 0.3); backdrop-filter: blur(5px);">
                        <span style="font-size: 42px; display: block; animation: float 3s ease-in-out infinite;">⚖️</span>
                    </div>
                </div>
            </div>

            <style>
                @keyframes float { 0%,100% {transform: translateY(0)} 50% {transform: translateY(-8px)} }
                .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.08); padding: 25px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); transition: transform 0.3s, border-color 0.3s; }
                .glass-card:hover { border-color: rgba(255,255,255,0.15); }
                .custom-slider { -webkit-appearance: none; width: 100%; height: 6px; border-radius: 3px; background: rgba(255,255,255,0.1); outline: none; }
                .custom-slider::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #F59E0B; cursor: pointer; box-shadow: 0 0 10px rgba(245, 158, 11, 0.6); transition: transform 0.1s; border: 2px solid #fff; }
                .custom-slider::-webkit-slider-thumb:hover { transform: scale(1.2); }
                .canvas-layer { position: absolute; top:0; left:0; width:100%; height:100%; }
                .canvas-container { position:relative; width:100%; height: 160px; border-radius: 12px; overflow:hidden; background: rgba(0,0,0,0.4); }
                .data-pill { background: rgba(15, 23, 42, 0.85); padding: 6px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); font-size: 11px; font-weight: bold; letter-spacing: 0.5px; backdrop-filter: blur(4px); }
            </style>

            <div style="display: grid; grid-template-columns: 1fr 340px; gap: 25px; margin-bottom: 30px;">
                <!-- Main Interactive Simulator -->
                <div class="glass-card" style="display: flex; flex-direction: column; gap: 20px; border-left: 4px solid #8B5CF6;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display:flex; align-items:center; gap: 10px;">
                            <span style="font-size: 20px;">🖥️</span>
                            <h3 style="color: #fff; margin:0; font-size: 16px; font-weight: 600;">Live Acquisition Simulator</h3>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; background: #10B981; border-radius: 50%; box-shadow: 0 0 10px #10B981; animation: blink 1.5s infinite;"></span>
                            <span style="font-size: 11px; color: #10B981; text-transform:uppercase; font-weight:bold; letter-spacing:0.5px;">Synthesizing AI Data</span>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-direction:column; gap:20px; flex:1;">
                        
                        <!-- Monopolar Window -->
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                                <div style="color:#38BDF8; font-size:13px; font-weight:bold; display:flex; align-items:center; gap:6px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h4l2-9 4 18 2-9h4"/></svg>
                                    Monopolar Reference (Single Active Electrode)
                                </div>
                                <div id="mono-snr-label" class="data-pill" style="color:#38BDF8;">Est. SNR: Calculating...</div>
                            </div>
                            <div class="canvas-container" style="border: 1px solid rgba(56, 189, 248, 0.25); box-shadow: inset 0 0 20px rgba(56, 189, 248, 0.05);">
                                <!-- Background Grid Canvas -->
                                <canvas id="gridCanvasMono" class="canvas-layer"></canvas>
                                <!-- Signal Canvas -->
                                <canvas id="monoCanvas" class="canvas-layer"></canvas>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="height:1px; flex:1; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);"></div>
                            <div style="font-size: 11px; color: #64748B; font-weight: bold; letter-spacing: 2px;">VS</div>
                            <div style="height:1px; flex:1; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);"></div>
                        </div>
                        
                        <!-- Bipolar Window -->
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <div style="display:flex; justify-content:space-between; align-items:flex-end;">
                                <div style="color:#10B981; font-size:13px; font-weight:bold; display:flex; align-items:center; gap:6px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 8 8 8"/><path d="m8 16 8-8"/><path d="m5 8-3 3"/><path d="m13 21 3-3"/><path d="m9 5 3-3"/></svg>
                                    Bipolar Differential (Active + Reference)
                                </div>
                                <div id="bipo-snr-label" class="data-pill" style="color:#10B981;">Est. SNR: Calculating...</div>
                            </div>
                            <div class="canvas-container" style="border: 1px solid rgba(16, 185, 129, 0.25); box-shadow: inset 0 0 20px rgba(16, 185, 129, 0.05);">
                                <canvas id="gridCanvasBipo" class="canvas-layer"></canvas>
                                <canvas id="bipoCanvas" class="canvas-layer"></canvas>
                                <!-- AI Active CMRR badge -->
                                <div style="position:absolute; top:10px; right:10px; z-index:10; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.4); color:#10B981; font-size:10px; padding:3px 8px; border-radius:12px; display:flex; align-items:center; gap:4px; font-weight:bold;">
                                    <span style="display:block; width:4px; height:4px; border-radius:50%; background:#10B981; animation: blink 1s infinite;"></span> CMRR ACTIVE 96%
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Analysis Controls & Metrics -->
                <div style="display:flex; flex-direction:column; gap:25px;">
                    <!-- Environment Controls -->
                    <div class="glass-card" style="position: relative;">
                        <!-- Soft glow back -->
                        <div style="position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg, #F59E0B, transparent); border-radius: 3px 3px 0 0;"></div>
                        
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 20px;">
                            <div style="background:rgba(245,158,11,0.15); padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M4 14a8 8 0 0 1 15.5-2.5"/></svg>
                            </div>
                            <h3 style="color: #fff; margin:0; font-size: 15px;">Environment Controls</h3>
                        </div>
                        
                        <div style="margin-bottom:25px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                                <label style="font-size: 12px; color: #CBD5E1; font-weight:600;">Interference (EMI) Level</label>
                                <span id="noise-val-disp" style="background:rgba(245,158,11,0.2); padding:2px 6px; border-radius:4px; font-size: 11px; color: #F59E0B; font-weight:bold; display:flex; align-items:center; box-shadow: 0 0 5px rgba(245,158,11,0.3);">50%</span>
                            </div>
                            <input type="range" id="noiseSlider" class="custom-slider" min="0" max="100" value="50">
                            <div style="display:flex; justify-content:space-between; font-size:9px; color:#64748B; margin-top:8px;">
                                <span style="background:rgba(255,255,255,0.05); padding:2px 4px; border-radius:3px;">Shielded Room</span>
                                <span style="background:rgba(255,255,255,0.05); padding:2px 4px; border-radius:3px;">Operating Theater</span>
                            </div>
                        </div>
                        
                        <div>
                            <label style="font-size: 12px; color: #CBD5E1; font-weight:600; margin-bottom: 8px; display:block;">Target Bio-Signal Morphology</label>
                            <div style="position:relative;">
                                <select id="signalSelect" class="form-control" style="background:rgba(15,23,42,0.8); color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:8px; padding:10px 15px; appearance:none; width:100%; cursor:pointer; outline:none; font-size:13px;">
                                    <option value="ECG">❤️ ECG (Electrocardiogram)</option>
                                    <option value="EEG">🧠 EEG (Electroencephalogram)</option>
                                    <option value="EMG">💪 EMG (Electromyogram)</option>
                                    <option value="DBS">⚡ DBS (Deep Brain Stim)</option>
                                </select>
                                <div style="position:absolute; right:15px; top:50%; transform:translateY(-50%); pointer-events:none; color:#CBD5E1;">▼</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- AI Learning Insight -->
                    <div class="glass-card" style="flex:1; background: linear-gradient(180deg, rgba(88,28,135,0.1) 0%, rgba(15,23,42,0.6) 100%); border: 1px solid rgba(139,92,246,0.25); display:flex; flex-direction:column;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 15px;">
                            <div style="background:rgba(139,92,246,0.2); padding:6px; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                                <span style="font-size:16px;">🎓</span>
                            </div>
                            <h3 style="color: #C4B5FD; margin:0; font-size: 15px;">AI Educational Tutor</h3>
                        </div>
                        
                        <div style="flex:1;">
                            <p id="ai-tutor-text" style="font-size:13px; color:#E2E8F0; line-height:1.6; margin:0; font-weight:400;">
                                Initializing AI tutor environment. Please interact with the controls above.
                            </p>
                        </div>
                        
                        <div style="margin-top: 15px; padding-top:15px; border-top: 1px dashed rgba(139,92,246,0.3); display:flex; flex-direction:column; gap:8px;">
                            <span style="font-size:10px; color:#94A3B8; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">AI Analytical System Rec:</span>
                            <div id="ai-recommendation" style="align-self:flex-start; font-size:12px; font-weight:800; color:#10B981; padding:6px 12px; background:rgba(16,185,129,0.15); border:1px solid rgba(16,185,129,0.4); border-radius:6px; letter-spacing:0.5px; box-shadow: 0 0 10px rgba(16,185,129,0.1);">BIPOLAR</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theory explanation box below -->
            <div class="glass-card" style="background: rgba(15,23,42,0.4); border: 1px solid rgba(139, 92, 246, 0.2);">
                <div style="display:flex; gap: 20px; align-items:flex-start;">
                    <div style="font-size:32px; background: linear-gradient(135deg, rgba(139,92,246,0.2), rgba(76,29,149,0.4)); width:70px; height:70px; display:flex; align-items:center; justify-content:center; border-radius:15px; flex-shrink:0; border:1px solid rgba(139,92,246,0.3);">📚</div>
                    <div>
                        <h4 style="color:#fff; margin:0 0 10px 0; font-size:16px;">The Engineering Principle: Common-Mode Rejection Ratio (CMRR)</h4>
                        <p style="color:#94A3B8; font-size:14px; line-height:1.7; margin:0;">
                            A <strong style="color:#38BDF8;">Monopolar</strong> setup records the absolute voltage difference between the active site and a distant electrical ground. It acts as an antenna, absorbing all environmental noise (EMI, 50/60Hz mains interference) passing through the human body.<br><br>
                            A <strong style="color:#10B981;">Bipolar</strong> setup uses a differential amplifier to measure the voltage <i>between</i> two closely spaced active electrodes. Because environmental noise hits both electrodes almost identically (Common-Mode), the amplifier subtracts them out to zero, leaving only the localized physiological signal (Differential Mode). This mathematical rejection is why Bipolar remains pristine even at high noise levels.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Clinical Condition Placeholder -->
            <div id="viz-clinical-condition-container" style="display:none; margin-top:25px; margin-bottom:0;"></div>

            <div style="margin-top:25px; padding: 25px; border-radius: 16px; background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(37,99,235,0.15)); border: 1px solid rgba(37,99,235,0.3); text-align: center; display: flex; flex-direction: column; align-items: center;">
                <h4 style="color:#fff; margin:0 0 15px 0; font-size:18px;">Comprehensive Analysis</h4>
                <a href="dashboard.php?page=report" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:10px; padding:15px 30px; border-radius:12px; background:var(--g-blue); text-decoration:none; font-weight:700; color:#fff; box-shadow:0 10px 25px rgba(37,99,235,0.3);">
                    <span style="font-size:1.2rem;">📄</span> View Full Comparison Report
                </a>
            </div>

            <script>
            document.addEventListener("DOMContentLoaded", () => {
                // UI Elements
                const noiseSlider = document.getElementById('noiseSlider');
                const signalSelect = document.getElementById('signalSelect');
                const tutorText = document.getElementById('ai-tutor-text');
                const recText = document.getElementById('ai-recommendation');
                const monoSnr = document.getElementById('mono-snr-label');
                const bipoSnr = document.getElementById('bipo-snr-label');
                const noiseValDisp = document.getElementById('noise-val-disp');

                // Canvas Setup
                const contexts = {};
                ['mono', 'bipo'].forEach(type => {
                    const canvas = document.getElementById(type + 'Canvas');
                    const gridCanvas = document.getElementById('gridCanvas' + (type.charAt(0).toUpperCase() + type.slice(1)));
                    contexts[type] = {
                        c: canvas,
                        ctx: canvas.getContext('2d'),
                        gc: gridCanvas,
                        gctx: gridCanvas.getContext('2d')
                    };
                });

                function handleResize() {
                    const dpr = window.devicePixelRatio || 1;
                    
                    ['mono', 'bipo'].forEach(type => {
                        const info = contexts[type];
                        const rect = info.c.parentElement.getBoundingClientRect();
                        const w = Math.floor(rect.width);
                        const h = Math.floor(rect.height);
                        
                        info.c.width = w * dpr; info.c.height = h * dpr;
                        info.gc.width = w * dpr; info.gc.height = h * dpr;
                        
                        info.ctx.scale(dpr, dpr);
                        info.gctx.scale(dpr, dpr);
                        
                        info.w = w; info.h = h;
                        drawGrid(info.gctx, w, h);
                    });
                }
                window.addEventListener('resize', handleResize);

                // Draw background grids once per resize
                function drawGrid(ctx, w, h) {
                    ctx.clearRect(0,0,w,h);
                    ctx.strokeStyle = 'rgba(255,255,255,0.04)';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    // Vertical grid
                    for(let i=0; i<w; i+=40) { ctx.moveTo(i,0); ctx.lineTo(i,h); }
                    // Horizontal grid
                    for(let i=0; i<h; i+=40) { ctx.moveTo(0,i); ctx.lineTo(w,i); }
                    ctx.stroke();
                    
                    // Center baseline
                    ctx.strokeStyle = 'rgba(255,255,255,0.12)';
                    ctx.setLineDash([4,4]);
                    ctx.beginPath(); ctx.moveTo(0, h/2); ctx.lineTo(w, h/2); ctx.stroke();
                    ctx.setLineDash([]);
                }

                // Call resize to setup initial dims
                setTimeout(handleResize, 100);

                let globalTimeOffset = 0;

                // Physics Generators
                function getBioSignal(t, type) {
                    if (type === 'ECG') {
                        let phase = t % 1.2; // 1.2s avg heart beat
                        if (phase < 0.1) return 0;
                        if (phase < 0.18) return 0.15 * Math.sin((phase - 0.1) * Math.PI / 0.08); // P wave
                        if (phase < 0.25) return 0;
                        if (phase < 0.28) return -0.15; // Q
                        if (phase < 0.32) return 0.85;   // R
                        if (phase < 0.36) return -0.25; // S
                        if (phase < 0.5) return 0;
                        if (phase < 0.65) return 0.3 * Math.sin((phase - 0.5) * Math.PI / 0.15); // T wave
                        return 0;
                    }
                    if (type === 'EEG') {
                        // Alpha, beta, and theta superimposed
                        return 0.35 * Math.sin(2*Math.PI*10*t) + 0.15 * Math.sin(2*Math.PI*22*t) + 0.2 * Math.sin(2*Math.PI*5*t+1);
                    }
                    if (type === 'EMG') {
                        // Bursts of activation
                        let burst = (t % 2 < 0.8) ? Math.sin((t%2)*Math.PI/0.8) : 0.02;
                        return burst * (0.6 * Math.sin(2*Math.PI*85*t) + 0.4 * Math.sin(2*Math.PI*135*t+1.5) + 0.2 * Math.random());
                    }
                    if (type === 'DBS') {
                        let bp = (t % 0.1) / 0.1;
                        if (bp < 0.1) return 0.6; 
                        if (bp < 0.15) return -0.2;
                        return 0.03 * Math.sin(2*Math.PI*60*t);
                    }
                    return 0;
                }

                noiseSlider.addEventListener('input', () => { noiseValDisp.innerText = noiseSlider.value + "%"; });

                function animate() {
                    const params = {
                        sigT: signalSelect.value,
                        nsLvl: parseFloat(noiseSlider.value)
                    };
                    
                    if (!contexts.mono.w) { requestAnimationFrame(animate); return; }

                    const w = contexts.mono.w;
                    const h = contexts.mono.h;

                    contexts.mono.ctx.clearRect(0,0,w,h);
                    contexts.bipo.ctx.clearRect(0,0,w,h);

                    contexts.mono.ctx.beginPath();
                    contexts.bipo.ctx.beginPath();
                    
                    // Style config
                    contexts.mono.ctx.strokeStyle = '#38BDF8';
                    contexts.mono.ctx.lineWidth = 2.5;
                    contexts.mono.ctx.lineJoin = 'round';
                    contexts.mono.ctx.shadowColor = '#0EA5E9';
                    contexts.mono.ctx.shadowBlur = 4;

                    contexts.bipo.ctx.strokeStyle = '#34D399';
                    contexts.bipo.ctx.lineWidth = 2.5;
                    contexts.bipo.ctx.lineJoin = 'round';
                    contexts.bipo.ctx.shadowColor = '#10B981';
                    contexts.bipo.ctx.shadowBlur = 4;

                    let pSig = 0, pNsMono = 0, pNsBipo = 0;
                    const cmrrInefficiency = 0.04; // 96% noise rejection

                    for(let x=0; x<w; x++) {
                        const t = (x + globalTimeOffset) / 250; 
                        
                        const bio = getBioSignal(t, params.sigT);
                        
                        // Noise generation
                        const hz50 = Math.sin(2 * Math.PI * 50 * t);
                        const wandering = Math.sin(2 * Math.PI * 0.4 * t);
                        const rf = (Math.random() - 0.5);
                        
                        const totalNoiseRaw = (hz50 * 0.6 + wandering * 0.5 + rf * 0.4);
                        const scaledNoise = totalNoiseRaw * (params.nsLvl / 30);

                        // True signals
                        const monoVal = bio + scaledNoise;
                        const bipoVal = bio + (scaledNoise * cmrrInefficiency);

                        // Stats accum
                        pSig += bio*bio;
                        pNsMono += scaledNoise*scaledNoise;
                        pNsBipo += (scaledNoise*cmrrInefficiency)*(scaledNoise*cmrrInefficiency);

                        const ampScale = h * 0.35;
                        const monoY = (h / 2) - (monoVal * ampScale);
                        const bipoY = (h / 2) - (bipoVal * ampScale);

                        if(x===0) {
                            contexts.mono.ctx.moveTo(x, monoY);
                            contexts.bipo.ctx.moveTo(x, bipoY);
                        } else {
                            contexts.mono.ctx.lineTo(x, monoY);
                            contexts.bipo.ctx.lineTo(x, bipoY);
                        }
                    }

                    contexts.mono.ctx.stroke();
                    contexts.bipo.ctx.stroke();

                    // UI Updates (throttle to save layout thrashing)
                    if (Math.floor(globalTimeOffset) % 15 === 0) {
                        let snrM = 10 * Math.log10(pSig / (pNsMono+0.0001));
                        let snrB = 10 * Math.log10(pSig / (pNsBipo+0.0001));
                        
                        if(params.nsLvl === 0) { snrM = 99; snrB = 99; }
                        
                        monoSnr.textContent = `Est. SNR: ${snrM > 90 ? '>90' : snrM.toFixed(1)} dB`;
                        bipoSnr.textContent = `Est. SNR: ${snrB > 90 ? '>90' : snrB.toFixed(1)} dB`;

                        if (params.nsLvl <= 15) {
                            tutorText.innerHTML = `<strong>Ultra-Low Noise:</strong> In highly shielded environments, the difference between techniques is minimal. <strong style="color:#0EA5E9">Monopolar</strong> yields an excellent SNR (${snrM.toFixed(0)}dB) and accurately captures absolute potentials. Both are suitable.`;
                            updateBadge('BOTH SUITABLE', '#38BDF8', 'rgba(14,165,233,0.15)', 'rgba(14,165,233,0.3)');
                        } else if (params.nsLvl < 55) {
                            tutorText.innerHTML = `<strong>Moderate Interference:</strong> As standard educational noise (60Hz/50Hz mains) enters the system, Monopolar recordings accumulate significant artifacts. The AI recommends <strong style="color:#10B981">Bipolar</strong> to leverage differential subtraction, recovering ~${(snrB - snrM).toFixed(0)}dB of signal clarity.`;
                            updateBadge('BIPOLAR PREFERRED', '#10B981', 'rgba(16,185,129,0.15)', 'rgba(16,185,129,0.3)');
                        } else {
                            tutorText.innerHTML = `<strong>Severe Electromagnetic Interference:</strong> The Monopolar signal is completely swamped by noise and baseline wander, destroying analytical value. <strong style="color:#10B981">Bipolar</strong> differential recording is absolutely mandatory to extract the ${params.sigT} signal safely.`;
                            updateBadge('BIPOLAR REQUIRED', '#F59E0B', 'rgba(245,158,11,0.15)', 'rgba(245,158,11,0.3)');
                        }
                    }

                    globalTimeOffset += 3.5; 
                    requestAnimationFrame(animate);
                }

                function updateBadge(text, color, bg, border) {
                    recText.textContent = text;
                    recText.style.color = color;
                    recText.style.background = bg;
                    recText.style.borderColor = border;
                    recText.style.boxShadow = `0 0 10px ${bg}`;
                }
                

                // Start Animation Loop
                animate();

                // Sync AI context if available
                syncSignalQualityData();
            });
            </script>
        <?php elseif ($page === 'quiz'): ?>
            <div id="quiz-container">
                <!-- Quiz Selection View -->
                <div id="quiz-selection-view">
                    <div class="module-header" style="background: linear-gradient(135deg, #7B1FA2, #9C27B0); border-radius: 20px; padding: 40px; margin-bottom: 30px; text-align:center;">
                        <h2 style="color:#fff; margin:0;">Biomedical Quiz Center</h2>
                        <p style="color: rgba(255,255,255,0.8); margin-top:10px;">Select your specialized field and chapter to begin</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom:30px;">
                        <div class="detail-card selection-card active" id="type-bipolar" onclick="setQuizType('Bipolar')" style="cursor:pointer; transition:0.3s; border: 2px solid #7B1FA2;">
                            <div style="font-size: 2rem; margin-bottom:15px;">🧬</div>
                            <h4 style="color:#fff; margin:0;">Bipolar</h4>
                            <p style="font-size:0.8rem; color:#888; margin-top:5px;">Differential analysis</p>
                        </div>
                        <div class="detail-card selection-card" id="type-monopolar" onclick="setQuizType('Monopolar')" style="cursor:pointer; transition:0.3s; border: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-size: 2rem; margin-bottom:15px;">📡</div>
                            <h4 style="color:#fff; margin:0;">Monopolar</h4>
                            <p style="font-size:0.8rem; color:#888; margin-top:5px;">Absolute potential</p>
                        </div>
                    </div>

                    <div class="detail-card" style="padding: 30px; margin-bottom:30px;">
                        <h4 style="color:#fff; margin-bottom:20px; display:flex; align-items:center; gap:10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layers"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                            Select Chapter
                        </h4>
                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px;" id="chapter-selection">
                            <button class="chap-btn active" onclick="setChapter(1, this)">1</button>
                            <button class="chap-btn" onclick="setChapter(2, this)">2</button>
                            <button class="chap-btn" onclick="setChapter(3, this)">3</button>
                            <button class="chap-btn" onclick="setChapter(4, this)">4</button>
                            <button class="chap-btn" onclick="setChapter(5, this)">5</button>
                        </div>
                    </div>

                    <button onclick="startSelectedQuiz()" class="action-btn" style="width:100%; justify-content:center; background: #7B1FA2; padding: 20px; font-size:18px; font-weight:700; border-radius: 15px; box-shadow: 0 10px 25px rgba(123, 31, 162, 0.4);">
                        Launch Quiz Module
                    </button>
                </div>

                <!-- Quiz Question View -->
                <div id="quiz-question-view" style="display:none;">
                    <div class="module-header" style="background: linear-gradient(135deg, #7B1FA2, #9C27B0); border-radius: 20px; padding: 30px; margin-bottom: 30px; position:relative; overflow:hidden;">
                         <div style="position:relative; z-index:1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 12px; color: #fff;"><span id="quiz-cat-label">Bipolar</span> / <span id="quiz-chap-label">Chapter 1</span></span>
                                <span style="background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 12px; color: #fff;">Question <span id="current-q-num">1</span>/<span id="total-q-num"></span></span>
                            </div>
                            <h2 style="color:#fff; margin:0;" id="quiz-title-display">Electrode Techniques Quiz</h2>
                            <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;" id="quiz-subtitle-display">Test your mastery of recording configurations</p>
                         </div>
                    </div>

                    <div class="detail-card" style="margin-bottom:20px; padding: 40px 20px;">
                        <div style="text-align:center;">
                            <div style="width:64px; height:64px; background: rgba(123, 31, 162, 0.1); border-radius: 16px; display: flex; align-items:center; justify-content:center; margin: 0 auto 20px; border: 1px solid rgba(123, 31, 162, 0.2);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#7B1FA2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-help-circle"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
                            </div>
                            <h3 id="question-text" style="font-size: 1.3rem; line-height:1.6; color:#fff; max-width: 600px; margin: 0 auto;">Question loading...</h3>
                        </div>
                    </div>

                    <div id="options-container" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px;">
                        <!-- Options will be injected here -->
                    </div>

                    <button id="submit-answer-btn" onclick="submitAnswer()" class="action-btn" style="width:100%; justify-content:center; background: #7B1FA2; padding: 20px; font-size:16px; border-radius: 15px; box-shadow: 0 10px 20px rgba(123, 31, 162, 0.3);">
                        Submit Answer <span style="margin-left: 10px;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-right"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
                    </button>
                </div>

                <!-- Quiz Results View -->
                <div id="quiz-result-view" style="display:none; text-align:center;">
                    <div class="module-header" id="result-header" style="background: linear-gradient(135deg, #7B1FA2, #9C27B0); border-radius: 20px; padding: 40px; margin-bottom: 30px;">
                        <div style="margin-bottom: 15px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trophy"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
                        </div>
                        <h2 style="color:#fff; margin:0;">Section Certified!</h2>
                        <p style="color: rgba(255,255,255,0.8);" id="result-subtitle">Results for Monopolar - Chapter 2</p>
                    </div>

                    <div class="detail-card" style="padding: 40px; margin-bottom:20px;">
                        <div style="font-size: 14px; color: #888; text-transform:uppercase; letter-spacing:1px; margin-bottom:10px;">Knowledge Score</div>
                        <div id="final-score" style="font-size: 72px; font-weight: 800; color: #7B1FA2;">0%</div>
                        <div id="score-text" style="font-size: 18px; font-weight: 600; margin-top:10px;">Mastery Achieved!</div>
                        <div style="width: 100px; height: 2px; background: rgba(255,255,255,0.1); margin: 25px auto;"></div>
                        <div id="correct-count" style="font-size: 16px; color: #94A3B8;">0 / 10 Correct</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom:30px;">
                        <div class="detail-card" style="padding:15px; display:flex; align-items:center; gap:12px;">
                            <div style="color: #FACC15;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            </div>
                            <div style="text-align:left;">
                                <div style="font-size:11px; color:#888;">Best Chapter Run</div>
                                <div style="font-weight:bold; color:#fff;" id="best-score-display">100%</div>
                            </div>
                        </div>
                        <div class="detail-card" style="padding:15px; display:flex; align-items:center; gap:12px;">
                            <div style="color: #94A3B8;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-check"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
                            </div>
                            <div style="text-align:left;">
                                <div style="font-size:11px; color:#888;">Module Status</div>
                                <div style="font-weight:bold; color:#fff;">COMPLETED</div>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 15px;">
                        <button onclick="resetQuiz()" class="action-btn" style="flex:1; justify-content:center; background: #7B1FA2;">
                            Pick New Chapter
                        </button>
                        <a href="dashboard.php" class="action-btn" style="flex:1; justify-content:center; background: rgba(255,255,255,0.05); color:#fff; border: 1px solid rgba(255,255,255,0.1);">
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>

            <script>
                // Selection state
                let selectedType = 'Bipolar';
                let selectedChapter = 1;

                function setQuizType(type) {
                    selectedType = type;
                    document.querySelectorAll('.selection-card').forEach(cc => {
                        cc.style.borderColor = 'rgba(255,255,255,0.1)';
                        cc.style.borderWidth = '1px';
                    });
                    const activeCard = document.getElementById('type-' + type.toLowerCase());
                    activeCard.style.borderColor = '#7B1FA2';
                    activeCard.style.borderWidth = '2px';
                }

                function setChapter(chap, btn) {
                    selectedChapter = chap;
                    document.querySelectorAll('.chap-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                }

                function startSelectedQuiz() {
                    const chapterKey = "Chapter " + selectedChapter;
                    const loadedQuestions = quizData[selectedType][chapterKey];
                    
                    if (!loadedQuestions || loadedQuestions.length === 0) {
                        alert("No questions found for this selection.");
                        return;
                    }

                    // Replace global questions
                    questions.length = 0;
                    questions.push(...loadedQuestions);
                    
                    document.getElementById('quiz-selection-view').style.display = 'none';
                    document.getElementById('quiz-question-view').style.display = 'block';
                    
                    document.getElementById('quiz-cat-label').innerText = selectedType;
                    document.getElementById('quiz-chap-label').innerText = chapterKey;
                    document.getElementById('total-q-num').innerText = questions.length;
                    
                    currentQuestion = 0;
                    score = 0;
                    loadQuestion();
                }

                function resetQuiz() {
                    document.getElementById('quiz-result-view').style.display = 'none';
                    document.getElementById('quiz-selection-view').style.display = 'block';
                }

                // Quiz logic
                let questions = [];
                let currentQuestion = 0;
                let score = 0;
                window.selectedOption = null;

                function loadQuestion() {
                    const qData = questions[currentQuestion];
                    document.getElementById('current-q-num').innerText = currentQuestion + 1;
                    document.getElementById('question-text').innerText = qData.q;
                    
                    const container = document.getElementById('options-container');
                    container.innerHTML = '';
                    
                    qData.options.forEach((opt, idx) => {
                        const optBtn = document.createElement('div');
                        optBtn.className = 'detail-card quiz-option-item';
                        optBtn.style.padding = '20px';
                        optBtn.style.cursor = 'pointer';
                        optBtn.style.border = '1px solid rgba(255,255,255,0.1)';
                        optBtn.style.transition = '0.2s';
                        optBtn.innerHTML = opt;
                        optBtn.onclick = () => {
                            document.querySelectorAll('#options-container .detail-card').forEach(b => {
                                b.style.borderColor = 'rgba(255,255,255,0.1)';
                                b.style.background = 'transparent';
                            });
                            optBtn.style.borderColor = '#7B1FA2';
                            optBtn.style.background = 'rgba(123, 31, 162, 0.05)';
                            window.selectedOption = idx;
                        };
                        container.appendChild(optBtn);
                    });
                    
                    window.selectedOption = null;
                }

                function submitAnswer() {
                    if (window.selectedOption === null) {
                        alert('Please select an option');
                        return;
                    }
                    
                    if (window.selectedOption === questions[currentQuestion].correct) {
                        score++;
                    }
                    
                    currentQuestion++;
                    if (currentQuestion < questions.length) {
                        loadQuestion();
                    } else {
                        showResults();
                    }
                }

                function showResults() {
                    document.getElementById('quiz-question-view').style.display = 'none';
                    document.getElementById('quiz-result-view').style.display = 'block';
                    
                    const percent = Math.round((score / questions.length) * 100);
                    document.getElementById('final-score').innerText = percent + '%';
                    document.getElementById('correct-count').innerText = score + ' / ' + questions.length + ' Correct';
                    document.getElementById('result-subtitle').innerText = 'Results for ' + selectedType + ' - Chapter ' + selectedChapter;
                    
                    const scoreText = document.getElementById('score-text');
                    if (percent >= 80) {
                        scoreText.innerText = 'Exceptional Mastery!';
                        scoreText.style.color = '#4ADE80';
                    } else if (percent >= 60) {
                        scoreText.innerText = 'Good Progress!';
                        scoreText.style.color = '#FACC15';
                    } else {
                        scoreText.innerText = 'Needs Review';
                        scoreText.style.color = '#F87171';
                    }
                }
            </script>

            <style>
                .chap-btn {
                    padding: 10px;
                    border-radius: 10px;
                    border: 1px solid rgba(255,255,255,0.1);
                    background: rgba(255,255,255,0.02);
                    color: #fff;
                    font-weight: bold;
                    transition: 0.3s;
                    cursor: pointer;
                }
                .chap-btn:hover { background: rgba(123, 31, 162, 0.1); }
                .chap-btn.active { background: #7B1FA2; border-color: #7B1FA2; box-shadow: 0 4px 15px rgba(123, 31, 162, 0.3); }
                
                .selection-card:hover { transform: translateY(-5px); background: rgba(123, 31, 162, 0.05); }
                
                .quiz-option-item:hover { 
                    border-color: rgba(123, 31, 162, 0.5) !important;
                    background: rgba(123, 31, 162, 0.02) !important;
                    transform: translateX(5px);
                }
            </style>

    <?php elseif ($page === 'settings'): // ═══ SETTINGS PAGE — App-Mirrored Content ═══
        require_once __DIR__ . '/api/db.php';
        $_sdb = getDB();
        $uid  = (int)($_SESSION['user_id'] ?? 0);

        // Fetch user row
        $sUser = null;
        if ($uid) {
            $ss = $_sdb->prepare("SELECT name,email,role,bio,profile_image,status,created_at,last_login FROM users WHERE id=?");
            $ss->bind_param('i', $uid); $ss->execute();
            $sUser = $ss->get_result()->fetch_assoc(); $ss->close();
        }
        $memberSince = $sUser ? date('M Y', strtotime($sUser['created_at'])) : 'N/A';
        $acctStatus  = ($sUser && $sUser['status'] === 'Active') ? 'Active' : ($sUser['status'] ?? 'Unknown');
        $userBio     = htmlspecialchars($sUser['bio'] ?? 'No biography provided by the user.');

        // Dataset count
        $dsS = $_sdb->prepare("SELECT COUNT(*) AS c FROM datasets WHERE uploaded_by=?");
        $dsS->bind_param('i', $uid); $dsS->execute();
        $dsCnt = (int)($dsS->get_result()->fetch_assoc()['c'] ?? 0); $dsS->close();

        // Total platform datasets
        $tds = $_sdb->query("SELECT COUNT(*) AS c FROM datasets");
        $totalDs = $tds ? (int)($tds->fetch_assoc()['c'] ?? 0) : 0;

        // Latest deployed AI model
        $aiVersion = 'v2.4'; $aiAccuracy = 'N/A';
        $aiStmt = $_sdb->query("SELECT version,training_accuracy FROM ai_models WHERE status='Deployed' ORDER BY last_trained DESC LIMIT 1");
        if ($aiStmt && $aiR = $aiStmt->fetch_assoc()) { $aiVersion = $aiR['version']; $aiAccuracy = $aiR['training_accuracy'].'%'; }

        // Storage from uploads/
        $uploadFiles = array_filter(glob('uploads/*') ?: [], 'is_file');
        $totalBytes  = array_reduce($uploadFiles, fn($c,$f) => $c + filesize($f), 0);
        $usedMB      = round($totalBytes / 1024 / 1024, 2);
        $usedPct     = min(100, round(($usedMB / 100) * 100, 1));
        $storageColor= $usedPct > 80 ? '#EF4444' : ($usedPct > 50 ? '#F59E0B' : '#1565C0');

        // Platform datasets for global stats
        $platformDatasets = [];
        $pDs = $_sdb->query("SELECT d.name,d.signal_type,d.technique,d.file_size,d.status,d.upload_date FROM datasets d ORDER BY d.upload_date DESC LIMIT 10");
        if ($pDs) while ($dr = $pDs->fetch_assoc()) $platformDatasets[] = $dr;

        // User-specific datasets for history
        $userDatasets = [];
        $udS = $_sdb->prepare("SELECT name, signal_type, technique, file_size, status, upload_date FROM datasets WHERE uploaded_by=? ORDER BY upload_date DESC LIMIT 10");
        $udS->bind_param('i', $uid); $udS->execute();
        $ur = $udS->get_result();
        while($dr = $ur->fetch_assoc()) $userDatasets[] = $dr;
        $udS->close();

        // Dynamic Learning History
        $learningHistory = [];
        $lhS = $_sdb->prepare("SELECT module_name, completion_percentage, last_updated FROM user_progress WHERE user_id=? ORDER BY last_updated DESC LIMIT 5");
        $lhS->bind_param('i', $uid); $lhS->execute();
        $lhr = $lhS->get_result();
        while($lr = $lhr->fetch_assoc()) {
            $mTitle = $modules[$lr['module_name']]['title'] ?? $lr['module_name'];
            $learningHistory[] = [
                'module' => $mTitle,
                'progress' => $lr['completion_percentage'],
                'date' => date('M d', strtotime($lr['last_updated']))
            ];
        }
        $lhS->close();

        // Privacy policy text (from strings.xml)
        $privacyText = "Last Updated: February 11, 2026\n\nThis Privacy Policy describes how we collect, use, and protect your information when you use the BioElectrode Learning Application.\n\n1. Information We Collect\nWe collect: Name, email address, profile information, learning data (progress, quiz scores), usage data, and device information.\n\n2. How We Use Your Information\nWe use your information to provide educational services, personalize your learning experience, track progress, send notifications, and improve our app.\n\n3. Data Security\nWe implement industry-standard security: HTTPS/SSL encryption, secure password storage, regular security audits, and limited access to personal information.\n\n4. Your Rights\nYou have the right to: access your personal information, correct inaccurate data, request deletion, opt-out of communications, and export your learning data.\n\n5. Data Retention\nWe retain your information as long as your account is active. Account deletion requests are processed within 30 days.\n\n6. Contact Us\nEmail: privacy@bioelectrode.app\nWebsite: www.bioelectrode.app/privacy";
    ?>

    <style>
        /* ─── Settings Page: App-Mirrored Web View ─── */
        .set-page { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }

        /* Hero banner (mirrors gradient_header) */
        .set-hero {
            background: linear-gradient(135deg, #1565C0 0%, #7B1FA2 60%, #E65100 100%);
            border-radius: 28px; padding: 32px 36px;
            display: flex; align-items: center; gap: 24px;
            position: relative; overflow: hidden;
        }
        .set-hero::after {
            content:''; position:absolute; right:-60px; top:-60px;
            width:200px; height:200px; background:rgba(255,255,255,.06);
            border-radius:50%;
        }
        .set-hero-av {
            width:76px; height:76px; border-radius:22px;
            background:rgba(255,255,255,.18); border:3px solid rgba(255,255,255,.3);
            display:flex; align-items:center; justify-content:center;
            font-size:2rem; font-weight:900; color:#fff;
            overflow:hidden; flex-shrink:0; z-index:1;
        }
        .set-hero-av img { width:100%; height:100%; object-fit:cover; }
        .set-hero-info  { flex:1; z-index:1; }
        .set-hero-info h2 { color:#fff; font-size:1.6rem; font-weight:900; margin-bottom:3px; }
        .set-hero-info .sh-sub { color:rgba(255,255,255,.72); font-size:.85rem; margin-bottom:14px; }
        .set-hero-pills { display:flex; flex-wrap:wrap; gap:8px; }
        .set-pill {
            background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
            color:#fff; padding:4px 12px; border-radius:30px;
            font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
        }

        /* Section label */
        .set-label {
            font-size:.7rem; font-weight:800; text-transform:uppercase;
            letter-spacing:1.5px; color:var(--text3); margin-bottom:8px; padding-left:4px;
        }

        /* Card list */
        .set-list { display:flex; flex-direction:column; gap:2px; }

        /* Item row */
        .set-row {
            display:flex; align-items:center; gap:15px;
            background:rgba(255,255,255,.04); padding:16px 20px;
            border:1px solid rgba(255,255,255,.07); transition:.22s;
            text-decoration:none;
        }
        .set-row:first-child { border-radius:18px 18px 4px 4px; }
        .set-row:last-child  { border-radius:4px 4px 18px 18px; }
        .set-row:only-child  { border-radius:18px; }
        .set-row:hover { background:rgba(255,255,255,.09); transform:translateX(4px); }
        label.set-row { cursor:pointer; }

        .set-ico {
            width:42px; height:42px; border-radius:13px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; flex-shrink:0;
        }
        .ic-purple { background:rgba(123,31,162,.2);  }
        .ic-blue   { background:rgba(21,101,192,.2);  }
        .ic-teal   { background:rgba(0,137,123,.2);   }
        .ic-orange { background:rgba(230,81,0,.2);    }
        .ic-grey   { background:rgba(120,120,120,.15);}
        .ic-pink   { background:rgba(219,39,119,.2);  }
        .ic-green  { background:rgba(5,150,105,.2);   }
        .ic-red    { background:rgba(211,47,47,.12);  }

        .set-txt { flex:1; }
        .set-title { font-size:.9rem; font-weight:700; color:#fff; margin-bottom:2px; }
        .set-sub   { font-size:.74rem; color:var(--text3); line-height:1.4; }
        .set-chev  { color:var(--text3); font-size:1.1rem; line-height:1; }

        /* Toggle */
        .set-tog { appearance:none; width:44px; height:23px; border-radius:30px; background:rgba(255,255,255,.12);
                   position:relative; cursor:pointer; transition:.35s; flex-shrink:0; border:none; outline:none; }
        .set-tog.blue:checked   { background:#1565C0; box-shadow:0 0 8px rgba(21,101,192,.5); }
        .set-tog.orange:checked { background:#FF9800; box-shadow:0 0 8px rgba(255,152,0,.4); }
        .set-tog::before { content:''; position:absolute; width:17px; height:17px; border-radius:50%;
                           background:#fff; top:3px; left:3px; transition:.35s; box-shadow:0 1px 4px rgba(0,0,0,.3); }
        .set-tog:checked::before { transform:translateX(21px); }

        /* Storage bar */
        .stor-bar-bg   { height:6px; background:rgba(255,255,255,.08); border-radius:6px; overflow:hidden; margin:8px 0 4px; }
        .stor-bar-fill { height:100%; border-radius:6px; transition:.6s; }

        /* Mini table */
        .mini-tbl { width:100%; border-collapse:collapse; margin-top:10px; }
        .mini-tbl th { font-size:.63rem; text-transform:uppercase; letter-spacing:1px; color:var(--text3); padding:9px 12px; text-align:left; border-bottom:1px solid rgba(255,255,255,.05); }
        .mini-tbl td { font-size:.8rem; padding:11px 12px; border-bottom:1px solid rgba(255,255,255,.04); color:var(--text2); vertical-align:middle; }
        .mini-tbl tr:last-child td { border-bottom:none; }
        .mini-tbl tr:hover td { background:rgba(255,255,255,.025); }
        .sig-tag { padding:2px 8px; border-radius:6px; font-size:.62rem; font-weight:800; display:inline-block; }

        /* FAQ accordion */
        .faq-item { border-bottom:1px solid rgba(255,255,255,.05); padding:14px 0; }
        .faq-item:last-child { border-bottom:none; }
        .faq-q { font-size:.88rem; font-weight:700; color:#1565C0; margin-bottom:6px; display:flex; align-items:flex-start; gap:8px; }
        .faq-a { font-size:.8rem; color:var(--text2); line-height:1.6; }

        /* Download content items */
        .dl-row { display:flex; align-items:center; gap:14px; padding:14px 18px;
                  background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07);
                  border-radius:14px; transition:.2s; cursor:pointer; }
        .dl-row:hover { background:rgba(255,255,255,.08); }
        .dl-ico { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
        .dl-txt { flex:1; }
        .dl-title { font-size:.88rem; font-weight:700; color:#fff; }
        .dl-sub   { font-size:.72rem; color:var(--text3); margin-top:2px; }
        .dl-btn   { font-size:1.1rem; color:var(--text3); transition:.2s; }
        .dl-row:hover .dl-btn { color:var(--blue-l); }

        /* Logout */
        .set-logout {
            width:100%; padding:16px; border-radius:18px;
            border:1.5px solid rgba(211,47,47,.4); background:rgba(211,47,47,.07);
            color:#FC8181; font-size:.95rem; font-weight:800;
            cursor:pointer; transition:.3s; display:flex; align-items:center; justify-content:center; gap:10px; text-decoration:none;
        }
        .set-logout:hover { background:rgba(211,47,47,.18); border-color:rgba(211,47,47,.8); color:#FCA5A5; transform:translateY(-2px); }

        /* Modal overlay for Privacy / T&C */
        .set-modal { background:#1a1f2e; border:1px solid rgba(255,255,255,.1); border-radius:24px; max-width:600px; width:100%; max-height:80vh; display:flex; flex-direction:column; }
        .set-modal-head { padding:24px 28px 18px; border-bottom:1px solid rgba(255,255,255,.07); display:flex; align-items:center; justify-content:space-between; }
        .set-modal-head h3 { font-size:1.1rem; font-weight:800; color:#fff; }
        .set-modal-close { background:rgba(255,255,255,.08); border:none; color:var(--text3); width:32px; height:32px; border-radius:50%; cursor:pointer; font-size:1rem; transition:.2s; }
        .set-modal-close:hover { background:rgba(255,255,255,.15); color:#fff; }
        .set-modal-body { padding:24px 28px; overflow-y:auto; flex:1; font-size:.85rem; color:var(--text2); line-height:1.8; white-space:pre-wrap; }

        /* History Card Specifics */
        .hist-tabs { display: flex; gap: 4px; background: rgba(0,0,0,0.2); padding: 4px; border-radius: 12px; margin-bottom: 20px; }
        .hist-tab { 
            flex: 1; padding: 10px; text-align: center; font-size: 0.75rem; font-weight: 800; 
            color: var(--text3); cursor: pointer; border-radius: 9px; transition: 0.2s;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .hist-tab.active { background: rgba(255,255,255,0.08); color: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .hist-tab:hover:not(.active) { background: rgba(255,255,255,0.04); color: var(--text2); }

        .progress-item { 
            display: flex; align-items: center; gap: 15px; padding: 12px; 
            background: rgba(255,255,255,0.03); border-radius: 14px; margin-bottom: 10px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .prog-circle { 
            width: 36px; height: 36px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 900;
            color: #fff; flex-shrink: 0; position: relative;
        }
        .prog-circle::after {
            content: ''; position: absolute; inset: -3px; border-radius: 50%;
            border: 3px solid transparent; border-top-color: var(--blue-l);
            transform: rotate(45deg);
        }
        .prog-circle.complete { border-color: rgba(52,211,153,0.3); color: #34D399; }
        .prog-circle.complete::after { border-top-color: #34D399; transform: rotate(0deg); border: 3px solid #34D399; }

        .hist-empty { text-align: center; padding: 40px 20px; color: var(--text3); opacity: 0.7; }
        .hist-empty i { font-size: 2.5rem; display: block; margin-bottom: 15px; }
    </style>

    <!-- App Map Modal -->
    <div class="set-modal-overlay" id="appMapModal">
        <div class="set-modal" style="background:#0F172A; max-width: 860px; border: 1px solid rgba(255,255,255,.15); box-shadow: 0 20px 40px rgba(0,0,0,0.8);">
            <div class="set-modal-head" style="background:linear-gradient(135deg,#1E3A8A,#4C1D95,#b45309); border-bottom:1px solid rgba(255,255,255,.1);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="background:rgba(255,255,255,.2);padding:8px;border-radius:12px;font-size:1.3rem;">🗺️</div>
                    <div>
                        <h3 style="color:#fff;font-size:1.2rem;margin:0;font-weight:900;">App Map &amp; Architecture</h3>
                        <div style="color:rgba(255,255,255,.7);font-size:.75rem;font-weight:600;margin-top:2px;">Complete navigation structure of BioElectrode AI</div>
                    </div>
                </div>
                <button class="set-modal-close" style="background:rgba(0,0,0,.2);color:#fff;" onclick="document.getElementById('appMapModal').classList.remove('open')">✕</button>
            </div>
            
            <div class="set-modal-body" style="padding:0; background: radial-gradient(circle at 50% -20%, rgba(30,58,138,0.2) 0%, transparent 80%);">
                <!-- App Map Grid Layout with Connection Flow -->
                <div style="padding:32px 36px; display:grid; grid-template-columns: repeat(3, 1fr); gap:40px; position:relative;">
                    
                    <!-- Flow SVG overlay strictly pinned between columns -->
                    <svg style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; z-index:0;" preserveAspectRatio="none">
                        <!-- Connecting Auth -> Core (Phase 1 -> 2) -->
                        <path d="M 285 140 C 320 140, 310 180, 345 180" fill="none" stroke="rgba(96,165,250,.4)" stroke-width="3" stroke-dasharray="6,4" />
                        <!-- Connecting Core -> Dash (Phase 2 -> 3) -->
                        <path d="M 585 180 C 620 180, 610 120, 645 120" fill="none" stroke="rgba(192,132,252,.4)" stroke-width="3" stroke-dasharray="6,4" />
                        <!-- Connecting Dash -> Personal (Phase 3 -> 4) -->
                        <path d="M 740 180 L 740 215" fill="none" stroke="rgba(52,211,153,.4)" stroke-width="3" stroke-dasharray="6,4" />
                        <!-- Arrows -->
                        <polygon points="340,176 348,180 340,184" fill="rgba(96,165,250,.8)" />
                        <polygon points="638,116 646,120 638,124" fill="rgba(192,132,252,.8)" />
                        <polygon points="736,210 740,218 744,210" fill="rgba(52,211,153,.8)" />
                    </svg>

                    <!-- 1. AUTH & ONBOARDING -->
                    <div style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:20px; padding:20px; position:relative; z-index:1;">
                        <h4 style="color:#60A5FA; font-size:.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                            <span style="background:rgba(96,165,250,.2); padding:4px 8px; border-radius:6px;">1</span> Authentication
                        </h4>
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <div style="background:linear-gradient(90deg, #1E40AF, #1E3A8A); color:#fff; padding:12px 16px; border-radius:12px; font-weight:700; font-size:.85rem; display:flex; align-items:center; gap:10px;"><span style="font-size:1.1rem;">⚡</span> Splash Screen</div>
                            <div style="margin-left:22px; width:2px; height:10px; background:rgba(255,255,255,.1);"></div>
                            <div style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); color:#fff; padding:12px 16px; border-radius:12px; font-weight:700; font-size:.85rem; display:flex; align-items:center; gap:10px;"><span style="font-size:1.1rem;">📘</span> Onboarding</div>
                            <div style="margin-left:22px; width:2px; height:10px; background:rgba(255,255,255,.1);"></div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                                <div style="background:rgba(52,211,153,.15); color:#34D399; text-align:center; padding:10px; border-radius:10px; font-weight:800; font-size:.75rem;">Login</div>
                                <div style="background:rgba(167,139,250,.15); color:#A78BFA; text-align:center; padding:10px; border-radius:10px; font-weight:800; font-size:.75rem;">Register</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. MAIN DASHBOARD / CORE -->
                    <div style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:20px; padding:20px; position:relative; z-index:1;">
                        <h4 style="color:#C084FC; font-size:.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                            <span style="background:rgba(192,132,252,.2); padding:4px 8px; border-radius:6px;">2</span> Core Topics
                        </h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                            <div style="background:rgba(37,99,235,.15); border:1px solid rgba(37,99,235,.3); padding:16px 12px; border-radius:14px; text-align:center;">
                                <div style="font-size:1.6rem; margin-bottom:6px;">📘</div><div style="color:#fff; font-size:.75rem; font-weight:700;">Learn</div>
                            </div>
                            <div style="background:rgba(124,58,237,.15); border:1px solid rgba(124,58,237,.3); padding:16px 12px; border-radius:14px; text-align:center;">
                                <div style="font-size:1.6rem; margin-bottom:6px;">⚖️</div><div style="color:#fff; font-size:.75rem; font-weight:700;">Compare</div>
                            </div>
                            <div style="background:rgba(217,119,6,.15); border:1px solid rgba(217,119,6,.3); padding:16px 12px; border-radius:14px; text-align:center;">
                                <div style="font-size:1.6rem; margin-bottom:6px;">🎛️</div><div style="color:#fff; font-size:.75rem; font-weight:700;">Simulator</div>
                            </div>
                            <div style="background:rgba(5,150,105,.15); border:1px solid rgba(5,150,105,.3); padding:16px 12px; border-radius:14px; text-align:center;">
                                <div style="font-size:1.6rem; margin-bottom:6px;">🤖</div><div style="color:#fff; font-size:.75rem; font-weight:700;">AI Analysis</div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. ACTIONS & PROFILE -->
                    <div style="display:flex; flex-direction:column; gap:20px; position:relative; z-index:1;">
                        <div style="background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06); border-radius:20px; padding:20px;">
                            <h4 style="color:#F87171; font-size:.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:16px; font-weight:800; display:flex; align-items:center; gap:8px;">
                                <span style="background:rgba(248,113,113,.2); padding:4px 8px; border-radius:6px;">3</span> Dash &amp; Actions
                            </h4>
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                                <div style="flex:1; background:linear-gradient(135deg,#9D174D,#BE123C); color:#fff; text-align:center; padding:12px; border-radius:12px; font-size:.75rem; font-weight:800;">📝 Quiz</div>
                                <div style="flex:1; background:rgba(255,255,255,.08); color:#fff; text-align:center; padding:12px; border-radius:12px; font-size:.75rem; font-weight:700;">🏥 Cases</div>
                            </div>
                            <div style="background:rgba(255,255,255,.08); color:#fff; text-align:center; padding:12px; border-radius:12px; font-size:.75rem; font-weight:700;">📚 Resources</div>
                        </div>

                        <div style="background:linear-gradient(180deg, rgba(20,83,45,.4), rgba(20,83,45,.1)); border:1px solid rgba(52,211,153,.2); border-radius:20px; padding:20px;">
                            <h4 style="color:#34D399; font-size:.7rem; text-transform:uppercase; letter-spacing:1px; margin-bottom:14px; font-weight:800; display:flex; align-items:center; gap:8px;">
                                <span style="background:rgba(52,211,153,.2); padding:4px 8px; border-radius:6px;">4</span> Personal
                            </h4>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="text-align:center;"><div style="background:rgba(0,0,0,.3);width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 4px;font-size:1.1rem;">👤</div><div style="font-size:.65rem;color:#A7F3D0;font-weight:700;">Profile</div></div>
                                <div style="text-align:center;"><div style="background:rgba(0,0,0,.3);width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 4px;font-size:1.1rem;">⚙️</div><div style="font-size:.65rem;color:#A7F3D0;font-weight:700;">Settings</div></div>
                                <div style="text-align:center;"><div style="background:rgba(0,0,0,.3);width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 4px;font-size:1.1rem;">📖</div><div style="font-size:.65rem;color:#A7F3D0;font-weight:700;">Glossary</div></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="background:rgba(0,0,0,.2); border-top:1px solid rgba(255,255,255,.05); padding:16px; text-align:center;">
                    <div style="display:inline-flex; align-items:center; gap:12px;">
                        <span style="display:inline-block; width:8px; height:8px; background:#60A5FA; border-radius:50%; box-shadow:0 0 10px #60A5FA;"></span>
                        <span style="color:#60A5FA; font-size:.75rem; font-weight:700;">Entry</span>
                        <div style="width:20px; height:2px; background:rgba(255,255,255,.1);"></div>
                        <span style="display:inline-block; width:8px; height:8px; background:#C084FC; border-radius:50%; box-shadow:0 0 10px #C084FC;"></span>
                        <span style="color:#C084FC; font-size:.75rem; font-weight:700;">Education</span>
                        <div style="width:20px; height:2px; background:rgba(255,255,255,.1);"></div>
                        <span style="display:inline-block; width:8px; height:8px; background:#F87171; border-radius:50%; box-shadow:0 0 10px #F87171;"></span>
                        <span style="color:#F87171; font-size:.75rem; font-weight:700;">Engagement</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="set-modal-overlay" id="privacyModal">
        <div class="set-modal">
            <div class="set-modal-head">
                <h3>🛡️ Privacy Policy</h3>
                <button class="set-modal-close" onclick="document.getElementById('privacyModal').classList.remove('open')">✕</button>
            </div>
            <div class="set-modal-body"><?= nl2br(htmlspecialchars($privacyText)) ?></div>
        </div>
    </div>

    <!-- T&C Modal -->
    <div class="set-modal-overlay" id="termsModal">
        <div class="set-modal">
            <div class="set-modal-head">
                <h3>📄 Terms &amp; Conditions</h3>
                <button class="set-modal-close" onclick="document.getElementById('termsModal').classList.remove('open')">✕</button>
            </div>
            <div class="set-modal-body">Last Updated: February 11, 2026

By using the BioElectrode AI Learning Application, you agree to the following terms:

1. Acceptance of Terms
By accessing or using our app, you agree to be bound by these Terms & Conditions and all applicable laws and regulations.

2. Educational Use Only
All content provided in this application is for educational purposes only. Educational decisions should always be made by qualified healthcare professionals.

3. User Account
You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.

4. Intellectual Property
All content, including learning modules, datasets, AI models, and documentation, is the intellectual property of BioElectrode AI and is protected by applicable copyright laws.

5. Prohibited Activities
You may not: reproduce or redistribute any content without permission, use the app for any unlawful purpose, attempt to gain unauthorized access to any part of the service, or transmit harmful or malicious code.

6. Disclaimer of Warranties
The app is provided "as is" without warranties of any kind. We do not guarantee uninterrupted or error-free service.

7. Limitation of Liability
BioElectrode AI shall not be liable for any indirect, incidental, or consequential damages resulting from your use of the app.

8. Changes to Terms
We reserve the right to modify these terms at any time. Continued use after changes constitutes acceptance of the new terms.

Contact: legal@bioelectrode.app
Website: www.bioelectrode.app/terms</div>
        </div>
    </div>

    <!-- Download Content Modal -->
    <div class="set-modal-overlay" id="downloadModal">
        <div class="set-modal">
            <div class="set-modal-head">
                <h3>⬇️ Download Content</h3>
                <button class="set-modal-close" onclick="document.getElementById('downloadModal').classList.remove('open')">✕</button>
            </div>
            <div class="set-modal-body" style="white-space:normal;">
                <p style="color:var(--text2);font-size:.82rem;background:rgba(21,101,192,.1);border-radius:12px;padding:12px 16px;margin-bottom:20px;line-height:1.7;">
                    ℹ️ Downloaded content will be available offline. You can delete downloads anytime to free up space.
                </p>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);font-weight:800;margin-bottom:14px;">Available for Download</div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <div class="dl-row">
                        <div class="dl-ico ic-blue">📚</div>
                        <div class="dl-txt"><div class="dl-title">Learning Modules</div><div class="dl-sub">13 modules • 245 MB</div></div>
                        <span class="dl-btn">⬇️</span>
                    </div>
                    <div class="dl-row">
                        <div class="dl-ico ic-purple">🏥</div>
                        <div class="dl-txt"><div class="dl-title">Practice Scenarios</div><div class="dl-sub">8 cases • 128 MB</div></div>
                        <span class="dl-btn">⬇️</span>
                    </div>
                    <div class="dl-row">
                        <div class="dl-ico ic-teal">📖</div>
                        <div class="dl-txt"><div class="dl-title">Glossary Terms</div><div class="dl-sub">19 terms • 12 MB</div></div>
                        <span class="dl-btn">⬇️</span>
                    </div>
                    <div class="dl-row">
                        <div class="dl-ico ic-orange">📋</div>
                        <div class="dl-txt"><div class="dl-title">Resources Library</div><div class="dl-sub">23 resources • 156 MB</div></div>
                        <span class="dl-btn">⬇️</span>
                    </div>
                </div>
                <div style="margin-top:20px;display:flex;flex-direction:column;gap:10px;">
                    <button style="padding:14px;background:linear-gradient(135deg,#1565C0,#00897B);border:none;border-radius:14px;color:#fff;font-weight:800;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">⬇️ Download All Content</button>
                    <div style="font-size:.72rem;color:var(--text3);text-align:center;">Total size: 541 MB</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help & Support Modal -->
    <div class="set-modal-overlay" id="helpModal">
        <div class="set-modal">
            <div class="set-modal-head">
                <h3>❓ Help &amp; Support</h3>
                <button class="set-modal-close" onclick="document.getElementById('helpModal').classList.remove('open')">✕</button>
            </div>
            <div class="set-modal-body" style="white-space:normal;">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);font-weight:800;margin-bottom:14px;">How can we help you?</div>
                <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:22px;">
                    <a href="dashboard.php?page=learn" style="text-decoration:none;" class="dl-row">
                        <div class="dl-ico ic-teal">🚀</div>
                        <div class="dl-txt"><div class="dl-title">Getting Started</div><div class="dl-sub">Learn the basics</div></div>
                        <span class="dl-btn">›</span>
                    </a>
                    <a href="dashboard.php?page=profile" style="text-decoration:none;" class="dl-row">
                        <div class="dl-ico ic-purple">👤</div>
                        <div class="dl-txt"><div class="dl-title">Account &amp; Profile</div><div class="dl-sub">Manage your account</div></div>
                        <span class="dl-btn">›</span>
                    </a>
                    <a href="dashboard.php?page=learn" style="text-decoration:none;" class="dl-row">
                        <div class="dl-ico ic-blue">📊</div>
                        <div class="dl-txt"><div class="dl-title">Learning &amp; Progress</div><div class="dl-sub">Track your learning</div></div>
                        <span class="dl-btn">›</span>
                    </a>
                    <div class="dl-row" onclick="alert('For technical issues, please email support@bioelectrode.app')">
                        <div class="dl-ico ic-orange">⚙️</div>
                        <div class="dl-txt"><div class="dl-title">Technical Issues</div><div class="dl-sub">Troubleshoot problems</div></div>
                        <span class="dl-btn">›</span>
                    </div>
                </div>
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);font-weight:800;margin-bottom:14px;">Frequently Asked Questions</div>
                <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:16px;">
                    <div class="faq-item">
                        <div class="faq-q">❓ How do I reset my password?</div>
                        <div class="faq-a">Go to the login screen and click 'Forgot Password'. Enter your email address and we'll send you instructions to reset your password.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-q">❓ How do I track my learning progress?</div>
                        <div class="faq-a">Your progress is automatically tracked as you complete modules and quizzes. View your profile to see detailed statistics and achievements.</div>
                    </div>
                    <div class="faq-item">
                        <div class="faq-q">❓ Can I use the app offline?</div>
                        <div class="faq-a">Some content can be downloaded for offline access. Go to Settings → Download Content to manage your offline resources.</div>
                    </div>
                </div>
                <div style="margin-top:22px;">
                    <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);font-weight:800;margin-bottom:12px;">Contact Us</div>
                    <div style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:12px;">
                        <a href="mailto:support@bioelectrode.app?subject=BioElectrode App Support Request" class="dl-row" style="text-decoration:none;">
                            <div class="dl-ico ic-blue">📧</div>
                            <div class="dl-txt"><div class="dl-title">Email Support</div><div class="dl-sub" style="color:#1565C0;">support@bioelectrode.app</div></div>
                            <span class="dl-btn">↗</span>
                        </a>
                        <a href="https://www.bioelectrode.app" target="_blank" class="dl-row" style="text-decoration:none;">
                            <div class="dl-ico ic-teal">🌐</div>
                            <div class="dl-txt"><div class="dl-title">Visit Our Website</div><div class="dl-sub" style="color:#00897B;">www.bioelectrode.app</div></div>
                            <span class="dl-btn">↗</span>
                        </a>
                        <div style="text-align:center;font-size:.72rem;color:var(--text3);padding-top:6px;">⏱ We typically respond within 24 hours</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════
         SETTINGS PAGE BODY
    ════════════════════════════════════════════ -->
    <div class="set-page">

        <!-- ①  HERO (matches Android gradient_header) -->
        <div class="set-hero">
            <div class="set-hero-av">
                <?php if (!empty($_SESSION['profile_image']) && file_exists($_SESSION['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($_SESSION['profile_image']) ?>" alt="Profile">
                <?php else: ?>
                    <?= $userInitial ?>
                <?php endif; ?>
            </div>
            <div class="set-hero-info">
                <h2><?= $userName ?></h2>
                <div class="sh-sub"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?> &nbsp;·&nbsp; <?= $userRole ?></div>
                <div class="set-hero-pills">
                    <span class="set-pill">⚡ <?= $acctStatus ?></span>
                    <span class="set-pill">📅 Since <?= $memberSince ?></span>
                    <span class="set-pill">🤖 AI <?= $aiVersion ?></span>
                    <span class="set-pill">📊 <?= $totalDs ?> Datasets</span>
                </div>
            </div>
        </div>
        
        <?php if ($_SESSION['user_role'] === 'Admin'): ?>
        <!-- ①.A ADMINISTRATOR CONTROLS (Only for Admins) -->
        <div style="margin-top:20px;">
            <div class="set-label" style="color:#F472B6;">Administrator Controls</div>
            <div class="set-list">
                <a href="admin_dashboard.php" class="set-row" style="background:rgba(219,39,119,0.06); border-color:rgba(219,39,119,0.15);">
                    <div class="set-ico ic-pink">📊</div>
                    <div class="set-txt">
                        <div class="set-title">Admin Dashboard</div>
                        <div class="set-sub">Manage users, view system logs & global datasets</div>
                    </div>
                    <span class="set-chev">🔑</span>
                </a>
                <a href="admin_settings.php" class="set-row" style="background:rgba(37,99,235,0.06); border-color:rgba(37,99,235,0.15);">
                    <div class="set-ico ic-blue">⚙️</div>
                    <div class="set-txt">
                        <div class="set-title">Platform Infrastructure</div>
                        <div class="set-sub">Health metrics, AI tuning & database maintenance</div>
                    </div>
                    <span class="set-chev">🛡️</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- ② ACCOUNT -->
        <div>
            <div class="set-label">Account</div>
            <div class="set-list">
                <a href="dashboard.php?page=profile" class="set-row">
                    <div class="set-ico ic-purple">👤</div>
                    <div class="set-txt">
                        <div class="set-title">Edit Profile</div>
                        <div class="set-sub">Update your personal information</div>
                    </div>
                    <span class="set-chev">›</span>
                </a>
            </div>
        </div>

        <!-- ③ PREFERENCES -->
        <div>
            <div class="set-label">Preferences</div>
            <div class="set-list">
                <label class="set-row" for="tog-notif">
                    <div class="set-ico ic-blue">🔔</div>
                    <div class="set-txt">
                        <div class="set-title">Notifications</div>
                        <div class="set-sub">Study reminders and updates</div>
                    </div>
                    <input type="checkbox" id="tog-notif" class="set-tog blue" checked>
                </label>
                <label class="set-row" for="tog-dark">
                    <div class="set-ico ic-grey">🌙</div>
                    <div class="set-txt">
                        <div class="set-title">Dark Mode</div>
                        <div class="set-sub">Easier on the eyes — currently active</div>
                    </div>
                    <input type="checkbox" id="tog-dark" class="set-tog blue" checked>
                </label>
                <label class="set-row" for="tog-sound">
                    <div class="set-ico ic-orange">🔊</div>
                    <div class="set-txt">
                        <div class="set-title">Sound Effects</div>
                        <div class="set-sub">Button clicks and alerts</div>
                    </div>
                    <input type="checkbox" id="tog-sound" class="set-tog orange" checked>
                </label>
            </div>
        </div>

        <!-- ④ LANGUAGE & REGION -->
        <div>
            <div class="set-label">Language &amp; Region</div>
            <div class="set-list">
                <div class="set-row" style="cursor:default;">
                    <div class="set-ico ic-blue">🌐</div>
                    <div class="set-txt">
                        <div class="set-title">Language</div>
                        <div class="set-sub">Choose your preferred language</div>
                    </div>
                    <select id="langSel"
                        style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#fff;padding:7px 12px;border-radius:11px;font-size:.8rem;font-weight:700;cursor:pointer;outline:none;">
                        <option value="en">🇬🇧 English (Default)</option>
                        <option value="es">🇪🇸 Español (Spanish)</option>
                        <option value="fr">🇫🇷 Français (French)</option>
                        <option value="de">🇩🇪 Deutsch (German)</option>
                        <option value="hi">🇮🇳 हिंदी (Hindi)</option>
                        <option value="te">🇮🇳 తెలుగు (Telugu)</option>
                        <option value="zh">🇨🇳 中文 (Chinese)</option>
                        <option value="ja">🇯🇵 日本語 (Japanese)</option>
                        <option value="ar">🇸🇦 العربية (Arabic)</option>
                    </select>
                </div>
            </div>
            <div style="font-size:.7rem;color:var(--text3);padding:6px 4px;">Language changes will be applied instantly across the entire platform</div>
        </div>

        <!-- ⑤ DATA & STORAGE -->
        <div>
            <div class="set-label">Data &amp; Storage</div>
            <div class="set-list">

                <!-- Download Content -->
                <div class="set-row" onclick="document.getElementById('downloadModal').classList.add('open')">
                    <div class="set-ico ic-teal">⬇️</div>
                    <div class="set-txt">
                        <div class="set-title">Download Content</div>
                        <div class="set-sub">Save for offline access</div>
                    </div>
                    <span class="set-chev">›</span>
                </div>

                <!-- Storage Usage (live from uploads/) -->
                <div class="set-row" style="flex-direction:column;align-items:stretch;cursor:default;padding:20px;">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
                        <div class="set-ico ic-orange">💾</div>
                        <div class="set-txt">
                            <div class="set-title">Storage Usage</div>
                            <div class="set-sub"><?= $usedMB ?> MB used &nbsp;·&nbsp; <?= count($uploadFiles) ?> file(s)</div>
                        </div>
                        <span style="font-size:.82rem;font-weight:800;color:<?= $storageColor ?>;"><?= $usedPct ?>%</span>
                    </div>
                    <div class="stor-bar-bg">
                        <div class="stor-bar-fill" style="width:<?= max(1,$usedPct) ?>%;background:<?= $storageColor ?>;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.66rem;color:var(--text3);">
                        <span>0 MB</span><span>100 MB Free Tier</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ⑥ HISTORY & PROGRESS -->
        <div>
            <div class="set-label">History &amp; Progress</div>
            <div class="set-list">
                <div class="set-row" style="flex-direction:column;align-items:stretch;cursor:default;padding:24px;">
                    <div class="hist-tabs">
                        <div class="hist-tab active" onclick="switchHistTab('learning', this)">📚 Learning</div>
                        <div class="hist-tab" onclick="switchHistTab('datasets', this)">💾 Datasets</div>
                    </div>

                    <!-- Learning Progress Tab -->
                    <div id="hist-learning">
                        <?php if(!empty($learningHistory)): ?>
                            <?php foreach($learningHistory as $item): ?>
                            <div class="progress-item">
                                <div class="prog-circle <?= $item['progress']==100?'complete':'' ?>">
                                    <?= $item['progress'] ?>%
                                </div>
                                <div style="flex:1;">
                                    <div style="color:#fff; font-size:0.88rem; font-weight:700;"><?= $item['module'] ?></div>
                                    <div style="color:var(--text3); font-size:0.7rem;"><?= $item['date'] ?></div>
                                </div>
                                <div style="font-size:1.1rem;"><?= $item['progress']==100?'✅':'⏳' ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="hist-empty">
                                <div style="font-size:2rem;margin-bottom:10px;">📚</div>
                                <div style="font-size:0.85rem;">You haven't started any modules yet.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Datasets History Tab (Hidden by default) -->
                    <div id="hist-datasets" style="display:none;">
                        <?php if (!empty($userDatasets)): ?>
                        <div style="background:rgba(0,0,0,.25);border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.05);">
                            <table class="mini-tbl">
                                <thead>
                                    <tr><th>Name</th><th>Signal</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userDatasets as $ds): ?>
                                    <tr>
                                        <td style="color:#fff;font-weight:600;"><?= htmlspecialchars(mb_substr($ds['name'],0,20)) ?><?= mb_strlen($ds['name'])>20?'…':'' ?></td>
                                        <td><span class="sig-tag" style="background:rgba(21,101,192,.2);color:#60A5FA;"><?= $ds['signal_type'] ?></span></td>
                                        <td style="font-size:0.7rem;color:var(--text3);"><?= date('d M', strtotime($ds['upload_date'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="hist-empty">
                            <div style="font-size:2rem;margin-bottom:10px;">📡</div>
                            <div style="font-size:0.85rem;">No datasets uploaded by you yet.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top:15px; text-align:center;">
                        <a href="dashboard.php?page=profile" style="font-size:0.75rem; color:var(--blue-l); text-decoration:none; font-weight:700;">View Full Learning Analytics ↗</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ⑦ ABOUT -->
        <div>
            <div class="set-label">About</div>
            <div class="set-list">

                <!-- App Map -->
                <div class="set-row" onclick="document.getElementById('appMapModal').classList.add('open')" style="cursor:pointer;">
                    <div class="set-ico ic-purple">🗺️</div>
                    <div class="set-txt">
                        <div class="set-title">App Map</div>
                        <div class="set-sub">See how the app works</div>
                    </div>
                    <span class="set-chev">›</span>
                </div>

                <!-- Version -->
                <div class="set-row" style="cursor:default;">
                    <div class="set-ico ic-grey">⚡</div>
                    <div class="set-txt">
                        <div class="set-title">Version</div>
                        <div class="set-sub">BioElectrode AI &nbsp;·&nbsp; AI Core: <?= htmlspecialchars($aiVersion) ?> &nbsp;·&nbsp; Accuracy: <?= htmlspecialchars($aiAccuracy) ?></div>
                    </div>
                    <span style="font-size:.7rem;background:rgba(37,99,235,.15);color:var(--blue-l);padding:4px 10px;border-radius:8px;font-weight:800;">v2.4</span>
                </div>

                <!-- Privacy Policy -->
                <div class="set-row" onclick="document.getElementById('privacyModal').classList.add('open')" style="cursor:pointer;">
                    <div class="set-ico ic-teal">🛡️</div>
                    <div class="set-txt">
                        <div class="set-title">Privacy Policy</div>
                        <div class="set-sub">How we protect your data &nbsp;·&nbsp; Last Updated: Feb 11, 2026</div>
                    </div>
                    <span class="set-chev">›</span>
                </div>

                <!-- Terms & Conditions -->
                <div class="set-row" onclick="document.getElementById('termsModal').classList.add('open')" style="cursor:pointer;">
                    <div class="set-ico ic-blue">📄</div>
                    <div class="set-txt">
                        <div class="set-title">Terms &amp; Conditions</div>
                        <div class="set-sub">View agreement &amp; usage policy</div>
                    </div>
                    <span class="set-chev">›</span>
                </div>

                <!-- Help & Support -->
                <div class="set-row" onclick="document.getElementById('helpModal').classList.add('open')" style="cursor:pointer;">
                    <div class="set-ico ic-grey">❓</div>
                    <div class="set-txt">
                        <div class="set-title">Help &amp; Support</div>
                        <div class="set-sub">FAQs and contact us</div>
                    </div>
                    <span class="set-chev">›</span>
                </div>

            </div>
        </div>

        <!-- ⑧ ACCOUNT SECURITY (Change Password) -->
        <div>
            <div class="set-label">Account Security</div>
            <div class="set-list">
                <div class="set-row" style="flex-direction:column;align-items:stretch;cursor:default;padding:24px;">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
                        <div class="set-ico ic-pink">🔒</div>
                        <div>
                            <div class="set-title">Change Password</div>
                            <div class="set-sub">Update your account password &nbsp;·&nbsp; Leave blank to keep current</div>
                        </div>
                    </div>
                    <form action="api/update_profile_api.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="full_name" value="<?= $userName ?>">
                        <input type="hidden" name="email"     value="<?= htmlspecialchars($_SESSION['user_email']??'') ?>">
                        <input type="hidden" name="role"      value="<?= $userRole ?>">
                        <input type="hidden" name="bio"       value="<?= htmlspecialchars($sUser['bio']??'') ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                            <div>
                                <label style="display:block;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:7px;">New Password</label>
                                <input type="password" name="new_password" id="passInput" oninput="setPassBar(this.value)"
                                    placeholder="••••••••" autocomplete="new-password"
                                    style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);padding:12px 14px;border-radius:13px;color:#fff;outline:none;box-sizing:border-box;font-size:.88rem;">
                                <div class="pass-bar-bg"><div id="passBar" class="pass-bar-fill"></div></div>
                                <div id="passLbl" style="font-size:.64rem;color:var(--text3);margin-top:4px;"></div>
                            </div>
                            <div>
                                <label style="display:block;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:var(--text3);margin-bottom:7px;">Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="••••••••" autocomplete="new-password"
                                    style="width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);padding:12px 14px;border-radius:13px;color:#fff;outline:none;box-sizing:border-box;font-size:.88rem;">
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:rgba(37,99,235,.07);border-radius:12px;margin-bottom:16px;font-size:.75rem;color:var(--text3);">
                            🔒 <span>Your profile information is stored securely. Password must be at least 8 characters.</span>
                        </div>
                        <div style="display:flex;justify-content:flex-end;">
                            <button type="submit" style="padding:12px 28px;background:linear-gradient(135deg,#1565C0,#7B1FA2);border:none;border-radius:13px;color:#fff;font-weight:800;font-size:.88rem;cursor:pointer;transition:.3s;">
                                💾 Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ⑨ LOGOUT -->
        <div>
            <a href="api/logout_api.php" class="set-logout">🚪 Sign Out of Account</a>
        </div>

    </div><!-- /set-page -->

    <!-- Hidden Google Translate Element -->
    <div id="google_translate_element" style="display:none;"></div>

    <style>
        /* Hide Google Translate Banner and tooltips */
        .goog-te-banner-frame.skiptranslate, .goog-te-gadget-icon { display: none !important; }
        body { top: 0px !important; }
        .goog-tooltip { display: none !important; box-shadow: none !important; }
        .goog-tooltip:hover { display: none !important; box-shadow: none !important; }
        .goog-text-highlight { background-color: transparent !important; border: none !important; box-shadow: none !important; }
    </style>

    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: 'en',
            autoDisplay: false
        }, 'google_translate_element');
    }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    <script>
    // Tab switcher for history card
    function switchHistTab(tab, el) {
        document.querySelectorAll('.hist-tab').forEach(t => t.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('hist-learning').style.display = tab === 'learning' ? 'block' : 'none';
        document.getElementById('hist-datasets').style.display = tab === 'datasets' ? 'block' : 'none';
    }

    // Password strength bar
    function setPassBar(v) {
        let s = 0;
        if(v.length>=8) s++; if(v.length>=12) s++; if(/[A-Z]/.test(v)) s++;
        if(/[0-9]/.test(v)) s++; if(/[^A-Za-z0-9]/.test(v)) s++;
        const w=['0%','20%','40%','65%','85%','100%'],
              c=['','#EF4444','#F97316','#EAB308','#22C55E','#10B981'],
              l=['','Weak','Fair','Good','Strong','Very Strong'];
        const b=document.getElementById('passBar'), lb=document.getElementById('passLbl');
        b.style.width=w[s]; b.style.background=c[s];
        lb.textContent=v?'Strength: '+l[s]:''; lb.style.color=c[s];
    }
    // Persist toggle states
    document.querySelectorAll('.set-tog').forEach(t=>{
        const k='set_tog_'+t.id;
        const sv=localStorage.getItem(k);
        if(sv!==null) t.checked=sv==='1';
        t.addEventListener('change',() => {
            localStorage.setItem(k,t.checked?'1':'0');
            // Dynamically apply site wide theme if Dark Mode toggle is clicked
            if (t.id === 'tog-dark') {
                if (t.checked) document.documentElement.classList.remove('light-mode');
                else document.documentElement.classList.add('light-mode');
            }
        });
    });
    // Close modals on overlay click
    document.querySelectorAll('.set-modal-overlay').forEach(el=>{
        el.addEventListener('click', e=>{ if(e.target===el) el.classList.remove('open'); });
    });

    // Language change feature with Google Translate Cookie
    const langSel = document.getElementById('langSel');
    if (langSel) {
        // Init select box to current language
        const appLang = localStorage.getItem('appLang') || 'en';
        langSel.value = appLang;

        langSel.addEventListener('change', function() {
            const lang = this.value;
            localStorage.setItem('appLang', lang);
            
            if(lang === 'en') {
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                document.cookie = "googtrans=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=" + location.host;
            } else {
                document.cookie = "googtrans=/auto/" + lang + "; path=/;";
                document.cookie = "googtrans=/auto/" + lang + "; path=/; domain=" + location.host;
            }

            // Show neat toast so it feels instant
            const names={'en':'English','es':'Español','fr':'Français','de':'Deutsch','hi':'हिंदी','te':'తెలుగు','zh':'中文','ja':'日本語','ar':'العربية'};
            const n=document.createElement('div');
            n.style.cssText='position:fixed;bottom:24px;right:24px;background:linear-gradient(135deg,#1565C0,#7B1FA2);color:#fff;padding:14px 22px;border-radius:14px;font-size:.82rem;font-weight:700;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:.4s;';
            n.textContent='🌐 Applying translation ('+names[lang]+')...';
            document.body.appendChild(n);
            
            // Reload to let Google Translate SDK catch the cookie and translate DOM
            setTimeout(() => {
                location.reload();
            }, 600);
        });
    }
    </script>

    <?php elseif ($page === 'educational'): // ═══ CLINICAL CASES PAGE ═══ ?>
        <div class="module-hero" style="background: linear-gradient(135deg, #0F172A, #1E1B4B); border-color: rgba(255,255,255,0.05); margin-bottom: 30px;">
            <div class="hero-icon-box" style="background: rgba(239, 68, 68, 0.2); color: #FCA5A5;">🏥</div>
            <div class="hero-text">
                <h2 style="font-weight:900; letter-spacing:-0.5px;">Practice Scenarios</h2>
                <p>Real-world medical scenarios showcasing bioelectrode applications.</p>
            </div>
        </div>

        <div class="educational-grid">
            <!-- Cards will be populated dynamically by JS -->
        </div>

        <!-- Case Detail Modal -->
        <div id="caseModal" class="set-modal-overlay">
            <div class="set-modal-content" style="max-width: 800px; padding: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
                <div id="caseModalHeader" style="padding: 30px; background: linear-gradient(135deg, #1E293B, #0F172A); border-bottom: 1px solid rgba(255,255,255,0.1); position: relative;">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                        <span id="caseIcon" style="font-size: 2rem;"></span>
                        <span id="caseBadge" style="padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;"></span>
                        <span id="caseNumber" style="font-size: 0.8rem; opacity: 0.6; margin-left: auto;"></span>
                    </div>
                    <h2 id="caseTitle" style="color: #fff; margin: 0; font-size: 1.8rem; font-weight: 900;"></h2>
                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                        <span id="caseSpecialty" style="color: #60A5FA; font-size: 0.85rem; font-weight: 700;"></span>
                        <span style="opacity: 0.3;">•</span>
                        <span id="caseDifficulty" style="font-size: 0.85rem; font-weight: 700;"></span>
                    </div>
                    <button onclick="document.getElementById('caseModal').classList.remove('open')" style="position: absolute; top: 20px; right: 20px; background: rgba(0,0,0,0.3); border: none; color: #fff; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;">✕</button>
                </div>
                <div class="case-modal-body" style="padding: 30px; max-height: 60vh; overflow-y: auto; background: #0F172A;">
                    <div class="case-section">
                        <h4>Subject Profile</h4>
                        <p id="caseStudent"></p>
                    </div>
                    <div class="case-section">
                        <h4>Challenge</h4>
                        <p id="caseChallenge"></p>
                    </div>
                    <div class="case-section">
                        <h4>Why This Recording Mode?</h4>
                        <p id="caseWhy" style="white-space: pre-wrap;"></p>
                    </div>
                    <div class="case-section">
                        <h4>Educational Outcome</h4>
                        <p id="caseOutcome"></p>
                    </div>
                    <div class="case-section" style="background: rgba(124, 58, 237, 0.1); border: 1px solid rgba(124, 58, 237, 0.2); padding: 20px; border-radius: 12px;">
                        <h4 style="color: #C4B5FD; margin-top: 0;">Key Educational Learning</h4>
                        <p id="caseLearning" style="margin-bottom: 0; font-style: italic;"></p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function populateEducationalCases() {
                const grid = document.querySelector('.educational-grid');
                grid.innerHTML = '';
                
                educationalData.forEach((item, index) => {
                    const card = document.createElement('div');
                    card.className = 'educational-card';
                    card.onclick = () => showCaseDetail(index);
                    
                    const iconMap = {
                        'ic_brain': '🧠',
                        'ic_heart': '🫀',
                        'ic_muscle': '💪'
                    };
                    const icon = iconMap[item.icon] || '🏥';

                    const difficultyColors = {
                        'Critical': '#EF4444',
                        'Advanced': '#F59E0B',
                        'Intermediate': '#3B82F6',
                        'Basic': '#10B981'
                    };
                    const diffColor = difficultyColors[item.difficulty] || '#fff';

                    card.innerHTML = `
                        <div class="cc-header">
                            <span class="cc-type-badge ${item.type.toLowerCase()}">${item.type}</span>
                            <span class="cc-num">${item.caseNumber}</span>
                        </div>
                        <div class="cc-main">
                            <div class="cc-icon-box">${icon}</div>
                            <div class="cc-info">
                                <h3>${item.title}</h3>
                                <p class="cc-spec">${item.specialty}</p>
                            </div>
                        </div>
                        <div class="cc-footer">
                            <span class="cc-diff" style="color: ${diffColor}">● ${item.difficulty}</span>
                            <span class="cc-view">View Case →</span>
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }

            function showCaseDetail(index) {
                const item = educationalData[index];
                const modal = document.getElementById('caseModal');
                
                const iconMap = {
                    'ic_brain': '🧠',
                    'ic_heart': '🫀',
                    'ic_muscle': '💪'
                };
                
                document.getElementById('caseIcon').innerText = iconMap[item.icon] || '🏥';
                document.getElementById('caseTitle').innerText = item.title;
                document.getElementById('caseNumber').innerText = item.caseNumber;
                document.getElementById('caseSpecialty').innerText = item.specialty;
                
                const difficultyColors = {
                    'Critical': '#EF4444',
                    'Advanced': '#F59E0B',
                    'Intermediate': '#3B82F6',
                    'Basic': '#10B981'
                };
                const diffColor = difficultyColors[item.difficulty] || '#fff';
                document.getElementById('caseDifficulty').innerText = item.difficulty;
                document.getElementById('caseDifficulty').style.color = diffColor;
                
                const badge = document.getElementById('caseBadge');
                badge.innerText = item.type;
                badge.style.background = item.type === 'Bipolar' ? 'rgba(37,99,235,0.2)' : 'rgba(16,185,129,0.2)';
                badge.style.color = item.type === 'Bipolar' ? '#60A5FA' : '#34D399';
                badge.style.border = `1px solid ${item.type === 'Bipolar' ? 'rgba(37,99,235,0.4)' : 'rgba(16,185,129,0.4)'}`;

                document.getElementById('caseStudent').innerText = item.studentProfile;
                document.getElementById('caseChallenge').innerText = item.challenge;
                document.getElementById('caseWhy').innerText = item.whyRecorded;
                document.getElementById('caseOutcome').innerText = item.outcome;
                document.getElementById('caseLearning').innerText = item.keyLearning;

                modal.classList.add('open');
            }

            document.addEventListener('DOMContentLoaded', populateEducationalCases);
            // Handle late loads for single page app feel
            if (document.readyState === 'complete') populateEducationalCases();
        </script>

        <style>
            .educational-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 20px;
                animation: fadeIn 0.5s ease;
            }
            .educational-card {
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 24px;
                cursor: pointer;
                transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
                position: relative;
                overflow: hidden;
            }
            .educational-card:hover {
                transform: translateY(-5px);
                border-color: rgba(124,58,237,0.4);
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            .cc-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            .cc-type-badge {
                font-size: 0.65rem;
                font-weight: 900;
                text-transform: uppercase;
                padding: 4px 10px;
                border-radius: 8px;
                letter-spacing: 0.5px;
            }
            .cc-type-badge.bipolar { background: rgba(37,99,235,0.15); color: #60A5FA; border: 1px solid rgba(37,99,235,0.2); }
            .cc-type-badge.monopolar { background: rgba(16,185,129,0.15); color: #34D399; border: 1px solid rgba(16,185,129,0.2); }
            .cc-num { font-size: 0.75rem; color: var(--text3); font-family: monospace; }
            .cc-main { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 20px; }
            .cc-icon-box { 
                width: 54px; height: 54px; background: rgba(255,255,255,0.03); 
                border-radius: 14px; display: flex; align-items: center; justify-content: center;
                font-size: 1.8rem; border: 1px solid rgba(255,255,255,0.05);
            }
            .cc-info h3 { margin: 0 0 4px 0; font-size: 1.1rem; color: #fff; font-weight: 800; }
            .cc-spec { margin: 0; font-size: 0.85rem; color: var(--text3); }
            .cc-footer { 
                display: flex; justify-content: space-between; align-items: center; 
                padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.05);
            }
            .cc-diff { font-size: 0.75rem; font-weight: 700; }
            .cc-view { font-size: 0.8rem; font-weight: 800; color: var(--blue-l); }
            
            .case-section {
                margin-bottom: 24px;
            }
            .case-section h4 {
                color: var(--blue-l);
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                margin-bottom: 8px;
                font-weight: 800;
            }
            .case-section p {
                color: #e2e8f0;
                font-size: 0.95rem;
                line-height: 1.6;
                margin: 0;
            }
            .case-modal-body::-webkit-scrollbar {
                width: 6px;
            }
            .case-modal-body::-webkit-scrollbar-thumb {
                background: rgba(255,255,255,0.1);
                border-radius: 10px;
            }
        </style>

    <?php elseif ($page === 'resources'): // ═══ RESOURCE LIBRARY PAGE ═══ ?>
        <div class="module-hero" style="background: linear-gradient(135deg, #0D1117, #161B22); border-color: rgba(255,255,255,0.05); margin-bottom: 30px;">
            <div class="hero-icon-box" style="background: rgba(37,99,235,0.2); color: #93C5FD;">📁</div>
            <div class="hero-text">
                <h2 style="font-weight:900; letter-spacing:-0.5px;">Resources Library</h2>
                <p>Downloadable papers, tutorial notes, and quick reference guides.</p>
            </div>
        </div>

        <div class="resource-categories">
            <button class="res-cat-btn active" onclick="filterResources('all')">All Resources</button>
            <button class="res-cat-btn" onclick="filterResources('Research Papers')">Research Papers</button>
            <button class="res-cat-btn" onclick="filterResources('Video Tutorials')">Tutorials</button>
            <button class="res-cat-btn" onclick="filterResources('Quick Reference Cards')">Quick References</button>
            <button class="res-cat-btn" onclick="filterResources('Clinical Case Studies')">Case Studies</button>
        </div>

        <div class="resource-grid" id="resourceGrid">
            <!-- Populated by JS -->
        </div>

        <!-- Resource Viewer Modal -->
        <div id="resViewerModal" class="set-modal-overlay">
            <div class="set-modal-content" style="max-width: 900px; padding: 0; overflow: hidden; height: 85vh; display: flex; flex-direction: column;">
                <div class="res-modal-header">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span id="resViewIcon" style="font-size: 1.8rem;"></span>
                        <div>
                            <h3 id="resViewTitle" style="margin: 0; color: #fff; font-size: 1.4rem;"></h3>
                            <p id="resViewSource" style="margin: 4px 0 0 0; font-size: 0.8rem; color: var(--text3); font-style: italic;"></p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <button onclick="window.print()" class="btn btn-sm btn-secondary" style="width: auto;">🖨️ Print as PDF</button>
                        <button onclick="document.getElementById('resViewerModal').classList.remove('open')" class="res-close-btn">✕</button>
                    </div>
                </div>
                <div class="res-modal-body" id="resViewBody">
                    <!-- Dynamic Content -->
                </div>
            </div>
        </div>

        <script>
            function populateResources(filter = 'all') {
                const grid = document.getElementById('resourceGrid');
                grid.innerHTML = '';
                
                resourceData.forEach(cat => {
                    if (filter !== 'all' && cat.category !== filter) return;
                    
                    cat.items.forEach(item => {
                        const card = document.createElement('div');
                        card.className = 'res-card fade-up';
                        card.onclick = () => showResourceContent(item);
                        
                        card.innerHTML = `
                            <div class="res-card-icon">${cat.icon}</div>
                            <div class="res-card-info">
                                <span class="res-cat-tag">${cat.category}</span>
                                <h3>${item.title}</h3>
                                <p>${item.subtitle}</p>
                            </div>
                            <div class="res-card-action">
                                <span class="res-btn-peek">Read Now</span>
                                <span class="res-btn-dl" title="Download Reference">⬇️</span>
                            </div>
                        `;
                        grid.appendChild(card);
                    });
                });
            }

            function filterResources(catName) {
                document.querySelectorAll('.res-cat-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.innerText === catName || (catName === 'all' && btn.innerText === 'All Resources'));
                });
                populateResources(catName);
            }

            function showResourceContent(item) {
                const modal = document.getElementById('resViewerModal');
                document.getElementById('resViewTitle').innerText = item.title;
                document.getElementById('resViewSource').innerText = item.source;
                document.getElementById('resViewIcon').innerText = "📄";
                
                let bodyHtml = `
                    <div class="res-doc-page">
                        <div class="res-doc-intro">
                            <p>${item.description}</p>
                        </div>
                `;
                
                // NEW: Render beautiful embedded YouTube player if available
                if (item.youtubeId) {
                    bodyHtml += `
                        <div style="margin: 30px 0 40px 0; border-radius:16px; overflow:hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,0,0,0.1); background:#000; position:relative;">
                            <div style="position:absolute; top:20px; left:20px; background:rgba(0,0,0,0.6); color:#fff; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:bold; letter-spacing:1px; z-index:10; backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,0.2); pointer-events: none;">▶ HD ENGLISH AUDIO TUTORIAL</div>
                            <iframe width="100%" style="aspect-ratio: 16/9; display:block;" src="https://www.youtube.com/embed/${item.youtubeId}?autoplay=1&rel=0&modestbranding=1" title="Educational Video Player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    `;
                } else if (item.videoUrl) {
                    bodyHtml += `
                        <div style="margin: 30px 0 40px 0; border-radius:16px; overflow:hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.4), 0 0 0 1px rgba(0,0,0,0.1); background:#000; position:relative;">
                            <div style="position:absolute; top:20px; left:20px; background:rgba(0,0,0,0.6); color:#fff; padding:6px 14px; border-radius:20px; font-size:11px; font-weight:bold; letter-spacing:1px; z-index:10; backdrop-filter:blur(4px); border:1px solid rgba(255,255,255,0.2);">▶ SAMPLE EDU-VIDEO</div>
                            <video controls autoplay loop playsinline style="width:100%; aspect-ratio:16/9; display:block; object-fit:cover;">
                                <source src="${item.videoUrl}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                    `;
                }

                bodyHtml += `
                        <div class="res-doc-divider"></div>
                `;
                
                item.content.sections.forEach(sec => {
                    bodyHtml += `
                        <div class="res-doc-section">
                            <h4>${sec.heading}</h4>
                            <p>${sec.body}</p>
                        </div>
                    `;
                });
                
                bodyHtml += `
                    <div class="res-doc-footer">
                        <p>© 2026 BioElectrode AI Learning Library • Confidential Educational Resource</p>
                    </div>
                </div>`;
                
                document.getElementById('resViewBody').innerHTML = bodyHtml;
                modal.classList.add('open');
            }

            document.addEventListener('DOMContentLoaded', () => populateResources());
            if (document.readyState === 'complete') populateResources();
        </script>

        <style>
            .resource-categories { display: flex; gap: 12px; margin-bottom: 30px; overflow-x: auto; padding-bottom: 10px; }
            .res-cat-btn { 
                background: var(--card); border: 1px solid var(--border); color: var(--text2); 
                padding: 10px 22px; border-radius: 12px; font-size: 0.85rem; font-weight: 700; 
                cursor: pointer; transition: all 0.3s; white-space: nowrap;
            }
            .res-cat-btn:hover { border-color: var(--blue-l); color: var(--blue-l); }
            .res-cat-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); }

            .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
            .res-card { 
                background: var(--card); border: 1px solid var(--border); border-radius: 20px; 
                padding: 24px; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden;
            }
            .res-card:hover { transform: translateY(-5px); border-color: var(--blue-l); box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
            .res-card-icon { font-size: 2.2rem; margin-bottom: 20px; display: block; filter: drop-shadow(0 4px 10px rgba(0,0,0,0.3)); }
            .res-cat-tag { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 1px; color: var(--blue-l); font-weight: 800; margin-bottom: 8px; display: block; }
            .res-card-info h3 { margin: 0 0 6px 0; color: #fff; font-size: 1.1rem; font-weight: 800; }
            .res-card-info p { margin: 0; color: var(--text3); font-size: 0.85rem; font-weight: 500; }
            .res-card-action { margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center; }
            .res-btn-peek { font-size: 0.8rem; font-weight: 800; color: var(--text); background: rgba(255,255,255,0.05); padding: 6px 14px; border-radius: 20px; }
            .res-card:hover .res-btn-peek { background: var(--blue); }
            .res-btn-dl { font-size: 1.1rem; opacity: 0.5; transition: 0.3s; }
            .res-btn-dl:hover { opacity: 1; transform: scale(1.2); }

            /* Resource Viewer */
            .res-modal-header { padding: 24px 30px; background: #0D1117; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
            .res-close-btn { background: rgba(255,0,0,0.1); border: none; color: #ff5f57; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; font-size: 1.1rem; transition: 0.3s; }
            .res-close-btn:hover { background: #ff5f57; color: #fff; }
            .res-modal-body { flex: 1; overflow-y: auto; background: #0D1117; padding: 40px; }
            .res-doc-page { max-width: 700px; margin: 0 auto; background: #fff; color: #334155; padding: 60px; border-radius: 4px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
            .res-doc-intro { font-style: italic; color: #64748b; font-size: 1.05rem; margin-bottom: 30px; line-height: 1.7; }
            .res-doc-divider { height: 2px; background: #f1f5f9; margin-bottom: 40px; }
            .res-doc-section { margin-bottom: 40px; }
            .res-doc-section h4 { color: #1e293b; font-size: 1.25rem; font-weight: 800; margin: 0 0 16px 0; border-bottom: 2px solid #3b82f6; display: inline-block; }
            .res-doc-section p { font-size: 1rem; line-height: 1.8; margin: 0; }
            .res-doc-footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #f1f5f9; text-align: center; }
            .res-doc-footer p { font-size: 0.75rem; color: #94A3B8; font-weight: 600; }
            
            @media print {
                .res-modal-header, .res-cat-btn, aside, header { display: none !important; }
                .res-doc-page { box-shadow: none; padding: 0; margin: 0; }
                .res-modal-body { overflow: visible; padding: 0; }
            }
        </style>

    <?php elseif ($page === 'glossary'): // ═══ GLOSSARY PAGE ═══ ?>

        <div class="module-hero" style="background: linear-gradient(135deg, #0F172A, #1E1B4B); border-color: rgba(255,255,255,0.05); margin-bottom: 30px;">
            <div class="hero-icon-box" style="background: rgba(124,58,237,0.2); color: #C4B5FD;">📖</div>
            <div class="hero-text">
                <h2 style="font-weight:900; letter-spacing:-0.5px;">BioElectrode Glossary</h2>
                <p>Complete terminology guide for bioelectrode signal analysis.</p>
            </div>
        </div>

        <style>
            .glossary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
            .glossary-card { 
                background: var(--card); border: 1px solid var(--border); border-radius: 18px; padding: 20px; 
                display: flex; gap: 16px; align-items: flex-start; transition: transform 0.3s, box-shadow 0.3s; 
            }
            .glossary-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-l); border-color: rgba(124,58,237,0.4); }
            .glossary-icon {
                width: 48px; height: 48px; border-radius: 14px; background: rgba(5,150,105,0.15); 
                display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;
            }
        </style>

        <div class="glossary-grid">
            <?php 
            // 19 Glossary Terms directly pulled from GlossaryActivity.kt
            $terms = [
                ['icon'=>'📍', 'color'=>'#34D399', 'bg'=>'rgba(5,150,105,0.15)', 'name'=>'Active Electrode', 'def'=>'The electrode placed directly over the target muscle or recording site'],
                ['icon'=>'⚡', 'color'=>'#FBBF24', 'bg'=>'rgba(217,119,6,0.15)', 'name'=>'Anode/Cathode', 'def'=>'Positive and negative electrodes used in electrical stimulation'],
                ['icon'=>'〰️', 'color'=>'#60A5FA', 'bg'=>'rgba(37,99,235,0.15)', 'name'=>'Artifact', 'def'=>'Interference or noise in the recorded signal not originating from the target source'],
                ['icon'=>'⚖️', 'color'=>'#C084FC', 'bg'=>'rgba(124,58,237,0.15)', 'name'=>'Bipolar Recording', 'def'=>'Recording technique using two active electrodes to measure the potential difference'],
                ['icon'=>'💉', 'color'=>'#F87171', 'bg'=>'rgba(220,38,38,0.15)', 'name'=>'Concentric Needle', 'def'=>'A needle electrode with one active recording surface'],
                ['icon'=>'🛡️', 'color'=>'#A78BFA', 'bg'=>'rgba(109,40,217,0.15)', 'name'=>'Common Mode Rejection', 'def'=>'The ability of a differential amplifier to reject signals common to both inputs'],
                ['icon'=>'💧', 'color'=>'#38BDF8', 'bg'=>'rgba(2,132,199,0.15)', 'name'=>'Conducting Medium', 'def'=>'The substance that allows electrical current flow between electrode and tissue'],
                ['icon'=>'⏫', 'color'=>'#F472B6', 'bg'=>'rgba(219,39,119,0.15)', 'name'=>'Differential Amplifier', 'def'=>'An amplifier that amplifies the difference between two input signals'],
                ['icon'=>'🧬', 'color'=>'#4ADE80', 'bg'=>'rgba(22,163,74,0.15)', 'name'=>'SFAP', 'def'=>'Single Fiber Action Potential: The electrical signal from a single muscle fiber'],
                ['icon'=>'❤️', 'color'=>'#FB7185', 'bg'=>'rgba(225,29,72,0.15)', 'name'=>'ECG', 'def'=>'Electrocardiography: Recording of the electrical activity of the heart'],
                ['icon'=>'🧠', 'color'=>'#818CF8', 'bg'=>'rgba(79,70,229,0.15)', 'name'=>'EEG', 'def'=>'Electroencephalography: Recording of electrical activity of the brain'],
                ['icon'=>'📡', 'color'=>'#FEF08A', 'bg'=>'rgba(202,138,4,0.15)', 'name'=>'Monopolar Recording', 'def'=>'Recording technique using one active electrode and a distant reference'],
                ['icon'=>'💪', 'color'=>'#2DD4BF', 'bg'=>'rgba(13,148,136,0.15)', 'name'=>'Motor Unit', 'def'=>'A motor neuron and all the muscle fibers it innervates'],
                ['icon'=>'📝', 'color'=>'#94A3B8', 'bg'=>'rgba(71,85,105,0.15)', 'name'=>'Electrode Selection', 'def'=>'Criteria for choosing appropriate electrodes for a specific application'],
                ['icon'=>'🚀', 'color'=>'#FB923C', 'bg'=>'rgba(234,88,12,0.15)', 'name'=>'Depolarization Velocity', 'def'=>'The speed at which the action potential propagates along a nerve or muscle fiber'],
                ['icon'=>'💾', 'color'=>'#E879F9', 'bg'=>'rgba(192,38,211,0.15)', 'name'=>'DQP', 'def'=>'Digital Quantile Point: Digital measurement point in signal analysis'],
                ['icon'=>'⏱️', 'color'=>'#FDE047', 'bg'=>'rgba(234,179,8,0.15)', 'name'=>'Latency / Stimulation', 'def'=>'Time delay between stimulus and response'],
                ['icon'=>'🌩️', 'color'=>'#FACC15', 'bg'=>'rgba(202,138,4,0.15)', 'name'=>'Voltage Amplitude', 'def'=>'The magnitude of the electrical potential'],
                ['icon'=>'📈', 'color'=>'#6EE7B7', 'bg'=>'rgba(5,150,105,0.15)', 'name'=>'Waveform', 'def'=>'The shape and form of the electrical signal']
            ];
            foreach($terms as $t): ?>
                <div class="glossary-card">
                    <div class="glossary-icon" style="background: <?= $t['bg'] ?>; color: <?= $t['color'] ?>;">
                        <?= $t['icon'] ?>
                    </div>
                    <div>
                        <h4 style="margin:0 0 4px 0; font-size:1rem; font-weight:800; color:var(--text); letter-spacing:-0.2px;"><?= $t['name'] ?></h4>
                        <p style="margin:0; font-size:.85rem; color:var(--text2); line-height:1.5;"><?= $t['def'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 40px; display:flex; justify-content:center;">
            <button onclick="window.location.href='dashboard.php'" class="btn" style="padding: 16px 40px; font-size: 1.05rem; border-radius: 16px; background: linear-gradient(135deg, #1E40AF, #3B82F6); border:none; color:#fff !important; font-weight:800; cursor:pointer; box-shadow: 0 8px 24px rgba(37,99,235,0.4); transition: transform 0.2s;">
                🚀 Ready to learn!
            </button>
        </div>

    <?php else: // ─── Unknown / unhandled pages ─── ?>

        <div class="module-hero">
            <div class="hero-icon-box"><?= $m['icon'] ?? '🔬' ?></div>
            <div class="hero-text">
                <h2><?= $m['title'] ?? ucfirst($page) ?></h2>
                <p><?= $m['desc'] ?? 'This module is currently being developed.' ?></p>
            </div>
        </div>

        <div class="coming-soon-box">
            <span class="cs-emoji">🚧</span>
            <h3>Coming Soon</h3>
            <p>The <strong><?= $m['title'] ?? ucfirst($page) ?></strong> module is currently being developed. It will be available shortly. Stay tuned!</p>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>

    <?php endif; ?>

    </div><!-- /content-area -->
</div><!-- /main-content -->


<script>
    function syncSignalQualityData() {
        const saved = localStorage.getItem('last_ai_analysis');
        if (!saved) return;
        
        let state;
        try {
            state = JSON.parse(saved);
        } catch(e) { return; }
        
        if (!state.apiData || state.apiData.status !== 'success') return;
        
        const snrNum = Number(state.apiData.actual_snr) || 0;
        const actualSnr = snrNum.toFixed(1) + ":1";

        // Update Visualization Simulator if present
        const vizNoiseSlider = document.getElementById('noiseSlider');
        const vizSignalSelect = document.getElementById('signalSelect');
        const vizNoiseDisp = document.getElementById('noise-val-disp');

        if (vizNoiseSlider && vizSignalSelect) {
            // Map AI noise level to slider percentage
            const noiseMap = { 'Low': 15, 'Medium': 45, 'High': 85 };
            const nsVal = noiseMap[state.noise] || 50;
            vizNoiseSlider.value = nsVal;
            if(vizNoiseDisp) vizNoiseDisp.innerText = nsVal + "%";
            
            // Map AI app type to signal morphology
            const app = state.appInfo || '';
            if(app.includes('ECG')) vizSignalSelect.value = 'ECG';
            else if(app.includes('EEG')) vizSignalSelect.value = 'EEG';
            else if(app.includes('EMG')) vizSignalSelect.value = 'EMG';
            else if(app.includes('Brain')) vizSignalSelect.value = 'EEG';
            else if(app.includes('Deep Brain')) vizSignalSelect.value = 'DBS';
        }

        // Fidelity Card/Score
        const fs = document.getElementById('fidelity-score');
        const fd = document.getElementById('fidelity-desc');
        if (fs && fd) {
            let score = Math.round(Math.min(99, snrNum * 5));
            if (snrNum > 15) score = Math.round(85 + (snrNum/30)*14);
            fs.innerText = score;
            // Note: fidelity-desc is often overwritten by page-specific logic, but we can set it here too
        }

        // Comparison Metrics (used in report and signal quality)
        const bpAmp = document.getElementById('bipolar-amp');
        const mpAmp = document.getElementById('monopolar-amp');
        const rbpSnr = document.getElementById('report-bp-snr');
        const rmpSnr = document.getElementById('report-mp-snr');

        if (bpAmp && mpAmp) {
            const cleans = state.apiData.clean_signal || state.apiData.amplitudes;
            const peak = Math.max(...cleans.map(Math.abs));
            const displayAmp = (peak * 100).toFixed(1);
            bpAmp.innerText = `${displayAmp} μV`;
            mpAmp.innerText = `${(displayAmp * 2.4).toFixed(1)} μV`;
        }
        
        if (rbpSnr && rmpSnr) {
            rbpSnr.innerText = snrNum.toFixed(1) + ":1";
            const mpSnrNum = snrNum / 2.8;
            rmpSnr.innerText = mpSnrNum.toFixed(1) + ":1";
            
            const rbpNoise = document.getElementById('report-bp-noise');
            const rmpNoise = document.getElementById('report-mp-noise');
            if(rbpNoise) rbpNoise.innerText = (100/snrNum).toFixed(1) + "%";
            if(rmpNoise) rmpNoise.innerText = (100/mpSnrNum).toFixed(1) + "%";
            
            const rImp = document.getElementById('report-fidelity-imp');
            const rImpBar = document.getElementById('report-fidelity-bar');
            if(rImp) {
                const impVal = Math.round(Math.min(99, snrNum * 4.5));
                rImp.innerText = "+" + impVal + "%";
                if(rImpBar) rImpBar.style.width = impVal + "%";
            }
        }

        // Clinical Condition Populating (THE MAIN UPDATE)
        const cond = state.apiData.clinical_condition;
        if (cond) {
            const containers = [
                'sq-clinical-condition-container',
                'viz-clinical-condition-container',
                'report-clinical-condition-container'
            ];
            
            const html = `
                <div style="padding:25px; background:rgba(255,255,255,0.03); border-radius:24px; border:1px solid rgba(255,255,255,0.08); display:flex; align-items:center; gap:20px; border-left: 6px solid ${cond.color}; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <div style="width:20px; height:20px; border-radius:50%; background:${cond.color}; box-shadow:0 0 15px ${cond.color};"></div>
                    <div>
                        <div style="color:${cond.color}; font-size:0.75rem; text-transform:uppercase; font-weight:800; letter-spacing:1px; margin-bottom:4px;">AI Clinical Assessment</div>
                        <h3 style="color:#fff; margin:0 0 5px 0; font-size:1.3rem;">${cond.title}</h3>
                        <p style="color:#94A3B8; margin:0; font-size:1rem; line-height:1.5;">${cond.desc}</p>
                    </div>
                </div>
            `;
            
            containers.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerHTML = html;
                    el.style.display = 'block';
                }
            });
        }
    }

    function drawCompareSignal(canvasId, data, color, isNoisy) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const dpr = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);
        
        ctx.clearRect(0, 0, rect.width, rect.height);
        ctx.beginPath();
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        
        const step = rect.width / data.length;
        const min = Math.min(...data);
        const max = Math.max(...data);
        const range = max - min || 1;
        
        for (let i = 0; i < data.length; i++) {
            const x = i * step;
            let val = data[i];
            if (isNoisy) val += (Math.random() - 0.5) * (range * 0.4);
            const y = (rect.height / 2) - (val / range) * (rect.height * 0.4);
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        }
        ctx.stroke();
    }

    // Run on global load if we have data
    window.addEventListener('load', () => {
        setTimeout(syncSignalQualityData, 100);
    });
</script>

<script src="js/script.js"></script>
</body>
</html>
