<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Operations\Events\VehiclePositionUpdated;
use App\Integrations\Traccar\DTOs\TraccarPosition;
use App\Integrations\Traccar\TraccarService;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SyncTraccarPositions implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function handle(TraccarService $traccar): void
    {
        $positions = $traccar->positions();

        if ($positions->isEmpty()) {
            return;
        }

        // Indexa posições pelo deviceId numérico do Traccar
        $byDeviceId = $positions->keyBy(fn (TraccarPosition $p) => (string) $p->deviceId);

        // Busca viaturas que têm device_id e cujo device_id está entre as posições recebidas
        $vehicles = Vehicle::query()
            ->whereNotNull('device_id')
            ->whereIn('device_id', $byDeviceId->keys())
            ->get();

        if ($vehicles->isEmpty()) {
            return;
        }

        $now = now();
        $tobroadcast = [];

        DB::transaction(function () use ($vehicles, $byDeviceId, $now, &$tobroadcast): void {
            foreach ($vehicles as $vehicle) {
                /** @var TraccarPosition|null $pos */
                $pos = $byDeviceId->get((string) $vehicle->device_id);

                if ($pos === null || $vehicle->municipio_id === null) {
                    continue;
                }

                VehiclePosition::query()->updateOrCreate(
                    ['vehicle_id' => $vehicle->id],
                    [
                        'device_id' => (string) $pos->deviceId,
                        'latitude' => $pos->latitude,
                        'longitude' => $pos->longitude,
                        'altitude' => $pos->altitude ?: null,
                        'speed_kmh' => $pos->speedKmh(),
                        'course' => $pos->course ?: null,
                        'address' => $pos->address ?: null,
                        'valid' => $pos->valid,
                        'fix_time' => $pos->fixTime,
                        'synced_at' => $now,
                    ]
                );

                $tobroadcast[] = new VehiclePositionUpdated(
                    vehicleId: $vehicle->id,
                    municipioId: (int) $vehicle->municipio_id,
                    prefix: (string) ($vehicle->prefix ?? $vehicle->plate ?? 'VTR'),
                    latitude: $pos->latitude,
                    longitude: $pos->longitude,
                    speedKmh: $pos->speedKmh(),
                    fixTime: $pos->fixTime,
                    valid: $pos->valid,
                );
            }
        });

        foreach ($tobroadcast as $event) {
            event($event);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::warning('SyncTraccarPositions falhou', ['error' => $e->getMessage()]);
    }
}
