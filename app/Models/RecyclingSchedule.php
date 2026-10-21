<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/** Collecte de rebuts programmée : une demande de recyclage est créée automatiquement à chaque échéance. */
class RecyclingSchedule extends Model
{
    public const FREQUENCIES = ['monthly' => 'Chaque mois', 'quarterly' => 'Chaque trimestre'];

    protected $fillable = ['brand_id', 'supplier_id', 'created_by', 'mode', 'frequency', 'day_of_month', 'items', 'brand_message', 'next_run_on', 'last_run_at', 'last_loop_id', 'runs_count', 'active'];

    protected function casts(): array
    {
        return ['items' => 'array', 'next_run_on' => 'date', 'last_run_at' => 'datetime', 'active' => 'boolean'];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function loops(): HasMany
    {
        return $this->hasMany(RecyclingLoop::class);
    }

    public function totalKg(): float
    {
        return (float) collect($this->items)->sum('quantity_kg');
    }

    public function frequencyLabel(): string
    {
        return self::FREQUENCIES[$this->frequency] ?? $this->frequency;
    }

    /** Prochaine échéance après une date : même jour du mois, 1 ou 3 mois plus tard. */
    public function nextDateAfter(Carbon $date): Carbon
    {
        return $date->copy()->startOfMonth()->addMonthsNoOverflow($this->frequency === 'quarterly' ? 3 : 1)->day(min(28, $this->day_of_month));
    }

    /** Première échéance à partir d'aujourd'hui. */
    public static function firstRun(int $day): Carbon
    {
        $candidate = now()->startOfDay()->day(min(28, $day));

        return $candidate->lt(now()->startOfDay()) ? $candidate->addMonthNoOverflow() : $candidate;
    }
}
