export interface Booking {
  id: number;
  property_id: number;
  guest_name: string;
  guest_email: string;
  check_in: string;
  check_out: string;
  guests_count: number;
  total_price_cents: number;
  currency: string;
  status: 'pending' | 'confirmed' | 'cancelled';
  source_channel: string | null;
  notes: string | null;
  created_at: string;
  property?: {
    id: number;
    name: string;
    city: string;
  };
}

export interface CreateBookingPayload {
  property_id: number;
  guest_name: string;
  guest_email: string;
  check_in: string;
  check_out: string;
  guests_count: number;
  source_channel?: string;
  notes?: string;
}

export interface AvailabilityCheck {
  property_id: number;
  check_in: string;
  check_out: string;
  guests: number;
}

export interface AvailabilityResult {
  property_id: number;
  is_available: boolean;
  reason: string;
  unavailable_dates: string[];
}

export interface PricingQuote {
  property_id: number;
  total_price_cents: number;
  total_with_fees_cents: number;
  currency: string;
  nights: number;
  nightly_breakdown: Record<string, number>;
  applied_rules: string[];
  cleaning_fee_cents: number;
  service_fee_percent: number;
}

export interface AvailabilityResponse {
  availability: AvailabilityResult;
  pricing?: PricingQuote;
}
