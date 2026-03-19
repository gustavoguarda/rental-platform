import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { Property, PropertyFilters, CreatePropertyPayload } from '../models/property.model';
import { PaginatedResponse } from '../models/api.model';

@Injectable({ providedIn: 'root' })
export class PropertyService {
  private readonly api = inject(ApiService);

  list(filters?: PropertyFilters): Observable<PaginatedResponse<Property>> {
    return this.api.get('/v1/properties', filters as Record<string, string | number>);
  }

  get(id: number): Observable<Property> {
    return this.api.get(`/v1/properties/${id}`);
  }

  create(payload: CreatePropertyPayload): Observable<Property> {
    return this.api.post('/v1/properties', payload);
  }

  update(id: number, payload: Partial<CreatePropertyPayload>): Observable<Property> {
    return this.api.put(`/v1/properties/${id}`, payload);
  }

  delete(id: number): Observable<void> {
    return this.api.delete(`/v1/properties/${id}`);
  }

  regenerateDescription(id: number): Observable<{ message: string }> {
    return this.api.post(`/v1/properties/${id}/regenerate-description`, {});
  }
}
