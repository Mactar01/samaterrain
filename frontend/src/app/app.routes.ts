import { Routes } from '@angular/router';
import { LoginComponent } from './features/auth/login/login.component';
import { DashboardComponent } from './features/owner-dashboard/dashboard/dashboard.component';
import { CreateFieldComponent } from './features/owner-dashboard/create-field/create-field';
import { ManageSlotsComponent } from './features/owner-dashboard/manage-slots/manage-slots';
import { ProfileComponent } from './features/owner-dashboard/profile/profile';

export const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  { path: 'login', component: LoginComponent },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'dashboard/fields/new', component: CreateFieldComponent },
  { path: 'dashboard/fields/:id/slots', component: ManageSlotsComponent },
  { path: 'dashboard/profile', component: ProfileComponent }
];
