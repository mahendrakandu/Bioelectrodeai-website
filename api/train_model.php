<?php
/**
 * PHP equivalent of train_model.py
 * Note: Training a Random Forest model and saving as .joblib is natively 
 * handled by Python/Scikit-Learn. This PHP script acts as a bridge 
 * or can be used to trigger the training process.
 */

function train_model() {
    $csvFile = 'training_data.csv';
    
    if (!file_exists($csvFile)) {
        echo "Error: training_data.csv not found. Run create_training_data.php first.\n";
        return;
    }

    echo "Initiating Model Training...\n";
    
    // Since ai_analysis.py depends on bio_model.joblib (Scikit-Learn format),
    // we use this PHP script to trigger the Python trainer to ensure compatibility.
    
    $command = "python train_model.py 2>&1";
    $output = shell_exec($command);
    
    if ($output) {
        echo $output;
    } else {
        echo "Failed to execute Python training script. Ensure Python and Scikit-Learn are installed.\n";
    }
}

if (php_sapi_name() === 'cli') {
    train_model();
}
?>
