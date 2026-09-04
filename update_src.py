# -*- coding: utf-8 -*-
with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "r", encoding="utf-8") as f:
    content = f.read()

import re
old_src = r"\[src\]=\"field\.primary_image \? \('http://192\.168\.7\.140:8000/storage/' \+ field\.primary_image\) : 'https://images\.unsplash\.com/photo-1575361204481-4d3b6707ea20\?q=80&w=1000&auto=format&fit=crop'\""
new_src = "[src]=\"field.photo_url ? field.photo_url : (field.primary_image ? ('http://192.168.7.140:8000/storage/' + field.primary_image.url) : 'https://images.unsplash.com/photo-1575361204481-4d3b6707ea20?q=80&w=1000&auto=format&fit=crop')\""

content = re.sub(old_src, new_src, content)

with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated HTML photo src")
