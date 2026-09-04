# -*- coding: utf-8 -*-
import re

with open("backend/app/Http/Controllers/Api/FieldController.php", "r", encoding="utf-8") as f:
    content = f.read()

# Make sure to import Storage
if "use Illuminate\Support\Facades\Storage;" not in content:
    content = content.replace("use App\Models\Field;", "use App\Models\Field;\nuse Illuminate\Support\Facades\Storage;")

new_update = """    public function update(UpdateFieldRequest $request, $id)
    {
        $field = Field::findOrFail($id);
        
        $this->authorize('update', $field);

        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($field->photo) {
                Storage::disk('public')->delete($field->photo);
            }
            // Store new photo
            $path = $request->file('photo')->store('fields', 'public');
            $validated['photo'] = $path;
        }

        $field->update($validated);

        // Include full url for photo if needed
        $field->photo_url = $field->photo ? url('storage/' . $field->photo) : null;

        return response()->json([
            'message' => 'Terrain mis à jour avec succès.',
            'field' => $field
        ]);
    }"""

pattern = re.compile(r'    public function update\(UpdateFieldRequest \$request, \$id\).*?(?=\n    /\*\*\n     \* Suppression d\'un terrain)', re.DOTALL)
content = pattern.sub(new_update, content)

with open("backend/app/Http/Controllers/Api/FieldController.php", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated FieldController")
