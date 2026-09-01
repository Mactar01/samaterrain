const fs = require('fs');

let content = fs.readFileSync('frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.ts', 'utf8');

content = content.replace(/async editField\(field: any\)[\s\S]*\}\s*\}\s*\}$/, '}');

const codeToAdd = 
  async editField(field: any) {
    const { value: formValues } = await Swal.fire({
      title: 'Modifier le terrain',
      html: \
        <div class="text-left">
          <label class="block text-sm font-medium text-gray-700">Nom du terrain</label>
          <input id="swal-input1" class="swal2-input !w-[90%] !mx-auto !block" value="\">
          <label class="block text-sm font-medium text-gray-700 mt-3">Adresse exacte (ex: 45 rue X)</label>
          <input id="swal-input2" class="swal2-input !w-[90%] !mx-auto !block" value="\">
          <label class="block text-sm font-medium text-gray-700 mt-3">Prix / heure (FCFA)</label>
          <input id="swal-input3" type="number" class="swal2-input !w-[90%] !mx-auto !block" value="\">
        </div>
      \,
      focusConfirm: false,
      showCancelButton: true,
      confirmButtonText: 'Enregistrer',
      cancelButtonText: 'Annuler',
      confirmButtonColor: '#16a34a',
      preConfirm: () => {
        return {
          name: (document.getElementById('swal-input1') as HTMLInputElement).value,
          address: (document.getElementById('swal-input2') as HTMLInputElement).value,
          price_per_hour: (document.getElementById('swal-input3') as HTMLInputElement).value
        }
      }
    });

    if (formValues) {
      this.fieldService.updateField(field.id, formValues).subscribe({
        next: () => {
          this.confirmationService.toast("Terrain mis a jour !", "success");
          this.loadOwnerFields();
        },
        error: () => this.confirmationService.error("Erreur lors de la modification")
      });
    }
  }
}
;

content = content.trim().replace(/\}$/, '') + codeToAdd;

fs.writeFileSync('frontend/src/app/features/owner-dashboard/dashboard/dashboard.component.ts', content, 'utf8');
