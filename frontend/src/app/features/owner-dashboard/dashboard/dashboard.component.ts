import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { FieldService } from '../../../core/services/field.service';
import { AuthService } from '../../../core/services/auth.service';
import { AdminService } from '../../../core/services/admin.service';

import { ConfirmationService } from '../../../core/services/confirmation.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './dashboard.component.html'
})
export class DashboardComponent implements OnInit, OnDestroy {
  fields: any[] = [];
  user: any;
  notifications: any[] = [];
  unreadCount = 0;

  newOwner = { name: '', email: '', password: '', password_confirmation: '', phone: '', business_name: '' };
  ownerCreating = false;

  // Admin Data
  stats: any = null;
  ownersList: any[] = [];
  editingOwner: any = null;
  editForm = { name: '', email: '', phone: '', business_name: '' };

  constructor(
    private fieldService: FieldService, 
    private authService: AuthService,
    private adminService: AdminService,
    private http: HttpClient,
    private confirmationService: ConfirmationService
  ) {}

  ngOnInit() {
    this.authService.currentUser.subscribe(u => {
      this.user = u;
      if (this.user?.role === 'owner') {
        this.loadOwnerData();
      } else if (this.user?.role === 'admin') {
        this.loadAdminData();
      }
    });

    this.loadNotifications();
    // Poll every 15 seconds
    this.notifInterval = setInterval(() => {
      this.loadNotifications();
    }, 15000);
  }

  loadAdminData() {
    this.adminService.getStats().subscribe(res => this.stats = res);
    this.loadOwnersList();
  }

  loadOwnersList() {
    this.adminService.getOwners().subscribe(res => this.ownersList = res);
  }

  loadOwnerData() {
    this.fieldService.getOwnerFields().subscribe({
      next: (data) => this.fields = data,
      error: (err) => console.error(err)
    });
  }

  createOwner() {
    this.ownerCreating = true;

    this.adminService.createOwner(this.newOwner).subscribe({
      next: (res) => {
        this.ownerCreating = false;
        this.confirmationService.toast("Loueur crÃ©Ã© avec succÃ¨s !", "success");
        this.newOwner = { name: '', email: '', password: '', password_confirmation: '', phone: '', business_name: '' };
        this.loadOwnersList();
        this.loadAdminData(); // Refresh stats
      },
      error: (err) => {
        this.ownerCreating = false;
        this.confirmationService.error(err.error?.message || "Erreur lors de la crÃ©ation. VÃ©rifiez les informations.");
      }
    });
  }

  startEdit(owner: any) {
    this.editingOwner = owner;
    this.editForm = {
      name: owner.user.name,
      email: owner.user.email,
      phone: owner.user.phone || '',
      business_name: owner.business_name
    };
  }

  cancelEdit() {
    this.editingOwner = null;
  }

  saveEdit() {
    if (!this.editingOwner) return;
    this.adminService.updateOwner(this.editingOwner.id, this.editForm).subscribe({
      next: () => {
        this.editingOwner = null;
        this.confirmationService.toast("Partenaire mis Ã  jour", "success");
        this.loadOwnersList();
      },
      error: (err) => this.confirmationService.error("Erreur lors de la mise Ã  jour.")
    });
  }

  toggleStatus(owner: any) {
    this.adminService.toggleOwnerStatus(owner.id).subscribe(() => {
      this.confirmationService.toast("Statut mis Ã  jour", "success");
      this.loadOwnersList();
    });
  }

  async deleteOwner(owner: any) {
    const confirmed = await this.confirmationService.confirm({
      title: 'Supprimer ce partenaire ?',
      text: `ÃŠtes-vous sÃ»r de vouloir supprimer dÃ©finitivement le partenaire ${owner.business_name} ?`,
      confirmButtonText: 'Oui, supprimer',
      confirmButtonColor: '#ef4444' // red-500
    });

    if (confirmed) {
      this.adminService.deleteOwner(owner.id).subscribe(() => {
        this.confirmationService.toast("Partenaire supprimÃ©", "success");
        this.loadOwnersList();
        this.loadAdminData();
      });
    }
  }

  private notifInterval: any;

  loadNotifications() {
    this.http.get<any>('http://192.168.1.4:8000/api/v1/notifications').subscribe({
      next: (data) => {
        const newUnreadCount = data.unread_count || 0;
        // Si on a plus de notifications non lues qu'avant, on affiche un toast
        if (newUnreadCount > this.unreadCount && this.unreadCount !== 0) {
          const latestNotif = data.notifications?.data?.[0];
          if (latestNotif) {
            this.confirmationService.toast("ðŸ”” " + latestNotif.data.message, "info");
          }
        } else if (newUnreadCount > 0 && this.unreadCount === 0) {
            // Premier chargement s'il y a des notifs non lues
            this.confirmationService.toast(`Vous avez ${newUnreadCount} notification(s) non lue(s)`, "info");
        }
        
        this.notifications = data.notifications?.data || [];
        this.unreadCount = newUnreadCount;
      },
      error: (err) => console.error(err)
    });
  }

  ngOnDestroy() {
    if (this.notifInterval) {
      clearInterval(this.notifInterval);
    }
  }

  markAsRead(id: string) {
    this.http.patch(`http://192.168.1.4:8000/api/v1/notifications/${id}/read`, {}).subscribe({
      next: () => this.loadNotifications()
    });
  }

  logout() {
    this.authService.logout();
  }

  async editField(field: any) {
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
        </div>
      `,
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
          this.confirmationService.toast("Terrain mis à jour !", "success");
          this.loadOwnerData();
        },
        error: () => this.confirmationService.error("Erreur lors de la modification")
      });
    }
  }
}
