import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { PropertyService } from '../../services/property.service';

@Component({
  selector: 'app-property-form',
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './property-form.html',
  styleUrl: './property-form.scss',
})
export class PropertyForm {
  private readonly fb = inject(FormBuilder);
  private readonly propertyService = inject(PropertyService);
  private readonly router = inject(Router);

  protected readonly submitting = signal(false);
  protected readonly error = signal('');

  protected readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    description: [''],
    address: ['', Validators.required],
    city: ['', Validators.required],
    state: ['', Validators.required],
    country: ['US', Validators.required],
    bedrooms: [1, [Validators.required, Validators.min(0)]],
    bathrooms: [1, [Validators.required, Validators.min(0)]],
    max_guests: [2, [Validators.required, Validators.min(1)]],
    basePriceDollars: [150, [Validators.required, Validators.min(1)]],
    status: ['draft'],
  });

  protected onSubmit(): void {
    if (this.form.invalid) return;

    this.submitting.set(true);
    this.error.set('');

    const values = this.form.getRawValue();
    this.propertyService.create({
      name: values.name,
      description: values.description,
      address: values.address,
      city: values.city,
      state: values.state,
      country: values.country,
      bedrooms: values.bedrooms,
      bathrooms: values.bathrooms,
      max_guests: values.max_guests,
      status: values.status as 'draft' | 'active' | 'inactive',
      operator_id: 1,
      base_price_cents: Math.round(values.basePriceDollars * 100),
    }).subscribe({
      next: () => this.router.navigate(['/properties']),
      error: (err) => {
        this.error.set(err.error?.message || 'Failed to create property.');
        this.submitting.set(false);
      },
    });
  }

  protected cancel(): void {
    this.router.navigate(['/properties']);
  }
}
