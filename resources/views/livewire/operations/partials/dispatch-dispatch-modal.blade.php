<flux:modal wire:model.self="showDispatchModal" wire:close="closeDispatchModal" variant="floating" class="max-w-lg">
    @if ($modalIncident !== null)
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Empenhar equipe') }}</flux:heading>
                <flux:text class="mt-1 text-slate-600 dark:text-slate-400">
                    {{ __('Escolha um turno disponível na mesma base da ocorrência.') }}
                </flux:text>
            </div>

            <div class="rounded-xl border border-slate-200/90 bg-slate-50/80 px-4 py-3 dark:border-slate-700/60 dark:bg-slate-900/40">
                <flux:text class="font-semibold tabular-nums text-slate-900 dark:text-slate-50">
                    {{ __('Talão') }} {{ $modalIncident->talao }}/{{ $modalIncident->dispatch_year }}
                </flux:text>
                <flux:text size="sm" class="mt-1 text-slate-600 dark:text-slate-400">{{ $modalIncident->occurred_at->format('d/m/Y H:i') }}</flux:text>
                @if ($modalIncident->address_line || $modalIncident->city)
                    <flux:text size="sm" class="mt-2">{{ trim(implode(' · ', array_filter([$modalIncident->address_line, $modalIncident->city]))) }}</flux:text>
                @endif
                <flux:text size="sm" class="mt-1 text-slate-500">{{ $modalIncident->municipio?->razao_social ?? ('#'.$modalIncident->municipio_id) }}</flux:text>
            </div>

            @error('modalVehicleId')
                <flux:callout variant="danger">{{ $message }}</flux:callout>
            @enderror

            @if ($modalShifts->isEmpty())
                <flux:callout variant="warning">{{ __('Não há turnos disponíveis nesta base para empenho.') }}</flux:callout>
            @else
                <flux:fieldset :legend="__('Turno / viatura')">
                    <div class="grid gap-2">
                        @foreach ($modalShifts as $shift)
                            <button
                                type="button"
                                wire:key="modal-shift-{{ $shift->id }}"
                                wire:click="$set('modalVehicleId', {{ $shift->vehicle_id }})"
                                @class([
                                    'flex w-full flex-col gap-1 rounded-lg border px-3 py-2.5 text-start text-sm transition',
                                    'border-cyan-500/80 bg-cyan-500/10 dark:border-cyan-400/40 dark:bg-cyan-500/10' => $modalVehicleId === $shift->vehicle_id,
                                    'border-slate-200/90 bg-white hover:border-cyan-400/40 dark:border-slate-700/60 dark:bg-slate-900/30 dark:hover:border-cyan-500/30' => $modalVehicleId !== $shift->vehicle_id,
                                ])
                            >
                                <span class="font-semibold">{{ $shift->vehicle?->prefix ?? __('Sem prefixo') }}</span>
                                <span class="font-mono text-xs text-slate-600 dark:text-slate-400">{{ $shift->vehicle?->plate ?? __('Sem placa') }}</span>
                                <span class="text-[11px] uppercase tracking-wide text-slate-400">{{ __('Turno') }} #{{ $shift->id }}</span>
                            </button>
                        @endforeach
                    </div>
                </flux:fieldset>
            @endif

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/90 pt-4 dark:border-slate-700/60">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>
                @if ($modalShifts->isEmpty() || $modalVehicleId === null)
                    <flux:button variant="primary" icon="paper-airplane" type="button" disabled wire:loading.attr="disabled">
                        {{ __('Confirmar empenho') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" icon="paper-airplane" type="button" wire:click="confirmDispatch" wire:loading.attr="disabled">
                        {{ __('Confirmar empenho') }}
                    </flux:button>
                @endif
            </div>
        </div>
    @endif
</flux:modal>
