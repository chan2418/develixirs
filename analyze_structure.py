
import re

file_path = '/Users/chandruprasath/Desktop/chandru/projects/websites/herbal ecom/website copy/index.php'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Split by lines to track line numbers
lines = content.split('\n')

sections = []
current_section = None

# Regex to find structural comments or H3 headers
section_pattern = re.compile(r'<!--\s*(.*?)\s*-->|<h3.*?>(.*?)</h3>')

for i, line in enumerate(lines):
    match = section_pattern.search(line)
    if match:
        # Check if it's a comment or h3
        name = match.group(1) if match.group(1) else match.group(2)
        # Filter out common non-section comments
        if "google" in name.lower() or "end" in name.lower() or "start" in name.lower():
            continue
        
        # Identify parent container context (roughly)
        # We look backwards 50 lines? No, just track major divs
        pass
        
        sections.append({'line': i+1, 'name': name.strip()})

# Print sections
for s in sections:
    print(f"Line {s['line']}: {s['name']}")
