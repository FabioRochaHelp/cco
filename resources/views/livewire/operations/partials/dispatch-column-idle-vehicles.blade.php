<flux:card class="flex max-h-[min(72vh,44rem)] flex-col gap-3 shadow-sm lg:sticky lg:top-24">
    <div>
        <flux:subheading>{{ __('Viaturas sem turno') }}</flux:subheading>
        <flux:text size="sm" class="mt-1 text-slate-600 dark:text-slate-400">
            {{ __('Cadastro sem turno vigente (sem janela com fim ≥ agora). Abra um turno para aparecer à esquerda.') }}
        </flux:text>
    </div>

    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pe-1">
        @forelse ($vehiclesWithoutShift as $vehicle)
            <div
                wire:key="idle-vehicle-{{ $vehicle->id }}"
                class="rounded-xl border border-slate-200/90 bg-white/90 px-3 py-3 dark:border-slate-700/60 dark:bg-slate-900/35"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold text-slate-900 dark:text-slate-50">{{ $vehicle->prefix ?? __('Sem prefixo') }}</span>
                    <flux:badge size="sm" color="zinc" inset>{{ __('Sem turno') }}</flux:badge>
                </div>
                <span class="font-mono text-sm text-slate-600 dark:text-slate-400">{{ $vehicle->plate ?? '—' }}</span>
                <span class="mt-1 block text-xs text-slate-500">{{ $vehicle->municipio?->razao_social ?? ('#'.$vehicle->municipio_id) }}</span>
            </div>
        @empty
            <flux:text size="sm" class="py-4 text-center text-slate-500">{{ __('Todas as viaturas possuem turno vigente ou não há viaturas cadastradas.') }}</flux:text>
        @endforelse
    </div>
</flux:card>
