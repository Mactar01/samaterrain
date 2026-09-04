# -*- coding: utf-8 -*-
with open("backend/.env", "r", encoding="utf-8") as f:
    content = f.read()

content = content.replace("CACHE_STORE=database", "CACHE_STORE=file")

with open("backend/.env", "w", encoding="utf-8") as f:
    f.write(content)
print("Changed CACHE_STORE to file")
