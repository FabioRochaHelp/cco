<?php

declare(strict_types=1);

namespace App\Integrations\Traccar;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class TraccarClient
{
    private readonly string $baseUrl;

    private readonly string $email;

    private readonly string $password;

    private readonly int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('traccar.url', ''), '/');
        $this->email = (string) config('traccar.email', '');
        $this->password = (string) config('traccar.password', '');
        $this->timeout = (int) config('traccar.timeout', 10);
    }

    /** GET /api/server — sem auth, usado para health check. */
    public function serverInfo(): array
    {
        $response = Http::timeout($this->timeout)
            ->get("{$this->baseUrl}/api/server");

        $this->assertOk($response, 'server');

        return $response->json() ?? [];
    }

    /** GET /api/devices — lista todos os devices acessíveis ao usuário. */
    public function devices(): array
    {
        return $this->authed()->get("{$this->baseUrl}/api/devices")->json() ?? [];
    }

    /** GET /api/positions — posições atuais. Filtra por deviceId se fornecido. */
    public function positions(?int $deviceId = null): array
    {
        $params = $deviceId !== null ? ['deviceId' => $deviceId] : [];

        return $this->authed()->get("{$this->baseUrl}/api/positions", $params)->json() ?? [];
    }

    /**
     * GET /api/reports/route — percurso de um device num intervalo.
     *
     * @param  string  $from  ISO-8601 UTC, ex: 2026-05-22T14:00:00.000Z
     * @param  string  $to    ISO-8601 UTC
     */
    public function route(int $deviceId, string $from, string $to): array
    {
        return $this->authed()
            ->get("{$this->baseUrl}/api/reports/route", [
                'deviceId' => $deviceId,
                'from' => $from,
                'to' => $to,
            ])
            ->json() ?? [];
    }

    /** GET /api/reports/summary — resumo de uma viagem. */
    public function summary(int $deviceId, string $from, string $to): array
    {
        return $this->authed()
            ->get("{$this->baseUrl}/api/reports/summary", [
                'deviceId' => $deviceId,
                'from' => $from,
                'to' => $to,
            ])
            ->json() ?? [];
    }

    /** GET /api/events — eventos de um device num intervalo. */
    public function events(int $deviceId, string $from, string $to): array
    {
        return $this->authed()
            ->get("{$this->baseUrl}/api/reports/events", [
                'deviceId' => $deviceId,
                'from' => $from,
                'to' => $to,
            ])
            ->json() ?? [];
    }

    /** Monta request com Basic auth. */
    private function authed(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withBasicAuth($this->email, $this->password)
            ->accept('application/json');
    }

    private function assertOk(Response $response, string $endpoint): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                "Traccar [{$endpoint}] returned HTTP {$response->status()}"
            );
        }
    }
}
