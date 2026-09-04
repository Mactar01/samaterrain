# -*- coding: utf-8 -*-
with open("backend/app/Models/Field.php", "r", encoding="utf-8") as f:
    content = f.read()

import re
content = content.replace("return $this->primaryImage->image_url;", "return url($this->primaryImage->url);")

with open("backend/app/Models/Field.php", "w", encoding="utf-8") as f:
    f.write(content)
print("Fixed Field.php photo_url logic")
