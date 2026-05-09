<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Operations\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'municipio_id',
        'dispatch_year',
        'talao',
        'status',
        'nature_id',
        'primary_shift_id',
        'occurred_at',
        'call_received_at',
        'address_line',
        'number',
        'district',
        'city',
        'reference_notes',
        'description',
        'caller_name',
        'caller_phone',
        'patient_age',
        'patient_sex',
        'patient_name',
        'patient_call_type',
        'is_qta',
        'expected_victim_total',
        'total_death_count',
        'dispatched_at',
        'departed_base_at',
        'arrived_scene_at',
        'left_scene_at',
        'arrived_hospital_at',
        'released_hospital_at',
        'returned_base_at',
        'latitude',
        'longitude',
        'protected_area_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => IncidentStatus::class,
            'occurred_at' => 'datetime',
            'call_received_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'departed_base_at' => 'datetime',
            'arrived_scene_at' => 'datetime',
            'left_scene_at' => 'datetime',
            'arrived_hospital_at' => 'datetime',
            'released_hospital_at' => 'datetime',
            'returned_base_at' => 'datetime',
            'is_qta' => 'boolean',
        ];
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(Nature::class);
    }

    public function primaryShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'primary_shift_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function protectedArea(): BelongsTo
    {
        return $this->belongsTo(ProtectedArea::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(IncidentDispatch::class);
    }

    public function incidentEvents(): HasMany
    {
        return $this->hasMany(IncidentEvent::class)->orderByDesc('recorded_at');
    }

    /** @deprecated usar incidentEvents; mantido para compatibilidade com blades existentes */
    public function timelineEvents(): HasMany
    {
        return $this->incidentEvents();
    }

    public function victims(): HasMany
    {
        return $this->hasMany(Victim::class);
    }

    public function nurseReport(): HasOne
    {
        return $this->hasOne(IncidentNurseReport::class);
    }

    public function activeDispatch(): ?IncidentDispatch
    {
        return $this->dispatches()->whereNull('deleted_at')->orderByDesc('id')->first();
    }
}
