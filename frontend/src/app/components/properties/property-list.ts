import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { PropertyService } from '../../services/property.service';
import { Property, PropertyFilters } from '../../models/property.model';

@Component({
  selector: 'app-property-list',
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './property-list.html',
  styleUrl: './property-list.scss',
})
export class PropertyList implements OnInit {
  private readonly propertyService = inject(PropertyService);

  protected readonly properties = signal<Property[]>([]);
  protected readonly currentPage = signal(1);
  protected readonly totalPages = signal(1);

  protected filters: PropertyFilters = {};

  ngOnInit(): void {
    this.loadProperties();
  }

  protected onFilterChange(): void {
    this.currentPage.set(1);
    this.loadProperties();
  }

  protected changePage(page: number): void {
    this.currentPage.set(page);
    this.loadProperties();
  }

  protected regenerateDescription(property: Property): void {
    this.propertyService.regenerateDescription(property.id).subscribe({
      next: () => alert('AI description generation queued!'),
    });
  }

  private loadProperties(): void {
    this.propertyService.list({ ...this.filters, page: this.currentPage() }).subscribe({
      next: (res) => {
        this.properties.set(res.data);
        this.totalPages.set(res.last_page);
      },
    });
  }
}
