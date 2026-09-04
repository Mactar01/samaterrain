import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AdminService } from '../../../core/services/admin.service';
import { AuthService } from '../../../core/services/auth.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.scss']
})
export class DashboardComponent implements OnInit {
  stats: any = {
    total_owners: 0,
    total_fields: 0,
    total_players: 0,
    total_reservations: 0
  };
  owners: any[] = [];
  loadingStats = true;
  loadingOwners = true;
  adminName = '';

  constructor(
    private adminService: AdminService,
    private authService: AuthService,
    private router: Router,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit(): void {
    const userStr = localStorage.getItem('user');
    const user = userStr ? JSON.parse(userStr) : null;
    this.adminName = user ? user.name : 'Administrateur';
    this.loadStats();
    this.loadOwners();
  }

  loadStats() {
    this.adminService.getStats().subscribe({
      next: (res) => {
        this.stats = res;
        this.loadingStats = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Erreur chargement stats', err);
        this.loadingStats = false;
        this.cdr.detectChanges();
      }
    });
  }

  loadOwners() {
    this.adminService.getOwners().subscribe({
      next: (res) => {
        this.owners = res;
        this.loadingOwners = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Erreur chargement owners', err);
        this.loadingOwners = false;
        this.cdr.detectChanges();
      }
    });
  }

  toggleOwnerStatus(owner: any) {
    this.adminService.toggleOwnerStatus(owner.id).subscribe({
      next: (res) => {
        // Toggle local state
        if (owner.user) {
          owner.user.is_active = !owner.user.is_active;
        }
        Swal.fire({
          icon: 'success',
          title: 'SuccÃ¨s',
          text: res.message,
          timer: 2000,
          showConfirmButton: false
        });
        this.cdr.detectChanges();
      },
      error: (err) => {
        Swal.fire('Erreur', 'Impossible de modifier le statut.', 'error');
      }
    });
  }

  openCreateOwnerModal() {
    Swal.fire({
      title: 'Ajouter un nouveau loueur',
      html: `
        <div class="space-y-4 text-left">
          <div>
            <label class="block text-sm font-medium text-gray-700">Nom Complet</label>
            <input id="swal-name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:ring-black focus:border-black" placeholder="Jean Dupont">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Adresse Email</label>
            <input id="swal-email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:ring-black focus:border-black" placeholder="jean@example.com">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">TÃ©lÃ©phone</label>
            <input id="swal-phone" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:ring-black focus:border-black" placeholder="77 123 45 67">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Nom de l'entreprise / Complexe</label>
            <input id="swal-business" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:ring-black focus:border-black" placeholder="Complexe Sportif Dakar">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Mot de passe provisoire</label>
            <input id="swal-password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 border focus:ring-black focus:border-black" placeholder="********">
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'CrÃ©er',
      cancelButtonText: 'Annuler',
      confirmButtonColor: '#000000',
      cancelButtonColor: '#6b7280',
      preConfirm: () => {
        const name = (document.getElementById('swal-name') as HTMLInputElement).value;
        const email = (document.getElementById('swal-email') as HTMLInputElement).value;
        const phone = (document.getElementById('swal-phone') as HTMLInputElement).value;
        const business_name = (document.getElementById('swal-business') as HTMLInputElement).value;
        const password = (document.getElementById('swal-password') as HTMLInputElement).value;

        if (!name || !email || !business_name || !password) {
          Swal.showValidationMessage('Veuillez remplir tous les champs obligatoires.');
          return false;
        }
        return { name, email, phone, business_name, password, password_confirmation: password };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        this.adminService.createOwner(result.value).subscribe({
          next: (res) => {
            Swal.fire({ icon: 'success', title: 'SuccÃ¨s', text: 'Le compte a Ã©tÃ© crÃ©Ã©.', timer: 2000, showConfirmButton: false });
            this.loadOwners();
            this.loadStats();
          },
          error: (err) => {
            const errorMsg = err.error?.message || 'Erreur lors de la crÃ©ation.';
            Swal.fire('Erreur', errorMsg, 'error');
          }
        });
      }
    });
  }

  deleteOwner(ownerId: number) {
    Swal.fire({
      title: 'ÃŠtes-vous sÃ»r ?',
      text: "La suppression d'un loueur est dÃ©finitive et supprimera ses terrains.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Oui, supprimer !',
      cancelButtonText: 'Annuler'
    }).then((result) => {
      if (result.isConfirmed) {
        this.adminService.deleteOwner(ownerId).subscribe({
          next: () => {
            Swal.fire('SupprimÃ©!', 'Le loueur a Ã©tÃ© supprimÃ©.', 'success');
            this.owners = this.owners.filter(o => o.id !== ownerId);
            this.loadStats();
            this.cdr.detectChanges();
          },
          error: () => {
            Swal.fire('Erreur', 'Impossible de supprimer ce loueur.', 'error');
          }
        });
      }
    });
  }

  logout() {
    this.authService.logout();
    this.router.navigate(['/login']);
  }
}
