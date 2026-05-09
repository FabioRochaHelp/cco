<?php

declare(strict_types=1);

return [
    /**
     * Segredo para o webhook `POST /integrations/calls/incident-intake`.
     * Envio obrigatório no cabeçalho HTTP `X-Webhook-Secret`.
     */
    'call_webhook_secret' => (string) env('OPERATIONS_CALL_WEBHOOK_SECRET', ''),

    /** Tempo de vida da URL assinada retornada pelo webhook (minutos). */
    'call_intake_signed_url_ttl_minutes' => max(5, min(120, (int) env('OPERATIONS_CALL_INTAKE_URL_TTL_MINUTES', 30))),

    /**
     * Envia evento WebSocket (Reverb) para o canal `operations.dispatch`, abrindo o formulário nos navegadores conectados.
     * Requer BROADCAST_CONNECTION=reverb (ou compatível) e `php artisan reverb:start`.
     */
    'broadcast_call_intake' => filter_var(env('OPERATIONS_BROADCAST_CALL_INTAKE', true), FILTER_VALIDATE_BOOL),
];
