<div class="cco-page-gap" wire:poll.10s>
    @include('livewire.operations.partials.dispatch-header')
    @include('livewire.operations.partials.dispatch-alerts')

    <section class="cco-surface">
        <div class="cco-surface-header">
            <div>
                <div class="cco-surface-title">
                    <flux:icon.radio class="size-4 text-cyan-700 dark:text-cyan-300" />
                    <span>{{ __('Despacho') }}</span>
                </div>
                <div class="cco-surface-subtitle">{{ __('Fila, turnos disponíveis e viaturas sem turno.') }}</div>
            </div>
            <flux:badge color="cyan" size="sm">{{ __('Atualiza a cada 10s') }}</flux:badge>
        </div>

        <div class="grid gap-4 lg:grid-cols-12 lg:items-start">
            <aside class="order-1 lg:col-span-2 xl:col-span-2">
                @include('livewire.operations.partials.dispatch-column-shifts')
            </aside>

            <section class="order-2 flex min-w-0 flex-col gap-4 lg:col-span-8 xl:col-span-8">
                @include('livewire.operations.partials.open-incidents')
                @include('livewire.operations.partials.dispatch-dispatch-modal')
                @include('livewire.operations.partials.dispatch-incident-actions-modal')
            </section>

            <aside class="order-3 lg:col-span-2 xl:col-span-2">
                @include('livewire.operations.partials.dispatch-column-idle-vehicles')
            </aside>
        </div>
    </section>

    <section class="cco-surface">
        <div class="cco-surface-header">
            <div>
                <div class="cco-surface-title">
                    <flux:icon.rectangle-stack class="size-4 text-cyan-700 dark:text-cyan-300" />
                    <span>{{ __('Kanban operacional') }}</span>
                </div>
                <div class="cco-surface-subtitle">{{ __('Etapas do empenho até retorno.') }}</div>
            </div>
        </div>

        @include('livewire.operations.partials.kanban')
    </section>

    <section class="cco-surface">
        <div class="cco-surface-header">
            <div>
                <div class="cco-surface-title">
                    <flux:icon.chart-bar class="size-4 text-cyan-700 dark:text-cyan-300" />
                    <span>{{ __('Indicadores') }}</span>
                </div>
                <div class="cco-surface-subtitle">{{ __('Resumo rápido do estado operacional.') }}</div>
            </div>
        </div>

        @include('livewire.operations.partials.tactical-strip', ['stats' => $stats])
    </section>

    <section class="cco-surface">
        <div class="cco-surface-header">
            <div>
                <div class="cco-surface-title">
                    <flux:icon.map class="size-4 text-cyan-700 dark:text-cyan-300" />
                    <span>{{ __('Mapa e feed') }}</span>
                </div>
                <div class="cco-surface-subtitle">{{ __('Camadas táticas e últimos eventos.') }}</div>
            </div>
        </div>

        @include('livewire.operations.partials.map-and-feed')
    </section>

    <flux:text size="sm" class="border-t border-slate-200/95 pt-6 text-slate-600 dark:border-slate-700/50 dark:text-slate-500">
        {{ __('Eventos persistidos em') }}
        <code
            class="rounded-md border border-slate-300/90 bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-cyan-900 dark:border-slate-700/60 dark:bg-slate-950/80 dark:text-cyan-200/90"
        >incident_events</code>.
        {{ __('Broadcast privado:') }}
        <code
            class="rounded-md border border-slate-300/90 bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-cyan-900 dark:border-slate-700/60 dark:bg-slate-950/80 dark:text-cyan-200/90"
        >operations.dispatch</code>,
        <code
            class="rounded-md border border-slate-300/90 bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-cyan-900 dark:border-slate-700/60 dark:bg-slate-950/80 dark:text-cyan-200/90"
        >operations.municipio.{id}</code>,
        <code
            class="rounded-md border border-slate-300/90 bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-cyan-900 dark:border-slate-700/60 dark:bg-slate-950/80 dark:text-cyan-200/90"
        >incidents.{id}</code>.
    </flux:text>
</div>
