<flux:card class="flex max-h-[min(72vh,44rem)] flex-col gap-3 shadow-sm lg:sticky lg:top-24">
    <div>
        <flux:subheading>{{ __('Turnos disponíveis') }}</flux:subheading>
        <flux:text size="sm" class="mt-1 text-slate-600 dark:text-slate-400">
            {{ __('Referência de viaturas em turno disponível. O vínculo ocorrência ↔ turno é feito só no empenho (clique na fila e escolha a viatura).') }}
        </flux:text>
    </div>

    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pe-1">
        @forelse ($availableShifts as $shift)
            <div
                wire:key="disp-shift-{{ $shift->id }}"
                class="flex flex-col gap-1 rounded-xl border border-slate-200/90 bg-white px-3 py-3 dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-slate-900 dark:text-slate-50">{{ $shift->vehicle?->prefix ?? __('Sem prefixo') }}</span>
                    <flux:badge size="sm" color="green" inset>{{ __('Disponível') }}</flux:badge>
                </div>
                <span class="font-mono text-sm text-slate-600 dark:text-slate-400">{{ $shift->vehicle?->plate ?? __('Sem placa') }}</span>
                <span class="text-xs text-slate-500">{{ $shift->municipio?->razao_social ?? ('#'.$shift->municipio_id) }}</span>
                <span class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ __('Turno') }} #{{ $shift->id }}</span>
            </div>
        @empty
            <flux:text size="sm" class="py-4 text-center text-slate-500">{{ __('Nenhum turno disponível no escopo.') }}</flux:text>
        @endforelse
    </div>
</flux:card>
