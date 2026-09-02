import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AdminService {
  private apiUrl = 'http://192.168.7.140:8000/api/v1/admin';

  constructor(private http: HttpClient) {}

  getStats(): Observable<any> {
    return this.http.get(`${this.apiUrl}/stats`);
  }

  getOwners(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/owners`);
  }

  createOwner(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/owners`, data);
  }

  updateOwner(id: number, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/owners/${id}`, data);
  }

  toggleOwnerStatus(id: number): Observable<any> {
    return this.http.patch(`${this.apiUrl}/owners/${id}/toggle-status`, {});
  }

  deleteOwner(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/owners/${id}`);
  }
}
