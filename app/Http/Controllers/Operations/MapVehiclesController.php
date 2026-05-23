<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Integrations\Traccar\TraccarService;
use App\Models\Vehicle;
use App\Support\Operations\OperationalMunicipioSelection;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class MapVehiclesController extends Controller
{
    public function __invoke(TraccarService $traccar): JsonResponse
    {
        $mid = OperationalMunicipioSelection::current(Auth::user());

        $vehicles = Vehicle::query()
            ->whereNotNull('device_id')
            ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid))
            ->get()
            ->keyBy(fn ($v) => (string) $v->device_id);

        if ($vehicles->isEmpty()) {
            return response()->json([]);
        }

        try {
            $positions = $traccar->positions();
        } catch (\Throwable) {
            return response()->json([]);
        }

        $tz = config('app.timezone', 'UTC');

        $result = $positions
            ->filter(fn ($p) => $vehicles->has((string) $p->deviceId))
            ->map(function ($p) use ($vehicles, $tz) {
                $vehicle = $vehicles->get((string) $p->deviceId);

                return [
                    'vehicle_id' => $vehicle->id,
                    'prefix'     => $vehicle->prefix ?? $vehicle->plate ?? 'VTR',
                    'lat'        => $p->latitude,
                    'lng'        => $p->longitude,
                    'speed_kmh'  => $p->speedKmh(),
                    'fix_time'   => Carbon::parse($p->fixTime)->setTimezone($tz)->format('H:i:s'),
                    'valid'      => $p->valid,
                ];
            })
            ->values();

        return response()->json($result);
    }
}
