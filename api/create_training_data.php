<?php
/**
 * PHP equivalent of create_training_data.py
 * Generates a labeled dataset of bioelectrical signals for training.
 */

require_once 'signal_features.php';

function generate_simulated_dataset($n_samples = 200) {
    $data_list = [];
    $labels = [];
    $types = ['ECG', 'EEG', 'EMG'];

    for ($s = 0; $s < $n_samples; $s++) {
        $app_type = $types[array_rand($types)];
        $noise_levels = ['Low', 'Medium', 'High'];
        $noise_level = $noise_levels[array_rand($noise_levels)];

        // Decide Label (0: Normal, 1: Minor Issue, 2: High Risk/Artifact)
        if ($noise_level == 'Low') {
            $label = 0;
        } elseif ($noise_level == 'Medium') {
            $label = 1;
        } else {
            $label = 2;
        }

        // Generate Signal
        $N = 300;
        $amplitudes = [];
        for ($i = 0; $i < $N; $i++) {
            $t = $i / 100.0;
            $val = 0.0;

            if ($app_type == 'ECG') {
                $val = sin(2 * M_PI * 1.2 * $t); // Simple pulse
            } elseif ($app_type == 'EEG') {
                $val = 0.4 * sin(2 * M_PI * 10 * $t);
            } else {
                $val = rand(-100, 100) / 100.0; // EMG-like
            }

            // Add Noise based on label
            $noise_amp = ($label == 0) ? 0.05 : (($label == 1) ? 0.2 : 0.8);
            $val += (rand(-100, 100) / 100.0) * $noise_amp;
            $amplitudes[] = $val;
        }

        $features = extract_features($amplitudes);
        if ($features !== null) {
            $data_list[] = array_merge($features, [$label]);
        }
    }

    // Write to CSV (headers: mav, std_dev, p2p, energy, rms, zcr, target)
    $fp = fopen('training_data.csv', 'w');
    fputcsv($fp, ['mav', 'std_dev', 'p2p', 'energy', 'rms', 'zcr', 'target']);
    foreach ($data_list as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);

    echo "Generated " . count($data_list) . " samples for training in training_data.csv\n";
}

if (php_sapi_name() === 'cli') {
    generate_simulated_dataset();
}
?>
