{{-- Modal: Cancelar ocorrência --}}
<flux:modal wire:model.self="showCancelModal" class="max-w-md">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('Cancelar ocorrência') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('Informe o motivo do cancelamento. Esta ação é registrada no histórico.') }}
            </flux:text>
        </div>

        @error('cancelReason')
            <flux:callout variant="danger" class="text-sm">{{ $message }}</flux:callout>
        @enderror

        <flux:textarea
            wire:model="cancelReason"
            :label="__('Motivo')"
            rows="3"
            placeholder="{{ __('Descreva o motivo do cancelamento…') }}"
            autofocus
        />

        <div class="flex gap-2">
            <flux:button variant="danger" wire:click="cancelIncident" wire:loading.attr="disabled">
                {{ __('Confirmar cancelamento') }}
            </flux:button>
            <flux:button variant="ghost" wire:click="closeActionModals">
                {{ __('Voltar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>

{{-- Modal: Inserir observação --}}
<flux:modal wire:model.self="showObservationModal" class="max-w-lg">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">{{ __('Inserir observação') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
                {{ __('Texto livre registrado no histórico da ocorrência.') }}
            </flux:text>
        </div>

        @error('observationText')
            <flux:callout variant="danger" class="text-sm">{{ $message }}</flux:callout>
        @enderror

        <flux:textarea
            wire:model="observationText"
            :label="__('Observação')"
            rows="4"
            placeholder="{{ __('Descreva a observação…') }}"
            autofocus
        />

        <div class="flex gap-2">
            <flux:button variant="primary" wire:click="saveObservation" wire:loading.attr="disabled">
                {{ __('Salvar observação') }}
            </flux:button>
            <flux:button variant="ghost" wire:click="closeActionModals">
                {{ __('Cancelar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>

{{-- Modal: Detalhe da ocorrência --}}
<flux:modal wire:model.self="showDetailModal" class="max-w-xl">
    @if ($actionIncident !== null)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    {{ __('Talão :talao/:ano', ['talao' => $actionIncident->talao, 'ano' => $actionIncident->dispatch_year]) }}
                </flux:heading>
                <flux:badge color="zinc" size="sm">{{ $actionIncident->status->label() }}</flux:badge>
            </div>

            <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Natureza') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $actionIncident->nature?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Tipo de chamada') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ \App\Domain\Operations\Enums\CallType::tryFrom((string) $actionIncident->patient_call_type)?->label() ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Data/hora') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $actionIncident->occurred_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Município') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $actionIncident->municipio?->razao_social ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Endereço') }}</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                        {{ collect([$actionIncident->address_line, $actionIncident->city])->filter()->join(', ') ?: '—' }}
                    </dd>
                </div>
                @if ($actionIncident->reference_notes)
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Referência') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $actionIncident->reference_notes }}</dd>
                    </div>
                @endif
                @if ($actionIncident->manchester_risk)
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Risco Manchester') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $actionIncident->manchester_risk->label() }}</dd>
                    </div>
                @endif
                @if ($actionIncident->caller_name || $actionIncident->caller_phone)
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Solicitante') }}</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                            {{ collect([$actionIncident->caller_name, $actionIncident->caller_phone])->filter()->join(' · ') }}
                        </dd>
                    </div>
                @endif
                @if ($actionIncident->description)
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">{{ __('Descrição') }}</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $actionIncident->description }}</dd>
                    </div>
                @endif
            </dl>

            <div class="flex gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-800">
                <flux:button :href="route('operations.incidents.show', $actionIncident)" variant="ghost" size="sm" wire:navigate>
                    {{ __('Abrir ocorrência completa') }}
                </flux:button>
                <flux:button variant="ghost" size="sm" wire:click="closeActionModals">
                    {{ __('Fechar') }}
                </flux:button>
            </div>
        </div>
    @endif
</flux:modal>
