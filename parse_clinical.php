<?php
/**
 * PHP equivalent of parse_clinical.py
 * Parses a Kotlin file to extract ClinicalCase objects and saves them to clinical_data.js
 */

function parse_clinical_cases($file_path) {
    if (!file_exists($file_path)) {
        return [];
    }

    $content = file_get_contents($file_path);

    // Find the getClinicalCases function content
    if (preg_match('/fun getClinicalCases\(\): List<ClinicalCase> \{(.*?)\}/s', $content, $match)) {
        $cases_block = $match[1];
    } else {
        return [];
    }

    // Regular expression to extract individual ClinicalCase objects
    // Manual parsing might be safer for nested parentheses, but let's try regex first
    preg_match_all('/ClinicalCase\((.*?)\)/s', $cases_block, $case_matches);
    
    $cases = [];
    foreach ($case_matches[1] as $case_text) {
        
        // Helper to extract field values
        $get_field = function($field_name) use ($case_text) {
            // Matches field = "value" or field = R.drawable.value
            $pattern = '/' . $field_name . '\s*=\s*(?:"(.*?)"|(R\.drawable\.\w+))/s';
            if (preg_match($pattern, $case_text, $m)) {
                return isset($m[1]) && $m[1] !== "" ? $m[1] : $m[2];
            }
            return "";
        };

        $case_obj = [
            "type" => $get_field("type"),
            "title" => $get_field("title"),
            "caseNumber" => $get_field("caseNumber"),
            "specialty" => $get_field("specialty"),
            "difficulty" => $get_field("difficulty"),
            "studentProfile" => trim(str_replace("\n", " ", $get_field("patientProfile"))),
            "challenge" => trim(str_replace("\n", " ", $get_field("challenge"))),
            "whyRecorded" => trim($get_field("whyRecorded")),
            "outcome" => trim(str_replace("\n", " ", $get_field("outcome"))),
            "keyLearning" => trim(str_replace("\n", " ", $get_field("keyLearning"))),
            "icon" => str_replace('R.drawable.', '', $get_field("iconRes"))
        ];
        $cases[] = $case_obj;
    }

    return $cases;
}

// Main execution block (cli)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $kotlin_file = "C:\\Users\\MAHENDRA REDDY\\AndroidStudioProjects\\frontend-Learning\\app\\src\\main\\java\\com\\simats\\learning\\ClinicalCasesActivity.kt";
    $data = parse_clinical_cases($kotlin_file);
    
    $output_file = 'scenario_data.js';
    $js_content = "const educationalData = " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ";";
    
    if (file_put_contents($output_file, $js_content)) {
        echo "Successfully parsed " . count($data) . " clinical cases to $output_file\n";
    } else {
        echo "Failed to write to $output_file\n";
    }
}
?>
