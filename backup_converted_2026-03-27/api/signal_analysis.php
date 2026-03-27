<?php
/**
 * PHP equivalent of ai_analysis.py (excluding machine learning AI analysis)
 * Provides signal simulation, filtering, and heuristic condition detection.
 */

function get_app_profile($app_type) {
    $profiles = [
        'EEG' => [
            'baseSnrBi' => 15, 'baseSnrMo' => 6,
            'confBi' => [88, 97], 'confMo' => [62, 78],
            'nrBi' => [50, 72], 'nrMo' => [18, 32],
            'stabBi' => [78, 92], 'stabMo' => [55, 72],
            'spatBi' => [82, 95], 'spatMo' => [60, 78],
            'freqBand' => '0.5 – 100 Hz', 'ampRange' => '10 – 100 μV',
            'signalLabel' => 'Brain Wave Activity'
        ],
        'ECG' => [
            'baseSnrBi' => 22, 'baseSnrMo' => 9,
            'confBi' => [92, 99], 'confMo' => [70, 85],
            'nrBi' => [60, 80], 'nrMo' => [25, 40],
            'stabBi' => [85, 96], 'stabMo' => [65, 80],
            'spatBi' => [75, 88], 'spatMo' => [55, 72],
            'freqBand' => '0.05 – 150 Hz', 'ampRange' => '0.5 – 5 mV',
            'signalLabel' => 'Cardiac Rhythm'
        ],
        'EMG' => [
            'baseSnrBi' => 12, 'baseSnrMo' => 5,
            'confBi' => [85, 94], 'confMo' => [58, 75],
            'nrBi' => [45, 65], 'nrMo' => [15, 28],
            'stabBi' => [70, 85], 'stabMo' => [48, 65],
            'spatBi' => [88, 97], 'spatMo' => [55, 70],
            'freqBand' => '20 – 500 Hz', 'ampRange' => '50 μV – 30 mV',
            'signalLabel' => 'Muscle Activation'
        ],
        'DBS' => [
            'baseSnrBi' => 20, 'baseSnrMo' => 8,
            'confBi' => [90, 98], 'confMo' => [68, 82],
            'nrBi' => [58, 78], 'nrMo' => [22, 38],
            'stabBi' => [88, 97], 'stabMo' => [62, 78],
            'spatBi' => [92, 99], 'spatMo' => [65, 80],
            'freqBand' => '130 – 185 Hz', 'ampRange' => '1 – 10 V',
            'signalLabel' => 'Stimulation Pulse'
        ],
        'Nerve' => [
            'baseSnrBi' => 14, 'baseSnrMo' => 5.5,
            'confBi' => [86, 95], 'confMo' => [60, 76],
            'nrBi' => [48, 68], 'nrMo' => [16, 30],
            'stabBi' => [75, 88], 'stabMo' => [52, 68],
            'spatBi' => [85, 96], 'spatMo' => [58, 74],
            'freqBand' => '2 – 10 kHz', 'ampRange' => '5 – 80 μV',
            'signalLabel' => 'Compound Action Potential'
        ],
        'Custom' => [
            'baseSnrBi' => 16, 'baseSnrMo' => 7,
            'confBi' => [82, 93], 'confMo' => [60, 78],
            'nrBi' => [48, 70], 'nrMo' => [18, 32],
            'stabBi' => [72, 88], 'stabMo' => [50, 68],
            'spatBi' => [70, 88], 'spatMo' => [50, 70],
            'freqBand' => 'Variable', 'ampRange' => 'Variable',
            'signalLabel' => 'Custom Signal'
        ]
    ];

    foreach ($profiles as $k => $v) {
        if (strpos($app_type, $k) !== false) {
            return $v;
        }
    }
    return $profiles['Custom'];
}

function get_synthesized_signal($app_type, $noise_level, $is_bipolar) {
    $amplitudes = [];
    $clean_signal = [];
    $N = 300;
    
    for ($i = 0; $i < $N; $i++) {
        $val = 0.0;
        if (strpos($app_type, 'ECG') !== false) {
            $t = ($i % 75) / 75.0;
            if (0.0 <= $t && $t < 0.10) $val = 0.12 * exp(-(($t - 0.05) / 0.04)**2 * 8);
            elseif (0.16 <= $t && $t < 0.20) $val = -0.08 * exp(-(($t - 0.18) / 0.015)**2 * 12);
            elseif (0.20 <= $t && $t < 0.28) $val = 0.9 * exp(-(($t - 0.24) / 0.025)**2 * 20);
            elseif (0.28 <= $t && $t < 0.34) $val = -0.18 * exp(-(($t - 0.31) / 0.025)**2 * 14);
            elseif (0.34 <= $t && $t < 0.44) $val = 0.02;
            elseif (0.44 <= $t && $t < 0.62) $val = 0.25 * exp(-(($t - 0.53) / 0.06)**2 * 6);
        } elseif (strpos($app_type, 'EEG') !== false) {
            $tSec = $i / 100.0;
            $alpha = 0.4 * sin(2 * M_PI * 10 * $tSec);
            $beta = 0.15 * sin(2 * M_PI * 22 * $tSec + 1.2);
            $theta = 0.25 * sin(2 * M_PI * 6 * $tSec + 0.5);
            $delta = 0.1 * sin(2 * M_PI * 2.5 * $tSec + 2.0);
            $val = $alpha + $beta + $theta + $delta;
            $val *= 0.7 + 0.3 * sin(2 * M_PI * 0.5 * $tSec);
        } elseif (strpos($app_type, 'EMG') !== false) {
            $tSec = $i / 150.0;
            $burstPhase = ($i % 100) / 100.0;
            $envelope = 0;
            if (0.1 <= $burstPhase && $burstPhase < 0.6) {
                $envelope = sin(M_PI * ($burstPhase - 0.1) / 0.5);
            }
            $muap1 = sin(2 * M_PI * 85 * $tSec);
            $muap2 = 0.6 * sin(2 * M_PI * 130 * $tSec + 0.8);
            $muap3 = 0.3 * sin(2 * M_PI * 210 * $tSec + 1.5);
            $val = $envelope * ($muap1 + $muap2 + $muap3) * 0.35;
            $val += 0.05 * sin(2 * M_PI * 45 * $tSec);
        } elseif (strpos($app_type, 'DBS') !== false) {
            $pulseWidth = 5;
            $pulsePeriod = 20;
            $pulsePos = $i % $pulsePeriod;
            if ($pulsePos < $pulseWidth) {
                $val = 0.8;
            } elseif ($pulsePos < $pulseWidth + 3) {
                $val = -0.3;
            } else {
                $val = 0;
            }
            $val += 0.08 * sin(2 * M_PI * 20 * ($i / 200.0)) + 0.04 * sin(2 * M_PI * 4 * ($i / 200.0));
        } elseif (strpos($app_type, 'Nerve') !== false) {
            $t = ($i % 60) / 60.0;
            if (0.02 <= $t && $t < 0.06) {
                $val = 0.3 * exp(-(($t - 0.04) / 0.01)**2 * 20);
            } elseif (0.15 <= $t && $t < 0.30) {
                $x = ($t - 0.22) / 0.05;
                $val = 0.7 * exp(-$x * $x * 6) * cos(2 * M_PI * 2 * ($t - 0.15) / 0.15);
            } elseif (0.35 <= $t && $t < 0.50) {
                $x = ($t - 0.42) / 0.05;
                $val = 0.35 * exp(-$x * $x * 5) * cos(2 * M_PI * 2 * ($t - 0.35) / 0.15);
            } elseif (0.65 <= $t && $t < 0.85) {
                $x = ($t - 0.75) / 0.08;
                $val = 0.15 * exp(-$x * $x * 4);
            }
        } else {
            $tSec = $i / 100.0;
            $val = (0.3 * sin(2 * M_PI * 5 * $tSec) +
                   0.2 * sin(2 * M_PI * 12 * $tSec + 0.7) +
                   0.1 * sin(2 * M_PI * 30 * $tSec + 1.3));
        }
        
        $clean_signal[] = $val;
        
        // Adding noise artifacts
        $noise_val = (mt_rand() / mt_getrandmax() - 0.5) * ($is_bipolar ? 0.04 : 0.2);
        if ($noise_level != 'Low' && !$is_bipolar) {
            $noise_val += 0.06 * sin(2 * M_PI * 50 * ($i / 200.0)); // Power-line
            $noise_val += 0.05 * sin(2 * M_PI * 0.3 * ($i / 200.0)); // Wandering
        }
        
        $noise_multipliers = ["Low" => 1.2, "Medium" => 1.0, "High" => 0.75];
        $noise_multiplier = isset($noise_multipliers[$noise_level]) ? $noise_multipliers[$noise_level] : 1.0;
        
        if ($noise_level == 'High' && !$is_bipolar) {
            $noise_val *= 3.0;
        }
        
        $amplitudes[] = $val + $noise_val;
    }
    
    return [$clean_signal, $amplitudes];
}

function evaluate_parameters($prof, $noise_multiplier, $is_bipolar) {
    $rand_range = function($arr) {
        return round($arr[0] + (mt_rand() / mt_getrandmax()) * ($arr[1] - $arr[0]), 1);
    };

    $baseSnr = $is_bipolar ? $prof['baseSnrBi'] : $prof['baseSnrMo'];
    $snrNum = round($baseSnr * $noise_multiplier + ((mt_rand() / mt_getrandmax()) * 2 - 1), 1);
    if ($snrNum < 2) $snrNum = 2.0;

    $confidence = round($rand_range($is_bipolar ? $prof['confBi'] : $prof['confMo']));
    $noiseReduction = $rand_range($is_bipolar ? $prof['nrBi'] : $prof['nrMo']);
    $stability = $rand_range($is_bipolar ? $prof['stabBi'] : $prof['stabMo']);
    $spatialRes = $rand_range($is_bipolar ? $prof['spatBi'] : $prof['spatMo']);

    return [$snrNum, $confidence, $noiseReduction, $stability, $spatialRes];
}

function determine_condition($app_type, $snr) {
    if (strpos($app_type, 'EMG') !== false) {
        if ($snr > 10) {
            return ["title" => "At Rest", "desc" => "Minimal electrical activity — Baseline noise only", "color" => "#10B981"];
        } elseif ($snr > 5) {
            return ["title" => "Light Contraction", "desc" => "Few motor units firing — Low amplitude spikes", "color" => "#3B82F6"];
        } else {
            return ["title" => "Maximum Contraction", "desc" => "Dense interference pattern — Full recruitment", "color" => "#EF4444"];
        }
    } elseif (strpos($app_type, 'ECG') !== false) {
        if ($snr > 15) {
            return ["title" => "Normal Sinus Rhythm", "desc" => "Clean recognizable P-QRS-T morphology", "color" => "#10B981"];
        } elseif ($snr > 8) {
            return ["title" => "Minor Arrhythmia / Baseline Wander", "desc" => "Slight rhythm deviations or respiratory artifact detected", "color" => "#F59E0B"];
        } else {
            return ["title" => "Severe Artifact / Tachycardia", "desc" => "Indistinguishable QRS complexes — Heavy interference", "color" => "#EF4444"];
        }
    } elseif (strpos($app_type, 'EEG') !== false) {
        if ($snr > 12) {
            return ["title" => "Relaxed Awake State (Alpha)", "desc" => "Prominent synchronized Alpha band rhythms", "color" => "#10B981"];
        } elseif ($snr > 6) {
            return ["title" => "Active / Focused (Beta)", "desc" => "Desynchronized high-frequency Beta wave activity", "color" => "#3B82F6"];
        } else {
            return ["title" => "Artifact-Heavy / Seizure Activity", "desc" => "High-amplitude uncoordinated spikes — poor signal isolation", "color" => "#EF4444"];
        }
    } elseif (strpos($app_type, 'DBS') !== false) {
        if ($snr > 14) {
            return ["title" => "Stable Stimulation", "desc" => "Clear high-frequency therapeutic pulse delivery", "color" => "#10B981"];
        } else {
            return ["title" => "Sub-optimal Targeting", "desc" => "Distorted pulse wave — Potential high impedance", "color" => "#F59E0B"];
        }
    } elseif (strpos($app_type, 'Nerve') !== false) {
        if ($snr > 12) {
            return ["title" => "Healthy Conduction", "desc" => "Clear biphasic compound action potential", "color" => "#10B981"];
        } else {
            return ["title" => "Conduction Block", "desc" => "Dispersed or temporally delayed AP — High noise floor", "color" => "#F59E0B"];
        }
    } else {
        return ["title" => "Signal Status Determined", "desc" => "Custom signal profile mapped based on current quality metrics", "color" => "#A855F7"];
    }
}

function analyze_signal($file_path, $app_type, $noise_level, $electrode) {
    try {
        $is_bipolar = ($electrode == 'Bipolar');
        $noise_multipliers = ["Low" => 1.2, "Medium" => 1.0, "High" => 0.75];
        $noise_multiplier = isset($noise_multipliers[$noise_level]) ? $noise_multipliers[$noise_level] : 1.0;
        $prof = get_app_profile($app_type);
        
        $amplitudes = [];
        $clean_signal = [];
        
        if ($file_path && $file_path != 'None' && file_exists($file_path)) {
            $handle = fopen($file_path, 'r');
            fgetcsv($handle); // skip header
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 2) {
                    $amplitudes[] = (float)$row[1];
                }
            }
            fclose($handle);
        }
                
        if (!empty($amplitudes)) {
            // FIR Filtering
            $weights = [1.0, 2.0, 4.0, 8.0, 4.0, 2.0, 1.0];
            $n = count($amplitudes);
            $half_window = floor(count($weights) / 2);
            for ($i = 0; $i < $n; $i++) {
                $local_sum = 0.0;
                $local_weight_sum = 0.0;
                foreach ($weights as $w_idx => $w) {
                    $data_idx = $i + ($w_idx - $half_window);
                    if ($data_idx >= 0 && $data_idx < $n) {
                        $local_sum += $amplitudes[$data_idx] * $w;
                        $local_weight_sum += $w;
                    }
                }
                $clean_signal[] = $local_weight_sum > 0 ? $local_sum / $local_weight_sum : 0;
            }
                
            // Statistics for SNR
            $var_signal = 0;
            $var_noise = 0;
            if (count($clean_signal) > 0) {
                $mean_c = array_sum($clean_signal) / count($clean_signal);
                $sq_diff_c = 0;
                foreach ($clean_signal as $c) $sq_diff_c += pow($c - $mean_c, 2);
                $var_signal = $sq_diff_c / count($clean_signal);
                
                $noise_vals = [];
                for ($i = 0; $i < count($amplitudes); $i++) $noise_vals[] = $amplitudes[$i] - $clean_signal[$i];
                $mean_n = array_sum($noise_vals) / count($noise_vals);
                $sq_diff_n = 0;
                foreach ($noise_vals as $n_val) $sq_diff_n += pow($n_val - $mean_n, 2);
                $var_noise = $sq_diff_n / count($noise_vals);
            }
            
            if ($var_noise > 0.00001) {
                $calculated_snr = round(10 * log10(max($var_signal, 0.0001) / $var_noise), 1);
            } else {
                $calculated_snr = 30.0;
            }
            
            $snrNum = max(2.0, min($calculated_snr, 40.0));
            
            $base_val = min(100.0, max(0.0, ($snrNum / 25.0) * 100));
            $confidence = round(min(99.0, max(30.0, $base_val + ((mt_rand() / mt_getrandmax()) * 10 - 5))), 1);
            $stability = round(min(99.0, max(30.0, $base_val * 0.9 + ((mt_rand() / mt_getrandmax()) * 15 - 5))), 1);
            $noiseReduction = round(min(85.0, max(10.0, (100 - $base_val) * 0.8)), 1);
            $spatialRes = round(min(99.0, max(40.0, $base_val * 0.95)), 1);
            
            $condition = determine_condition($app_type, $snrNum);
        } else {
            // Simulation Mode
            list($clean_signal, $amplitudes) = get_synthesized_signal($app_type, $noise_level, $is_bipolar);
            list($snrNum, $confidence, $noiseReduction, $stability, $spatialRes) = evaluate_parameters($prof, $noise_multiplier, $is_bipolar);
            $condition = determine_condition($app_type, $snrNum);
        }

        $noise_signal = [];
        for ($i = 0; $i < count($amplitudes); $i++) $noise_signal[] = $amplitudes[$i] - $clean_signal[$i];

        return [
            "status" => "success",
            "actual_snr" => $snrNum,
            "actual_noise_percent" => round(100.0 - $stability, 1),
            "base_stability" => $stability,
            "amplitudes" => $amplitudes,
            "clean_signal" => $clean_signal,
            "noise_signal" => $noise_signal,
            "confidence" => $confidence,
            "noise_reduction" => $noiseReduction,
            "stability" => $stability,
            "spatial_resolution" => $spatialRes,
            "app_type" => $app_type,
            "noise_level" => $noise_level,
            "freq_band" => $prof['freqBand'],
            "amp_range" => $prof['ampRange'],
            "signal_label" => $prof['signalLabel'],
            "clinical_condition" => $condition,
            "ml_intelligence" => "Heuristic Engine (PHP)"
        ];
        
    } catch (\Throwable $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    }
}
?>
