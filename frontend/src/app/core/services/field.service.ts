import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class FieldService {
  private http = inject(HttpClient);
  private apiUrl = 'http://192.168.7.140:8000/api/v1';

  getOwnerFields(): Observable<any> {
    return this.http.get(`${this.apiUrl}/owner/fields`);
  }

  createField(fieldData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/fields`, fieldData, { responseType: 'text' });
  }

  getSlots(fieldId: number, date: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/fields/${fieldId}/slots?date=${date}`);
  }

  createSlot(fieldId: number, slotData: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/fields/${fieldId}/slots`, slotData);
  }

  deleteSlot(fieldId: number, slotId: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/fields/${fieldId}/slots/${slotId}`);
  }

  updateField(fieldId: number, fieldData: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/fields/${fieldId}`, fieldData);
  }
}
