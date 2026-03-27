<?php
/**
 * bioelectrodeai - Search API
 * Searches across learning content, hardcoded glossary, and resource data.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

$query = trim($_GET['q'] ?? '');
if (strlen($query) < 2) {
    echo json_encode(['status' => 'success', 'results' => []]);
    exit;
}

$conn = getDB();
$results = [];

// 1. Search Learning Content (Database)
$stmt = $conn->prepare("SELECT page_slug, title, content FROM learning_content WHERE title LIKE ? OR content LIKE ? LIMIT 10");
$likeQuery = "%$query%";
$stmt->bind_param('ss', $likeQuery, $likeQuery);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $results[] = [
        'type' => 'Learning',
        'title' => $row['title'],
        'snippet' => mb_substr(strip_tags($row['content']), 0, 100) . '...',
        'link' => 'dashboard.php?page=' . $row['page_slug']
    ];
}
$stmt->close();

// 2. Search Glossary (Hardcoded Array)
$glossary = [
    ['name'=>'Active Electrode', 'def'=>'The electrode placed directly over the target muscle or recording site'],
    ['name'=>'Anode/Cathode', 'def'=>'Positive and negative electrodes used in electrical stimulation'],
    ['name'=>'Artifact', 'def'=>'Interference or noise in the recorded signal not originating from the target source'],
    ['name'=>'Bipolar Recording', 'def'=>'Recording technique using two active electrodes to measure the potential difference'],
    ['name'=>'CMRR', 'def'=>'Common Mode Rejection Ratio: Ability of an amplifier to reject common-mode signals'],
    ['name'=>'Baseline Wander', 'def'=>'Low-frequency noise in a signal, often caused by breathing or movement'],
    ['name'=>'ECG', 'def'=>'Electrocardiography: Recording of the electrical activity of the heart'],
    ['name'=>'EEG', 'def'=>'Electroencephalography: Recording of electrical activity of the brain'],
    ['name'=>'EMG', 'def'=>'Electromyography: Recording of electrical activity of muscles'],
    ['name'=>'Monopolar Recording', 'def'=>'Recording technique using one active electrode and a distant reference'],
    ['name'=>'Impedance', 'def'=>'The total opposition that a circuit presents to alternating current (AC)'],
    ['name'=>'SNR', 'def'=>'Signal-to-Noise Ratio: The ratio of signal strength to background noise'],
    ['name'=>'Sampling Rate', 'def'=>'Number of samples taken per second to digitize an analog signal'],
    ['name'=>'Alpha Waves', 'def'=>'EEG rhythms (8-13 Hz) associated with relaxed wakefulness'],
    ['name'=>'Beta Waves', 'def'=>'EEG rhythms (13-30 Hz) associated with active focus and thinking'],
    ['name'=>'Delta Waves', 'def'=>'EEG rhythms (0.5-4 Hz) associated with deep, dreamless sleep'],
    ['name'=>'Motor Unit', 'def'=>'A motor neuron and all the muscle fibers it innervates'],
    ['name'=>'Waveform', 'def'=>'The shape and form of the electrical signal']
];

foreach ($glossary as $term) {
    if (stripos($term['name'], $query) !== false || stripos($term['def'], $query) !== false) {
        $results[] = [
            'type' => 'Glossary',
            'title' => $term['name'],
            'snippet' => $term['def'],
            'link' => 'dashboard.php?page=glossary'
        ];
    }
}

// 3. Search Resources (Simplified from resource_data.js logic)
$resources = [
    ['title' => 'Bioelectronic Medicine', 'subtitle' => 'Neural Recording and Stimulation'],
    ['title' => 'Electrode Configuration Strategies', 'subtitle' => 'Educational Settings & Analysis'],
    ['title' => 'Neural Signal Processing Methods', 'subtitle' => 'Advanced Computational Decoding'],
    ['title' => 'Machine Learning in Biosignal Analysis', 'subtitle' => 'Pattern Recognition & Neural Networks'],
    ['title' => 'Electrode Setup Guide', 'subtitle' => 'Step-by-Step Preparation'],
    ['title' => 'Signal Quality Troubleshooting', 'subtitle' => 'Artifact Rejection Techniques'],
    ['title' => 'Bipolar vs Monopolar: Visual Comparison', 'subtitle' => 'Animated Data Systems']
];

foreach ($resources as $res) {
    if (stripos($res['title'], $query) !== false || stripos($res['subtitle'], $query) !== false) {
        $results[] = [
            'type' => 'Resource',
            'title' => $res['title'],
            'snippet' => $res['subtitle'],
            'link' => 'dashboard.php?page=resources'
        ];
    }
}

$conn->close();
echo json_encode(['status' => 'success', 'results' => $results]);
