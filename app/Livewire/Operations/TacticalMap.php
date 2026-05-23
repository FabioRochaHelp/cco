<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Domain\Operations\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentDispatch;
use App\Models\VehiclePosition;
use App\Support\Operations\OperationalIncidentVisibility;
use App\Support\Operations\OperationalMunicipioSelection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.tactical-map')]
#[Title('Mapa tático')]
final class TacticalMap extends Component
{
    public function render(): View
    {
        $mid = OperationalMunicipioSelection::current(Auth::user());

        $openIncidentsQuery = Incident::query()
            ->with(['nature', 'municipio'])
            ->where('status', IncidentStatus::Open);

        OperationalIncidentVisibility::constrainListing($openIncidentsQuery, Auth::user());

        $openIncidents = $openIncidentsQuery->orderByDesc('occurred_at')->get();

        $activeDispatches = IncidentDispatch::query()
            ->with(['incident.nature'])
            ->whereNull('deleted_at')
            ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid))
            ->get();

        $allActiveIncidents = $openIncidents->merge(
            $activeDispatches->map(fn (IncidentDispatch $d) => $d->incident)->filter()
        )->unique('id');

        $mapIncidents = $allActiveIncidents
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->map(fn (Incident $i) => [
                'id'     => $i->id,
                'lat'    => (float) $i->latitude,
                'lng'    => (float) $i->longitude,
                'talao'  => $i->talao,
                'year'   => $i->dispatch_year,
                'nature' => $i->nature?->name ?? '—',
                'status' => $i->status->value,
                'url'    => route('operations.incidents.show', $i),
            ])
            ->values();

        try {
            $mapVehicles = VehiclePosition::query()
                ->join('vehicles', 'vehicles.id', '=', 'vehicle_positions.vehicle_id')
                ->whereNull('vehicles.deleted_at')
                ->when($mid !== null, fn ($q) => $q->where('vehicles.municipio_id', $mid))
                ->select(
                    'vehicle_positions.vehicle_id',
                    'vehicle_positions.latitude',
                    'vehicle_positions.longitude',
                    'vehicle_positions.speed_kmh',
                    'vehicle_positions.fix_time',
                    'vehicle_positions.valid',
                    'vehicles.prefix',
                    'vehicles.plate',
                    'vehicles.municipio_id',
                )
                ->get()
                ->map(fn ($p) => [
                    'vehicle_id'   => $p->vehicle_id,
                    'prefix'       => $p->prefix ?? $p->plate ?? 'VTR',
                    'lat'          => (float) $p->latitude,
                    'lng'          => (float) $p->longitude,
                    'speed_kmh'    => (float) ($p->speed_kmh ?? 0),
                    'fix_time'     => $p->fix_time ? \Carbon\Carbon::parse($p->fix_time)->format('H:i:s') : null,
                    'valid'        => (bool) $p->valid,
                    'municipio_id' => $p->municipio_id,
                ])
                ->values();
        } catch (\Throwable) {
            $mapVehicles = collect();
        }

        return view('livewire.operations.tactical-map', [
            'mapIncidents' => $mapIncidents,
            'mapVehicles'  => $mapVehicles,
        ]);
    }
}
