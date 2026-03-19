import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import {
  Booking,
  CreateBookingPayload,
  AvailabilityCheck,
  AvailabilityResponse,
} from '../models/booking.model';
import { PaginatedResponse } from '../models/api.model';

@Injectable({ providedIn: 'root' })
export class BookingService {
  private readonly api = inject(ApiService);

  list(params?: Record<string, string | number>): Observable<PaginatedResponse<Booking>> {
    return this.api.get('/v1/bookings', params);
  }

  create(payload: CreateBookingPayload): Observable<{ booking: Booking; pricing: unknown }> {
    return this.api.post('/v1/bookings', payload);
  }

  confirm(id: number): Observable<Booking> {
    return this.api.post(`/v1/bookings/${id}/confirm`, {});
  }

  cancel(id: number): Observable<Booking> {
    return this.api.post(`/v1/bookings/${id}/cancel`, {});
  }

  checkAvailability(params: AvailabilityCheck): Observable<AvailabilityResponse> {
    return this.api.post('/v1/availability/check', params);
  }
}
