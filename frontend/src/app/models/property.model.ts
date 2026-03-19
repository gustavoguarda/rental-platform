export interface Property {
  id: number;
  operator_id: number;
  name: string;
  slug: string;
  description: string | null;
  ai_description: string | null;
  address: string;
  city: string;
  state: string;
  country: string;
  latitude: number | null;
  longitude: number | null;
  bedrooms: number;
  bathrooms: number;
  max_guests: number;
  base_price_cents: number;
  currency: string;
  amenities: string[];
  status: 'active' | 'inactive' | 'draft';
  created_at: string;
  updated_at: string;
}

export interface PropertyFilters {
  city?: string;
  min_bedrooms?: number;
  max_guests?: number;
  status?: string;
  page?: number;
  per_page?: number;
}

export interface CreatePropertyPayload {
  operator_id: number;
  name: string;
  description?: string;
  address: string;
  city: string;
  state: string;
  country: string;
  bedrooms: number;
  bathrooms: number;
  max_guests: number;
  base_price_cents: number;
  currency?: string;
  amenities?: string[];
  status?: 'active' | 'inactive' | 'draft';
}
