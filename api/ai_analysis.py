import sys
import json
import csv
import math

def calculate_variance(data):
    if not data: return 0.0
    mean = sum(data) / len(data)
    return sum((x - mean) ** 2 for x in data) / len(data)

def analyze(file_path):
    try:
        amplitudes = []
        with open(file_path, 'r') as f:
            reader = csv.reader(f)
            next(reader, None) # skip header
            for row in reader:
                if len(row) >= 2:
                    try:
                        amplitudes.append(float(row[1]))
                    except ValueError:
                        pass
        
        if not amplitudes:
            print(json.dumps({"error": "CSV contained no valid data"}))
            return

        weights = [1.0, 2.0, 4.0, 8.0, 4.0, 2.0, 1.0]
        weight_sum = sum(weights)
        half_window = len(weights) // 2

        clean_signal = []
        noise_signal = []

        n = len(amplitudes)
        for i in range(n):
            local_sum = 0.0
            local_weight_sum = 0.0
            
            for w_idx, w in enumerate(weights):
                data_idx = i + (w_idx - half_window)
                if 0 <= data_idx < n:
                    local_sum += amplitudes[data_idx] * w
                    local_weight_sum += w
            
            smoothed_value = local_sum / local_weight_sum if local_weight_sum > 0 else 0
            clean_signal.append(smoothed_value)
            noise_signal.append(amplitudes[i] - smoothed_value)

        signal_variance = calculate_variance(clean_signal)
        noise_variance = calculate_variance(noise_signal)

        actual_snr = signal_variance / noise_variance if noise_variance > 0 else 99.0
        
        signal_peak = max(clean_signal) if clean_signal else 1.0
        if signal_peak == 0: signal_peak = 1.0
        noise_std_dev = math.sqrt(noise_variance)
        actual_noise_percent = (noise_std_dev / abs(signal_peak)) * 100.0

        actual_snr = max(1.0, min(actual_snr, 30.0))
        actual_noise_percent = max(1.0, min(actual_noise_percent, 95.0))
        base_stability = 100.0 - actual_noise_percent

        print(json.dumps({
            "status": "success",
            "actual_snr": actual_snr,
            "actual_noise_percent": actual_noise_percent,
            "base_stability": base_stability,
            "amplitudes": amplitudes,
            "clean_signal": clean_signal,
            "noise_signal": noise_signal
        }))

    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == "__main__":
    if len(sys.argv) > 1:
        analyze(sys.argv[1])
    else:
        print(json.dumps({"error": "No file path provided"}))
