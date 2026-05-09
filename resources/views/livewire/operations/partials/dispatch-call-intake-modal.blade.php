@php
    $callIntakeExpiresLabel = null;
    $rawExpiry = $callIntakePrefill['expires_at'] ?? '';
    if (is_string($rawExpiry) && $rawExpiry !== '') {
        try {
            $callIntakeExpiresLabel = \Carbon\CarbonImmutable::parse($rawExpiry)
                ->timezone(config('app.timezone'))
                ->format('d/m/Y H:i');
        } catch (\Throwable) {
            $callIntakeExpiresLabel = null;
        }
    }
@endphp

<flux:modal
    wire:model.self="showCallIntakeModal"
    wire:close="closeCallIntakeModal"
    variant="floating"
    class="max-h-[92vh] w-full max-w-5xl overflow-y-auto"
>
    @if ($showCallIntakeModal)
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200/90 pb-4 dark:border-slate-700/60">
                <div class="min-w-0 flex-1">
                    <flux:heading size="lg">{{ __('Nova chamada (PABX)') }}</flux:heading>
                    @if ($callIntakeExpiresLabel !== null)
                        <flux:text size="sm" class="mt-1 text-slate-600 dark:text-slate-400">
                            {{ __('Link para formulário externo / convidado expira em :hora.', ['hora' => $callIntakeExpiresLabel]) }}
                        </flux:text>
                    @endif
                    @if (! empty($callIntakePrefill['form_url']))
                        <div class="mt-3 flex flex-wrap gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="arrow-right-circle"
                                :href="$callIntakePrefill['form_url']"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ __('Abrir link assinado (nova aba)') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Fechar') }}</flux:button>
                </flux:modal.close>
            </div>

            <livewire:operations.incident-create
                wire:key="call-intake-{{ $callIntakeRenderKey }}"
                :embedded-in-modal="true"
                :caller-phone="$callIntakePrefill['phone'] ?? ''"
                :caller-name="$callIntakePrefill['caller_name'] ?? ''"
                :latitude="$callIntakePrefill['latitude'] ?? ''"
                :longitude="$callIntakePrefill['longitude'] ?? ''"
                :call-received-at="$callIntakePrefill['call_received_at'] ?? ''"
                :reference-notes="$callIntakePrefill['external_reference'] ?? ''"
            />
        </div>
    @endif
</flux:modal>
