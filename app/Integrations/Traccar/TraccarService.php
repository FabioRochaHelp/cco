<?php

declare(strict_types=1);

namespace App\Integrations\Traccar;

use App\Integrations\Traccar\DTOs\TraccarDevice;
use App\Integrations\Traccar\DTOs\TraccarPosition;
use App\Integrations\Traccar\DTOs\TraccarRoutePoint;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class TraccarService
{
    public function __construct(private readonly TraccarClient $client) {}

    /** @return Collection<int, TraccarDevice> */
    public function devices(): Collection
    {
        return collect($this->client->devices())
            ->map(fn (array $d) => TraccarDevice::fromArray($d));
    }

    /** @return Collection<int, TraccarPosition> */
    public function positions(?int $deviceId = null): Collection
    {
        return collect($this->client->positions($deviceId))
            ->map(fn (array $p) => TraccarPosition::fromArray($p));
    }

    /**
     * Percurso de um device num intervalo de tempo local (UTC automaticamente).
     *
     * @return Collection<int, TraccarRoutePoint>
     */
    public function route(int $deviceId, Carbon $from, Carbon $to): Collection
    {
        $fromUtc = $from->copy()->utc()->toIso8601ZuluString();
        $toUtc = $to->copy()->utc()->toIso8601ZuluString();

        return collect($this->client->route($deviceId, $fromUtc, $toUtc))
            ->map(fn (array $p) => TraccarRoutePoint::fromArray($p));
    }

    /** Verifica conectividade com o servidor Traccar. */
    public function ping(): bool
    {
        try {
            $info = $this->client->serverInfo();

            return isset($info['id']);
        } catch (\Throwable) {
            return false;
        }
    }
}
