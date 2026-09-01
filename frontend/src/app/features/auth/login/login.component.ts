import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html'
})
export class LoginComponent {
  credentials = { email: '', password: '' };
  error = '';
  isLoading = false;

  constructor(private authService: AuthService, private router: Router) {}

  onSubmit() {
    this.isLoading = true;
    this.error = '';
    this.authService.login(this.credentials).subscribe({
        next: (res) => {
          if (res.user.role === 'owner' || res.user.role === 'admin') {
            this.router.navigate(['/dashboard']);
          } else {
            this.error = 'Accès réservé aux loueurs et administrateurs.';
          }
          this.isLoading = false;
        },
      error: (err) => {
        this.error = 'Identifiants incorrects.';
        this.isLoading = false;
      }
    });
  }
}
