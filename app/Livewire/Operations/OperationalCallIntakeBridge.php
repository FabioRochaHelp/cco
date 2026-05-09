<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Ouve `operational-call-intake` (Echo/Reverb → Livewire.dispatch) em qualquer tela do layout operacional,
 * não só na Central — evita modal silencioso quando o operador não está em `/operations/dispatch`.
 */
final class OperationalCallIntakeBridge extends Component
{
    public bool $showCallIntakeModal = false;

    /** @var array<string, mixed> */
    public array $callIntakePrefill = [];

    public int $callIntakeRenderKey = 0;

    public function closeCallIntakeModal(): void
    {
        $this->showCallIntakeModal = false;
        $this->callIntakePrefill = [];
    }

    #[On('operational-call-intake')]
    public function openOperationalCallIntakeFromBroadcast(
        ?string $form_url = null,
        ?string $phone = null,
        ?string $expires_at = null,
        ?string $caller_name = null,
        ?string $latitude = null,
        ?string $longitude = null,
        ?string $call_received_at = null,
        ?string $external_reference = null,
    ): void {
        $user = Auth::user();
        if ($user === null || ! $user->hasOperationalAbility('incident.create')) {
            return;
        }

        $this->callIntakePrefill = [
            'form_url' => $form_url ?? '',
            'phone' => $phone ?? '',
            'expires_at' => $expires_at ?? '',
            'caller_name' => $caller_name,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'call_received_at' => $call_received_at,
            'external_reference' => $external_reference,
        ];
        $this->callIntakeRenderKey++;
        $this->showCallIntakeModal = true;
    }

    #[On('call-intake-incident-saved')]
    public function onCallIntakeIncidentSaved(int $incidentId): void
    {
        unset($incidentId);
        $this->closeCallIntakeModal();
    }

    public function render(): View
    {
        return view('livewire.operations.operational-call-intake-bridge');
    }
}
