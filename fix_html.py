# -*- coding: utf-8 -*-
with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace(r"\'", "'")

with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.html", "w", encoding="utf-8") as f:
    f.write(content)
print("Fixed HTML backslashes")
