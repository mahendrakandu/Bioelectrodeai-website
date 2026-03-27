<?php
/**
 * PHP equivalent of signal_features.py
 * Extracts morphological and statistical features from a bioelectrical signal.
 */

function extract_features($amplitudes) {
    if (count($amplitudes) < 50) {
        return null;
    }

    // Convert to float array
    $data = array_map('floatval', $amplitudes);
    
    // Calculate Mean
    $sum = array_sum($data);
    $count = count($data);
    $mean = $sum / $count;

    // Remove DC Offset (Center the data)
    foreach ($data as &$val) {
        $val -= $mean;
    }
    unset($val);

    // Calculate Features
    $abs_sum = 0;
    $sq_sum = 0;
    $min = $data[0];
    $max = $data[0];
    $zero_crossings = 0;

    for ($i = 0; $i < $count; $i++) {
        $val = $data[$i];
        $abs_sum += abs($val);
        $sq_sum += ($val * $val);
        
        if ($val < $min) $min = $val;
        if ($val > $max) $max = $val;
        
        if ($i > 0) {
            if (($data[$i-1] * $val) < 0) {
                $zero_crossings++;
            }
        }
    }

    $mav = $abs_sum / $count;
    $energy = $sq_sum / $count;
    $rms = sqrt($energy);
    $peak_to_peak = $max - $min;
    $zcr = $zero_crossings / $count;

    // Standard Deviation
    $variance_sum = 0;
    foreach ($data as $val) {
        $variance_sum += pow($val, 2); // Mean is 0 now
    }
    $std_dev = sqrt($variance_sum / $count);

    // Return features in an associative array
    return [
        'mav' => round($mav, 4),
        'std_dev' => round($std_dev, 4),
        'peak_to_peak' => round($peak_to_peak, 4),
        'energy' => round($energy, 4),
        'rms' => round($rms, 4),
        'zcr' => round($zcr, 4)
    ];
}
?>
