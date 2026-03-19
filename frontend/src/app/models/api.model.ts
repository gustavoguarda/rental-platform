export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface AIGuestResponsePayload {
  message: string;
  property_id: number;
  check_in?: string;
  check_out?: string;
}

export interface AIPricingSuggestion {
  suggested_price: number;
  confidence: number;
  reasoning: string;
}
