import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { BookingService } from '../../services/booking.service';
import { Booking } from '../../models/booking.model';

@Component({
  selector: 'app-booking-list',
  imports: [CommonModule, FormsModule],
  templateUrl: './booking-list.html',
  styleUrl: './booking-list.scss',
})
export class BookingList implements OnInit {
  private readonly bookingService = inject(BookingService);

  protected readonly bookings = signal<Booking[]>([]);
  protected statusFilter = '';

  ngOnInit(): void {
    this.loadBookings();
  }

  protected loadBookings(): void {
    const params: Record<string, string | number> = {};
    if (this.statusFilter) params['status'] = this.statusFilter;

    this.bookingService.list(params).subscribe({
      next: (res) => this.bookings.set(res.data),
    });
  }

  protected confirm(booking: Booking): void {
    this.bookingService.confirm(booking.id).subscribe({
      next: () => this.loadBookings(),
    });
  }

  protected cancel(booking: Booking): void {
    if (confirm(`Cancel booking for ${booking.guest_name}?`)) {
      this.bookingService.cancel(booking.id).subscribe({
        next: () => this.loadBookings(),
      });
    }
  }
}
