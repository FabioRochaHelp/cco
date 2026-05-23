<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Models\Incident;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
final class IncidentOperationalDetail extends Component
{
    public Incident $incident;

    public function mount(Incident $incident): void
    {
        Gate::authorize('view', $incident);

        $this->incident = $incident->load([
            'nature',
            'nurseReport.filledBy',
            'finalReport.filledBy',
            'victims.prescriptions.items',
            'timelineEvents' => fn ($q) => $q->orderByDesc('recorded_at')->limit(100),
            'timelineEvents.actor',
            'dispatches.shift.vehicle',
        ]);
    }

    public function refreshOperationalState(): void
    {
        $this->incident->refresh();
        $this->incident->load([
            'nature',
            'nurseReport.filledBy',
            'finalReport.filledBy',
            'victims.prescriptions.items',
            'timelineEvents' => fn ($q) => $q->orderByDesc('recorded_at')->limit(100),
            'timelineEvents.actor',
            'dispatches.shift.vehicle',
        ]);
    }

    public function render(): View
    {
        return view('livewire.operations.incident-detail', [
            'activeDispatch' => $this->incident->activeDispatch(),
        ]);
    }
}
