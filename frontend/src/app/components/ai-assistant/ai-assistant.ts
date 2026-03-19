import { Component, inject, signal, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AIService } from '../../services/ai.service';
import { PropertyService } from '../../services/property.service';
import { Property } from '../../models/property.model';
import { AIPricingSuggestion } from '../../models/api.model';

interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
  timestamp: Date;
}

@Component({
  selector: 'app-ai-assistant',
  imports: [CommonModule, FormsModule],
  templateUrl: './ai-assistant.html',
  styleUrl: './ai-assistant.scss',
})
export class AIAssistant implements OnInit {
  private readonly aiService = inject(AIService);
  private readonly propertyService = inject(PropertyService);

  protected readonly properties = signal<Property[]>([]);
  protected readonly chatMessages = signal<ChatMessage[]>([]);
  protected readonly chatLoading = signal(false);
  protected readonly pricingLoading = signal(false);
  protected readonly pricingSuggestion = signal<AIPricingSuggestion | null>(null);

  protected selectedPropertyId = 0;
  protected guestMessage = '';
  protected pricingPropertyId = 0;
  protected occupancyRate = 65;
  protected avgMarketRate = 200;

  ngOnInit(): void {
    this.propertyService.list({ per_page: 100 }).subscribe({
      next: (res) => this.properties.set(res.data),
    });
  }

  protected sendGuestMessage(): void {
    if (!this.guestMessage.trim() || !this.selectedPropertyId) return;

    const messages = [...this.chatMessages()];
    messages.push({ role: 'user', content: this.guestMessage, timestamp: new Date() });
    this.chatMessages.set(messages);

    this.chatLoading.set(true);
    const message = this.guestMessage;
    this.guestMessage = '';

    this.aiService.generateGuestResponse({
      message,
      property_id: this.selectedPropertyId,
    }).subscribe({
      next: (res) => {
        const updated = [...this.chatMessages()];
        updated.push({ role: 'assistant', content: res.response, timestamp: new Date() });
        this.chatMessages.set(updated);
        this.chatLoading.set(false);
      },
      error: () => {
        const updated = [...this.chatMessages()];
        updated.push({ role: 'assistant', content: 'Sorry, AI service is temporarily unavailable.', timestamp: new Date() });
        this.chatMessages.set(updated);
        this.chatLoading.set(false);
      },
    });
  }

  protected getPricingSuggestion(): void {
    if (!this.pricingPropertyId) return;

    this.pricingLoading.set(true);
    this.pricingSuggestion.set(null);

    this.aiService.getPricingSuggestion(
      this.pricingPropertyId,
      this.occupancyRate,
      this.avgMarketRate,
    ).subscribe({
      next: (res) => {
        this.pricingSuggestion.set(res.suggestion);
        this.pricingLoading.set(false);
      },
      error: () => {
        this.pricingLoading.set(false);
        alert('AI service is temporarily unavailable.');
      },
    });
  }
}
