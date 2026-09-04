# -*- coding: utf-8 -*-
with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.ts", "r", encoding="utf-8") as f:
    content = f.read()

new_edit_field = """  async editField(field: any) {
    const { value: formValues } = await Swal.fire({
      title: 'Modifier le terrain',
      html: `
        <div class="text-left">
          <label class="block text-sm font-medium text-gray-700">Nom du terrain</label>
          <input id="swal-input1" class="swal2-input !w-[90%] !mx-auto !block" value="${field.name}">
          <label class="block text-sm font-medium text-gray-700 mt-3">Adresse exacte (ex: 45 rue X)</label>
          <input id="swal-input2" class="swal2-input !w-[90%] !mx-auto !block" value="${field.address || ''}">
          <label class="block text-sm font-medium text-gray-700 mt-3">Prix / heure (FCFA)</label>
          <input id="swal-input3" type="number" class="swal2-input !w-[90%] !mx-auto !block" value="${field.price_per_hour}">
          <label class="block text-sm font-medium text-gray-700 mt-4 mb-2">Photo du terrain (Optionnel)</label>
          <input id="swal-file" type="file" accept="image/*" class="block w-[90%] mx-auto text-sm text-slate-500
            file:mr-4 file:py-2 file:px-4
            file:rounded-full file:border-0
            file:text-sm file:font-semibold
            file:bg-green-50 file:text-green-700
            hover:file:bg-green-100
          "/>
        </div>
      `,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Enregistrer',
      cancelButtonText: 'Annuler',
      confirmButtonColor: '#16a34a',
      preConfirm: () => {
        const fileInput = document.getElementById('swal-file') as HTMLInputElement;
        const file = fileInput?.files?.[0];
        const data: any = {
          name: (document.getElementById('swal-input1') as HTMLInputElement).value,
          address: (document.getElementById('swal-input2') as HTMLInputElement).value,
          price_per_hour: (document.getElementById('swal-input3') as HTMLInputElement).value
        };
        if (file) {
          data.photo = file;
        }
        return data;
      }
    });

    if (formValues) {
      this.fieldService.updateField(field.id, formValues).subscribe({
        next: () => {
          this.confirmationService.toast("Terrain mis à jour !", "success");
          this.loadOwnerData();
        },
        error: () => this.confirmationService.error("Erreur lors de la modification")
      });
    }
  }"""

import re
pattern = re.compile(r'  async editField\(field: any\) \{[\s\S]*?\}\s*\}', re.MULTILINE)
content = pattern.sub(new_edit_field, content)

with open("frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.ts", "w", encoding="utf-8") as f:
    f.write(content)
print("Updated editField in dashboard")
