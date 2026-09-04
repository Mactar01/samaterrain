# -*- coding: utf-8 -*-
with open("backend/vendor/composer/installed.json", "r", encoding="utf-8") as f:
    content = f.read()

import json
data = json.loads(content)

for package in data.get("packages", []):
    if package["name"] == "darkaonline/l5-swagger":
        if "laravel" in package.get("extra", {}):
            if "providers" in package["extra"]["laravel"]:
                package["extra"]["laravel"]["providers"] = []

with open("backend/vendor/composer/installed.json", "w", encoding="utf-8") as f:
    json.dump(data, f, indent=4)
print("Removed provider from installed.json")
