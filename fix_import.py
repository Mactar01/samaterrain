# -*- coding: utf-8 -*-
with open("frontend/src/app/app.routes.ts", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace(
    "from './features/admin/dashboard/dashboard.component';", 
    "from './features/admin/dashboard/dashboard';"
)

with open("frontend/src/app/app.routes.ts", "w", encoding="utf-8") as f:
    f.write(content)
print("Fixed app.routes.ts import")
