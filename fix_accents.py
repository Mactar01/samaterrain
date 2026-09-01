import os

files = [
    r'C:\Users\Mactar SECK\Desktop\myTerrain\frontend\src\app\features\owner-dashboard\dashboard\dashboard.component.html',
    r'C:\Users\Mactar SECK\Desktop\myTerrain\frontend\src\app\features\owner-dashboard\manage-slots\manage-slots.html'
]

replacements = {
    'Ã©': 'é',
    'Ã¨': 'è',
    'Ã ': 'à',
    'Ã¢': 'â',
    'Ãª': 'ê',
    'Ã®': 'î',
    'Ã´': 'ô',
    'Ã»': 'û',
    'Ã§': 'ç'
}

for filepath in files:
    with open(filepath, 'r', encoding='utf-8') as f:
        text = f.read()
    
    for old, new in replacements.items():
        text = text.replace(old, new)
        
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(text)
    print(f"Fixed {filepath}")
