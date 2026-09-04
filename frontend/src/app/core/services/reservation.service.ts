import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ReservationService {
  private apiUrl = 'http://localhost:8000/api/v1';

  constructor(private http: HttpClient) {}

  getOwnerReservations(): Observable<any> {
    return this.http.get(`${this.apiUrl}/owner/reservations`);
  }

  cancelReservation(id: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/reservations//cancel`, {});
  }
}

