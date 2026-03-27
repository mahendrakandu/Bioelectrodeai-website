<?php
/**
 * PHP equivalent of parse_v2.py
 * Parses a Kotlin file to extract quiz data and saves it to quiz_data.js
 */

function parse_kotlin_file($file_path) {
    if (!file_exists($file_path)) {
        return ["Bipolar" => [], "Monopolar" => []];
    }

    $content = file_get_contents($file_path);

    // Define the structure
    $quiz_data = [
        "Bipolar" => [],
        "Monopolar" => []
    ];

    // Regex to find chapter functions
    // example: private fun bipolarChapter1() = listOf(...)
    preg_match_all('/private fun (bipolar|monopolar)Chapter(\d+)\(\) = listOf\((.*?)\n    \)/s', $content, $chapter_funcs, PREG_SET_ORDER);

    foreach ($chapter_funcs as $chapter_match) {
        $electrode_type_raw = $chapter_match[1];
        $chapter_num = $chapter_match[2];
        $questions_content = $chapter_match[3];
        
        $electrode_type = $electrode_type_raw === "bipolar" ? "Bipolar" : "Monopolar";
        $chapter_key = "Chapter $chapter_num";
        
        // Extract individual QuizQuestion calls
        // QuizQuestion("...", listOf("...", ...), 0, "...", R.drawable.ic_bolt, "...")
        preg_match_all('/QuizQuestion\("(.*?)",\s*listOf\((.*?)\),\s*(\d+),\s*"(.*?)",\s*R\.drawable\.\w+,\s*"(.*?)"\)/s', $questions_content, $q_matches, PREG_SET_ORDER);
        
        $questions = [];
        foreach ($q_matches as $q_match) {
            $q_text = $q_match[1];
            $options_text = $q_match[2];
            $correct_idx = $q_match[3];
            $explanation = $q_match[4];
            $category = $q_match[5];
            
            // Clean up options
            $options = array_map(function($o) {
                return trim(trim(trim($o), '"'));
            }, explode(',', $options_text));
            
            $questions[] = [
                "q" => $q_text,
                "options" => $options,
                "correct" => (int)$correct_idx,
                "explanation" => $explanation
            ];
        }
        
        $quiz_data[$electrode_type][$chapter_key] = $questions;
    }

    return $quiz_data;
}

// Main execution block (cli)
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $kotlin_path = 'C:\\Users\\MAHENDRA REDDY\\AndroidStudioProjects\\frontend-Learning\\app\\src\\main\\java\\com\\simats\\learning\\QuizRepository.kt';
    $data = parse_kotlin_file($kotlin_path);
    
    $output_file = 'quiz_data.js';
    $js_content = "const quizData = " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . ";";
    if (file_put_contents($output_file, $js_content)) {
        echo "Extracted quiz data to $output_file\n";
    } else {
        echo "Failed to write to $output_file\n";
    }
}
?>
