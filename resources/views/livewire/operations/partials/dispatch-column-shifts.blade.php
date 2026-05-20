<flux:card class="flex max-h-[min(64vh,38rem)] flex-col gap-3 !p-3 shadow-sm">
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <flux:icon.users class="size-4 text-cyan-700 dark:text-cyan-300" />
            <flux:subheading>{{ __('Turnos') }}</flux:subheading>
        </div>
        <flux:badge size="sm" color="green" inset>{{ __('Disponíveis') }}</flux:badge>
    </div>

    <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pe-1">
        @forelse ($availableShifts as $shift)
            @php
                $staffTooltip = $shift->staff?->isNotEmpty()
                    ? $shift->staff
                        ->map(fn ($p) => trim($p->name.($p->cargo ? ' · '.$p->cargo : '')))
                        ->implode("\n")
                    : __('Sem efetivo vinculado');
            @endphp
            <div
                wire:key="disp-shift-{{ $shift->id }}"
                class="flex flex-col gap-1 rounded-xl border border-slate-200/90 bg-white/90 px-2.5 py-2.5 text-sm dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2">
                        <flux:icon.truck class="size-4 text-slate-500 dark:text-slate-400" />
                        <span class="truncate font-semibold text-slate-900 dark:text-slate-50">{{ $shift->vehicle?->prefix ?? __('Sem prefixo') }}</span>
                    </div>
                    <span class="text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400">#{{ $shift->id }}</span>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs text-slate-500">{{ $shift->municipio?->razao_social ?? ('#'.$shift->municipio_id) }}</span>
                    <span class="inline-flex items-center gap-1 text-xs font-medium text-slate-600 dark:text-slate-400" title="{{ $staffTooltip }}">
                        <flux:icon.users class="size-4 text-slate-400 dark:text-slate-500" />
                        <span class="tabular-nums">{{ (int) ($shift->staff_count ?? 0) }}</span>
                    </span>
                </div>
            </div>
        @empty
            <flux:text size="sm" class="py-4 text-center text-slate-500">{{ __('Nenhum turno disponível no escopo.') }}</flux:text>
        @endforelse
    </div>
</flux:card>
