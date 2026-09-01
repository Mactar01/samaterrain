import os
import glob

directories = [
    r'C:\Users\Mactar SECK\Desktop\myTerrain\frontend\src',
    r'C:\Users\Mactar SECK\Desktop\myTerrain\mobile\lib'
]

for directory in directories:
    for root, _, files in os.walk(directory):
        for file in files:
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                if 'http://192.168.1.4:8000' in content:
                    content = content.replace('http://192.168.1.4:8000', 'https://samaterrain-api.onrender.com')
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Updated {filepath}")
            except Exception as e:
                pass
