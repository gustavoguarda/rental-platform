import { Routes } from '@angular/router';
import { Dashboard } from './components/dashboard/dashboard';
import { PropertyList } from './components/properties/property-list';
import { PropertyForm } from './components/properties/property-form';
import { BookingList } from './components/bookings/booking-list';
import { AIAssistant } from './components/ai-assistant/ai-assistant';

export const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: Dashboard },
  { path: 'properties', component: PropertyList },
  { path: 'properties/new', component: PropertyForm },
  { path: 'bookings', component: BookingList },
  { path: 'ai', component: AIAssistant },
];
