import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class FieldService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8000/api/v1';

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
    if (fieldData.photo) {
      const formData = new FormData();
      formData.append('_method', 'PUT');
      Object.keys(fieldData).forEach(key => {
        if (fieldData[key] !== null && fieldData[key] !== undefined) {
          formData.append(key, fieldData[key]);
        }
      });
      return this.http.post(`${this.apiUrl}/fields/${fieldId}`, formData);
    }
    return this.http.put(`${this.apiUrl}/fields/${fieldId}`, fieldData);
  }

  updateSlot(fieldId: number, slotId: number, data: any): Observable<any> {
    return this.http.put(`${this.apiUrl}/fields/${fieldId}/slots/${slotId}`, data);
  }

  bulkCreateSlots(fieldId: number, data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/fields/${fieldId}/slots/bulk`, data);
  }
}
