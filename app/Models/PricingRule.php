<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'name',
        'type',
        'modifier_type',
        'modifier_value',
        'start_date',
        'end_date',
        'days_of_week',
        'min_nights',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_of_week' => 'array',
        'modifier_value' => 'decimal:2',
        'min_nights' => 'integer',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function appliesToDate(\DateTimeInterface $date): bool
    {
        if ($this->start_date && $date < $this->start_date) {
            return false;
        }

        if ($this->end_date && $date > $this->end_date) {
            return false;
        }

        if ($this->days_of_week && ! in_array($date->format('N'), $this->days_of_week)) {
            return false;
        }

        return $this->is_active;
    }
}
