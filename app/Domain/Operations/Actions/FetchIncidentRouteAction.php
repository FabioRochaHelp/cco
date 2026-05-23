<?php

declare(strict_types=1);

namespace App\Domain\Operations\Actions;

use App\Integrations\Traccar\DTOs\TraccarRoutePoint;
use App\Integrations\Traccar\TraccarService;
use App\Models\Incident;
use Illuminate\Support\Collection;
use RuntimeException;

final class FetchIncidentRouteAction
{
    public function __construct(private readonly TraccarService $traccar) {}

    /**
     * Retorna os pontos de percurso de uma ocorrência para plotagem no Leaflet.
     *
     * @return Collection<int, TraccarRoutePoint>
     *
     * @throws RuntimeException se a viatura não tiver device_id ou intervalo incompleto
     */
    public function execute(Incident $incident): Collection
    {
        if ($incident->dispatched_at === null) {
            throw new RuntimeException(__('Ocorrência sem hora de empenho registrada.'));
        }

        // Busca despacho mais recente para obter a viatura/device
        $dispatch = $incident->dispatches()
            ->with('shift.vehicle')
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if ($dispatch === null) {
            throw new RuntimeException(__('Ocorrência sem despacho registrado.'));
        }

        $vehicle = $dispatch->shift?->vehicle;

        if ($vehicle === null || $vehicle->device_id === null) {
            throw new RuntimeException(__('Viatura sem vínculo com device Traccar.'));
        }

        $from = $incident->dispatched_at;
        $to   = $incident->returned_base_at ?? now();

        $route = $this->traccar->route((int) $vehicle->device_id, $from, $to);

        if ($route->isEmpty()) {
            // Fallback sem ajuste de timezone (mesma lógica do CI4 legado)
            $route = $this->traccar->route((int) $vehicle->device_id, $from->utc(), $to->utc());
        }

        return $route;
    }
}
