import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { PropertyService } from '../../services/property.service';
import { BookingService } from '../../services/booking.service';
import { Property } from '../../models/property.model';
import { Booking } from '../../models/booking.model';

@Component({
  selector: 'app-dashboard',
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss',
})
export class Dashboard implements OnInit {
  private readonly propertyService = inject(PropertyService);
  private readonly bookingService = inject(BookingService);

  protected readonly properties = signal<Property[]>([]);
  protected readonly recentBookings = signal<Booking[]>([]);
  protected readonly totalProperties = signal(0);
  protected readonly pendingBookings = signal(0);
  protected readonly confirmedBookings = signal(0);
  protected readonly totalRevenue = signal(0);

  ngOnInit(): void {
    this.loadProperties();
    this.loadBookings();
  }

  private loadProperties(): void {
    this.propertyService.list({ per_page: 5 }).subscribe({
      next: (res) => {
        this.properties.set(res.data);
        this.totalProperties.set(res.total);
      },
    });
  }

  private loadBookings(): void {
    this.bookingService.list({ per_page: 10 }).subscribe({
      next: (res) => {
        this.recentBookings.set(res.data);
        this.pendingBookings.set(res.data.filter(b => b.status === 'pending').length);
        this.confirmedBookings.set(res.data.filter(b => b.status === 'confirmed').length);
        this.totalRevenue.set(
          res.data
            .filter(b => b.status === 'confirmed')
            .reduce((sum, b) => sum + b.total_price_cents, 0) / 100
        );
      },
    });
  }
}
