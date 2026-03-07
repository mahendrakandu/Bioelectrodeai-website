
import re
import json

def parse_kotlin_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Define the structure
    quiz_data = {
        "Bipolar": {},
        "Monopolar": {}
    }

    # Regex to find chapter functions
    # example: private fun bipolarChapter1() = listOf(...)
    chapter_funcs = re.findall(r'private fun (bipolar|monopolar)Chapter(\d+)\(\) = listOf\((.*?)\n    \)', content, re.DOTALL)

    for electrode_type_raw, chapter_num, questions_content in chapter_funcs:
        electrode_type = "Bipolar" if electrode_type_raw == "bipolar" else "Monopolar"
        chapter_key = f"Chapter {chapter_num}"
        
        # Extract individual QuizQuestion calls
        # QuizQuestion("...", listOf("...", ...), 0, "...", R.drawable.ic_bolt, "...")
        q_matches = re.findall(r'QuizQuestion\("(.*?)",\s*listOf\((.*?)\),\s*(\d+),\s*"(.*?)",\s*R\.drawable\.\w+,\s*"(.*?)"\)', questions_content, re.DOTALL)
        
        questions = []
        for q_text, options_text, correct_idx, explanation, category in q_matches:
            # Clean up options
            options = [o.strip().strip('"') for o in options_text.split(',')]
            questions.append({
                "q": q_text,
                "options": options,
                "correct": int(correct_idx),
                "explanation": explanation
            })
        
        quiz_data[electrode_type][chapter_key] = questions

    return quiz_data

if __name__ == "__main__":
    kotlin_path = r'c:\users\MAHENDRA REDDY\OneDrive\Desktop\Learning\app\src\main\java\com\simats\learning\QuizRepository.kt'
    data = parse_kotlin_file(kotlin_path)
    
    with open('quiz_data.js', 'w', encoding='utf-8') as f:
        f.write("const quizData = " + json.dumps(data, indent=4) + ";")
    print("Extracted quiz data to quiz_data.js")
