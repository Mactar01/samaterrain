<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = ['field_id', 'date', 'start_time', 'end_time', 'status', 'price'];

    protected function casts(): array
    {
        return [
            'date'  => 'date',
            'price' => 'decimal:2',
        ];
    }

    // ─── Relations ──────────────────────────────────────────────────

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class);
    }

    // ─── Helpers ────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Retourne le prix effectif du créneau :
     * prix spécifique du créneau, ou prix horaire du terrain.
     */
    public function effectivePrice(): float
    {
        return $this->price ?? $this->field->price_per_hour;
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }
}
