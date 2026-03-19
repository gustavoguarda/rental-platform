import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { ApiService } from './api.service';
import { AIGuestResponsePayload, AIPricingSuggestion } from '../models/api.model';

@Injectable({ providedIn: 'root' })
export class AIService {
  private readonly api = inject(ApiService);

  generateGuestResponse(payload: AIGuestResponsePayload): Observable<{ response: string }> {
    return this.api.post('/v1/ai/guest-response', payload);
  }

  getPricingSuggestion(
    propertyId: number,
    occupancyRate: number,
    avgMarketRate: number,
  ): Observable<{ suggestion: AIPricingSuggestion }> {
    return this.api.post(`/v1/ai/properties/${propertyId}/pricing-suggestion`, {
      occupancy_rate: occupancyRate,
      avg_market_rate: avgMarketRate,
    });
  }
}
