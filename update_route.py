# -*- coding: utf-8 -*-
with open("backend/routes/web.php", "r", encoding="utf-8") as f:
    content = f.read()

import re
content = content.replace("Route::get('/api/documentation', function () { return view('swagger'); });", """Route::get('/api/documentation', function () {
    return file_get_contents(resource_path('views/swagger.blade.php'));
});""")

with open("backend/routes/web.php", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated route to use file_get_contents")
