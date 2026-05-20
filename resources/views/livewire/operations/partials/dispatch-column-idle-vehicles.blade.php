<flux:card class="flex max-h-[min(64vh,38rem)] flex-col gap-3 !p-3 shadow-sm">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <flux:icon.truck class="size-4 text-cyan-700 dark:text-cyan-300" />
            <flux:subheading>{{ __('Viaturas') }}</flux:subheading>
        </div>
        <flux:badge size="sm" color="zinc" inset>{{ __('Sem turno') }}</flux:badge>
    </div>

    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pe-1">
        @forelse ($vehiclesWithoutShift as $vehicle)
            <div
                wire:key="idle-vehicle-{{ $vehicle->id }}"
                class="rounded-xl border border-slate-200/90 bg-white/90 px-2.5 py-2.5 text-sm dark:border-slate-700/60 dark:bg-slate-900/35"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2">
                        <flux:icon.exclamation-triangle class="size-4 text-amber-600 dark:text-amber-400" />
                        <span class="truncate font-semibold text-slate-900 dark:text-slate-50">{{ $vehicle->prefix ?? __('Sem prefixo') }}</span>
                    </div>
                </div>
                <span class="mt-1 block text-xs text-slate-500">{{ $vehicle->municipio?->razao_social ?? ('#'.$vehicle->municipio_id) }}</span>
            </div>
        @empty
            <flux:text size="sm" class="py-4 text-center text-slate-500">{{ __('Todas as viaturas possuem turno vigente ou não há viaturas cadastradas.') }}</flux:text>
        @endforelse
    </div>
</flux:card>
