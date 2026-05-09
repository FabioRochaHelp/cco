<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Operations\Events\OperationalCallIntakeReceived;
use App\Http\Controllers\Controller;
use App\Support\Operations\IncidentPhoneNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Equivalente operacional à rota legada `v1/ocorrencia/nova-chamada`: recebe dados da chamada
 * e devolve uma URL assinada que abre o formulário Livewire já hidratado.
 *
 * @see docs/migracao/fluxo-ocorrencias.md
 */
final class IncidentCallIntakeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $configuredSecret = config('operations.call_webhook_secret');
        if ($configuredSecret === '') {
            abort(config('app.env') === 'production' ? 503 : 422, __('Webhook de chamada não configurado (OPERATIONS_CALL_WEBHOOK_SECRET).'));
        }

        $provided = (string) $request->header('X-Webhook-Secret', '');
        if (! hash_equals($configuredSecret, $provided)) {
            abort(403);
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'caller_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'call_received_at' => ['nullable', 'date'],
            'external_reference' => ['nullable', 'string', 'max:500'],
        ]);

        $phone = IncidentPhoneNormalizer::normalize($validated['phone']);
        if (! IncidentPhoneNormalizer::passesMinimumLength($phone)) {
            return response()->json([
                'message' => __('O número informado em «phone» deve ter ao menos 8 dígitos.'),
            ], 422);
        }

        $ttl = now()->addMinutes((int) config('operations.call_intake_signed_url_ttl_minutes', 30));

        $query = array_filter([
            'phone' => $phone,
            'name' => $validated['caller_name'] ?? null,
            'lat' => isset($validated['latitude']) ? (string) $validated['latitude'] : null,
            'lng' => isset($validated['longitude']) ? (string) $validated['longitude'] : null,
            'received_at' => isset($validated['call_received_at'])
                ? CarbonImmutable::parse((string) $validated['call_received_at'])->toIso8601String()
                : null,
            'ref' => $validated['external_reference'] ?? null,
        ], static fn (?string $v): bool => $v !== null && $v !== '');

        $formUrl = URL::temporarySignedRoute('operations.incidents.create', $ttl, $query);

        $expiresAtIso = $ttl->toIso8601String();

        $callReceivedAtIso = isset($validated['call_received_at'])
            ? CarbonImmutable::parse((string) $validated['call_received_at'])->toIso8601String()
            : null;

        if (config('operations.broadcast_call_intake', true)) {
            OperationalCallIntakeReceived::dispatch(
                $formUrl,
                $phone,
                $expiresAtIso,
                $validated['caller_name'] ?? null,
                isset($validated['latitude']) ? (string) $validated['latitude'] : null,
                isset($validated['longitude']) ? (string) $validated['longitude'] : null,
                $callReceivedAtIso,
                $validated['external_reference'] ?? null,
            );
        }

        return response()->json([
            'form_url' => $formUrl,
            'expires_at' => $expiresAtIso,
        ]);
    }
}
