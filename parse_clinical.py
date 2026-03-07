import re
import json

def parse_clinical_cases(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find the getClinicalCases function content
    match = re.search(r'fun getClinicalCases\(\): List<ClinicalCase> \{(.*?)\}', content, re.DOTALL)
    if not match:
        return []
    
    cases_block = match.group(1)
    
    # Regular expression to extract individual ClinicalCase objects
    case_pattern = re.compile(r'ClinicalCase\((.*?)\)', re.DOTALL)
    cases = []
    
    for case_match in case_pattern.finditer(cases_block):
        case_text = case_match.group(1)
        
        # Helper to extract field values
        def get_field(field_name):
            # Matches field = "value" or field = R.drawable.value
            pattern = rf'{field_name}\s*=\s*(?:"(.*?)"|(R\.drawable\.\w+))'
            m = re.search(pattern, case_text, re.DOTALL)
            if m:
                return m.group(1) if m.group(1) is not None else m.group(2)
            return ""

        case_obj = {
            "type": get_field("type"),
            "title": get_field("title"),
            "caseNumber": get_field("caseNumber"),
            "specialty": get_field("specialty"),
            "difficulty": get_field("difficulty"),
            "patientProfile": get_field("patientProfile").replace('\n', ' ').strip(),
            "challenge": get_field("challenge").replace('\n', ' ').strip(),
            "whyRecorded": get_field("whyRecorded").strip(),
            "outcome": get_field("outcome").replace('\n', ' ').strip(),
            "keyLearning": get_field("keyLearning").replace('\n', ' ').strip(),
            "icon": get_field("iconRes").replace('R.drawable.', '')
        }
        cases.append(case_obj)
        
    return cases

if __name__ == "__main__":
    kotlin_file = r"c:\users\MAHENDRA REDDY\OneDrive\Desktop\Learning\app\src\main\java\com\simats\learning\ClinicalCasesActivity.kt"
    data = parse_clinical_cases(kotlin_file)
    
    with open('clinical_data.js', 'w', encoding='utf-8') as f:
        f.write("const clinicalData = ")
        json.dump(data, f, indent=4)
        f.write(";")
    print(f"Successfully parsed {len(data)} clinical cases.")
