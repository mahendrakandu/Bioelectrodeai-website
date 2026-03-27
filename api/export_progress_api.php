<?php
/**
 * bioelectrodeai - Progress Report Export
 * Generates a printable HTML report of the user's learning progress.
 */
session_start();
if (!isset($_SESSION['user_id'])) { die("Unauthorized"); }

require_once __DIR__ . '/db.php';
$conn = getDB();
$uid = $_SESSION['user_id'];

// 1. Fetch User Data
$uStmt = $conn->prepare("SELECT name, email, role, created_at FROM users WHERE id = ?");
$uStmt->bind_param('i', $uid);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

// 2. Fetch Progress
$pStmt = $conn->prepare("SELECT module_name, completion_percentage, last_accessed FROM user_progress WHERE user_id = ? ORDER BY completion_percentage DESC");
$pStmt->bind_param('i', $uid);
$pStmt->execute();
$progress = $pStmt->get_result();

// 3. Fetch Quiz Summary
$qStmt = $conn->prepare("SELECT quiz_type, score, total_questions, completed_at FROM quiz_results WHERE user_id = ? ORDER BY completed_at DESC");
$qStmt->bind_param('i', $uid);
$qStmt->execute();
$quizzes = $qStmt->get_result();

// 4. Fetch AI History
$aStmt = $conn->prepare("SELECT signal_type, technique, results_json, created_at FROM analysis_history WHERE user_id = ? ORDER BY created_at DESC");
$aStmt->bind_param('i', $uid);
$aStmt->execute();
$aiRuns = $aStmt->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Learning Progress Report - <?= htmlspecialchars($user['name']) ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; padding: 40px; }
        .header { border-bottom: 2px solid #2563EB; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; color: #1E40AF; }
        .user-meta { margin-bottom: 40px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .section { margin-bottom: 40px; }
        .section h2 { border-left: 5px solid #2563EB; padding-left: 15px; margin-bottom: 20px; font-size: 1.4rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 700; color: #475569; }
        .progress-bar { background: #e2e8f0; border-radius: 10px; height: 10px; width: 100px; display: inline-block; overflow: hidden; }
        .progress-fill { background: #2563EB; height: 100%; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563EB; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700;">🖨️ Save as PDF / Print Report</button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #64748b; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; margin-left: 10px;">Close Window</button>
    </div>

    <div class="header">
        <div>
            <h1>Progress Report</h1>
            <p>Generated on <?= date('F j, Y, g:i a') ?></p>
        </div>
        <div style="text-align: right;">
            <strong style="font-size: 1.2rem; color: #2563EB;">BioElectrode AI</strong>
            <p style="font-size: 0.8rem; margin: 0;">Learning & Analysis Platform</p>
        </div>
    </div>

    <div class="user-meta">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <div style="font-size: 0.8rem; color: #64748b;">Learner Name</div>
                <div style="font-weight: 700; font-size: 1.1rem;"><?= htmlspecialchars($user['name']) ?></div>
            </div>
            <div>
                <div style="font-size: 0.8rem; color: #64748b;">Academic Role</div>
                <div><span class="badge badge-info"><?= htmlspecialchars($user['role']) ?></span></div>
            </div>
            <div>
                <div style="font-size: 0.8rem; color: #64748b;">Email Address</div>
                <div><?= htmlspecialchars($user['email']) ?></div>
            </div>
            <div>
                <div style="font-size: 0.8rem; color: #64748b;">Member Since</div>
                <div><?= date('M d, Y', strtotime($user['created_at'])) ?></div>
            </div>
        </div>
    </div>

    <div class="section">
        <h2>Module Mastery</h2>
        <table>
            <thead>
                <tr>
                    <th>Module Name</th>
                    <th>Completion</th>
                    <th>Status</th>
                    <th>Last Activity</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $progress->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: 600;"><?= ucfirst(str_replace('_', ' ', $row['module_name'])) ?></td>
                    <td>
                        <div class="progress-bar"><div class="progress-fill" style="width: <?= $row['completion_percentage'] ?>%;"></div></div>
                        <span style="font-size: 0.8rem; margin-left: 5px;"><?= $row['completion_percentage'] ?>%</span>
                    </td>
                    <td>
                        <?php if($row['completion_percentage'] >= 100): ?>
                            <span class="badge badge-success">Completed</span>
                        <?php else: ?>
                            <span class="badge" style="background: #fef9c3; color: #854d0e;">In Progress</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 0.85rem; color: #64748b;"><?= date('M d, Y', strtotime($row['last_accessed'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Assessment History</h2>
        <table>
            <thead>
                <tr>
                    <th>Assessment Type</th>
                    <th>Score</th>
                    <th>Percentage</th>
                    <th>Date Completed</th>
                </tr>
            </thead>
            <tbody>
                <?php if($quizzes->num_rows > 0): while($row = $quizzes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['quiz_type']) ?> Quiz</td>
                    <td style="font-weight: 700;"><?= $row['score'] ?> / <?= $row['total_questions'] ?></td>
                    <td><?= round(($row['score'] / $row['total_questions']) * 100) ?>%</td>
                    <td style="font-size: 0.85rem;"><?= date('M d, Y', strtotime($row['completed_at'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No assessment results found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>AI Analysis Activity</h2>
        <table>
            <thead>
                <tr>
                    <th>Signal Studied</th>
                    <th>Technique Used</th>
                    <th>Features Extracted</th>
                    <th>Analysis Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if($aiRuns->num_rows > 0): while($row = $aiRuns->fetch_assoc()): 
                    $data = json_decode($row['results_json'], true);
                    $featCount = count($data['features'] ?? []);
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['signal_type']) ?></td>
                    <td><?= htmlspecialchars($row['technique']) ?></td>
                    <td><?= $featCount ?> characteristics identified</td>
                    <td style="font-size: 0.85rem;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No AI analysis history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 60px; text-align: center; color: #94a3b8; font-size: 0.8rem; border-top: 1px solid #e2e8f0; padding-top: 20px;">
        This is an official progress report from the BioElectrode AI Platform.<br>
        &copy; <?= date('Y') ?> BioElectrode AI. All rights reserved.
    </div>
</body>
</html>
