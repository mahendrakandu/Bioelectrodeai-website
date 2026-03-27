<?php
require_once 'api/db.php';
$conn = getDB();

$seeds = [
    ['ecg', 'intro', 'What is ECG?', 'Electrocardiography (ECG or EKG) measures the electrical activity of the heart over time using electrodes placed on the skin.'],
    ['eeg', 'intro', 'What is EEG?', 'Electroencephalography (EEG) records electrical activity of the brain through electrodes placed on the scalp.'],
    ['emg', 'intro', 'What is EMG?', 'Electromyography (EMG) measures the electrical activity produced by skeletal muscles.'],
    ['electrode_placement', 'intro', 'Electrode Placement Guide', 'Standardized protocols for signal acquisition ensure consistency and reliability in biomedical signal recording. Proper placement is the foundation of accurate data collection.']
];

foreach ($seeds as $s) {
    $stmt = $conn->prepare("INSERT IGNORE INTO learning_content (page_slug, section_id, title, content) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $s[0], $s[1], $s[2], $s[3]);
    if ($stmt->execute()) {
        echo "Inserted/Matched: {$s[0]} - {$s[1]}<br>";
    } else {
        echo "Error: " . $stmt->error . "<br>";
    }
    $stmt->close();
}
$conn->close();
?>
