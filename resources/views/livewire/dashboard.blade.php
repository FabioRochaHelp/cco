<div class="flex flex-col gap-8">
    @can('viewAny', \App\Models\Incident::class)
        <div class="mb-0">
            <flux:heading size="xl" class="tracking-tight text-slate-800 dark:text-slate-100">{{ __('Painel') }}</flux:heading>
            <flux:text class="mt-1 max-w-2xl text-slate-600 dark:text-slate-400">{{ __('Acesso rápido ao núcleo operacional — use os atalhos abaixo ou o menu lateral.') }}</flux:text>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <a href="{{ route('operations.dispatch') }}" wire:navigate class="cco-quick-link">
                <flux:icon.radio class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                <span class="cco-quick-link-title">{{ __('Central operacional') }}</span>
                <span class="cco-quick-link-meta">{{ __('CCO · despacho') }}</span>
            </a>
            <a href="{{ route('operations.incidents.index') }}" wire:navigate class="cco-quick-link">
                <flux:icon.rectangle-stack class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                <span class="cco-quick-link-title">{{ __('Ocorrências') }}</span>
                <span class="cco-quick-link-meta">{{ __('Lista e acompanhamento') }}</span>
            </a>
            @if (auth()->user()?->hasOperationalAbility('incident.create'))
                <a href="{{ route('operations.incidents.start') }}" wire:navigate class="cco-quick-link">
                    <flux:icon.plus-circle class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                    <span class="cco-quick-link-title">{{ __('Nova ocorrência') }}</span>
                    <span class="cco-quick-link-meta">{{ __('Informar telefone da chamada') }}</span>
                </a>
            @endif
            <a href="{{ route('operations.fleet') }}" wire:navigate class="cco-quick-link">
                <flux:icon.truck class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                <span class="cco-quick-link-title">{{ __('Turnos e viaturas') }}</span>
                <span class="cco-quick-link-meta">{{ __('Escalas e disponibilidade') }}</span>
            </a>
            @if (auth()->user()?->isOperationalCentral())
                <a href="{{ route('operations.parameters.natures') }}" wire:navigate class="cco-quick-link">
                    <flux:icon.adjustments-horizontal class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                    <span class="cco-quick-link-title">{{ __('Parâmetros da ocorrência') }}</span>
                    <span class="cco-quick-link-meta">{{ __('Cadastros globais') }}</span>
                </a>
            @endif
            @if (auth()->user()?->isOperationalCentral())
                <a href="{{ route('operations.cadastro.bases') }}" wire:navigate class="cco-quick-link">
                    <flux:icon.building-office-2 class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                    <span class="cco-quick-link-title">{{ __('Bases') }}</span>
                    <span class="cco-quick-link-meta">{{ __('Municípios contratantes') }}</span>
                </a>
            @endif
            <a href="{{ route('operations.cadastro.vehicles') }}" wire:navigate class="cco-quick-link">
                <flux:icon.cube class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                <span class="cco-quick-link-title">{{ __('Viaturas') }}</span>
                <span class="cco-quick-link-meta">{{ __('Unidades móveis') }}</span>
            </a>
            <a href="{{ route('operations.cadastro.staff') }}" wire:navigate class="cco-quick-link">
                <flux:icon.users class="size-5 text-cyan-600 dark:text-cyan-400/90" />
                <span class="cco-quick-link-title">{{ __('Efetivo') }}</span>
                <span class="cco-quick-link-meta">{{ __('Equipe operacional') }}</span>
            </a>
        </div>
    @endcan

    @if ($showCallStats)
        <div wire:key="call-stats-{{ $callStatsBroadcastTick }}">
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Chamadas por tipo') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Totais de hoje no seu escopo operacional (fuso :timezone).', ['timezone' => config('app.timezone')]) }}
                    </flux:text>
                    <flux:text size="sm" class="mt-1 text-zinc-500 dark:text-zinc-400">
                        {{ __('Atualização em tempo real via WebSocket (Reverb), ao criar ou alterar ocorrências relevantes.') }}
                    </flux:text>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @foreach ($callTypeStats as $row)
                        <div wire:key="call-stat-{{ $row['code'] }}" class="flex flex-col gap-2 rounded-xl border border-zinc-200 bg-zinc-50/80 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                            <flux:text size="sm" class="font-medium leading-tight text-zinc-800 dark:text-zinc-100">{{ $row['label'] }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">{{ __('Código') }} {{ $row['code'] }}</flux:text>
                            <span class="text-3xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50" data-test="call-count-{{ $row['code'] }}">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>
    @if ($modalityStats !== null)
        @assets
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        @endassets

        @php
            $slices     = $modalityStats['slices'];
            $totalCount = $modalityStats['total'];
        @endphp

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Ocorrências por modalidade') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $modalityStats['month'] }} · {{ $totalCount }} {{ __('ocorrência(s) no seu escopo') }}
                </flux:text>
            </div>

            @if ($totalCount === 0)
                <flux:text class="text-zinc-500">{{ __('Nenhuma ocorrência registrada neste mês.') }}</flux:text>
            @else
                <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                    {{-- Donut Chart.js --}}
                    <div
                        class="relative shrink-0"
                        x-data="{
                            init() {
                                new Chart(this.$refs.canvas, {
                                    type: 'doughnut',
                                    data: {
                                        labels: {{ Js::from(array_column($slices, 'label')) }},
                                        datasets: [{
                                            data: {{ Js::from(array_column($slices, 'count')) }},
                                            backgroundColor: {{ Js::from(array_column($slices, 'color')) }},
                                            borderWidth: 2,
                                            borderColor: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
                                            hoverBorderColor: 'transparent',
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        cutout: '60%',
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                callbacks: {
                                                    label: (ctx) => ` ${ctx.parsed} ocorrência(s) (${ctx.dataset.data.reduce((a,b)=>a+b,0) > 0 ? Math.round(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100) : 0}%)`
                                                }
                                            }
                                        }
                                    }
                                });
                            }
                        }"
                    >
                        <canvas x-ref="canvas" width="176" height="176"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $totalCount }}</span>
                            <span class="text-xs text-zinc-500">{{ __('total') }}</span>
                        </div>
                    </div>

                    {{-- Legenda --}}
                    <ul class="flex flex-1 flex-col justify-center gap-2">
                        @foreach ($slices as $slice)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="size-3 shrink-0 rounded-sm" style="background-color:{{ $slice['color'] }}"></span>
                                    <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $slice['label'] }}</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-2 tabular-nums">
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $slice['count'] }}</span>
                                    <span class="w-10 text-end text-zinc-400">{{ $slice['percentage'] }}%</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </flux:card>
    @endif

    @else
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-slate-200/95 bg-white/70 shadow-md shadow-slate-900/5 dark:border-slate-700/50 dark:bg-slate-900/40 dark:shadow-lg dark:shadow-black/30">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-cyan-600/20 dark:stroke-cyan-400/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-slate-200/95 bg-white/70 shadow-md shadow-slate-900/5 dark:border-slate-700/50 dark:bg-slate-900/40 dark:shadow-lg dark:shadow-black/30">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-cyan-600/20 dark:stroke-cyan-400/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-slate-200/95 bg-white/70 shadow-md shadow-slate-900/5 dark:border-slate-700/50 dark:bg-slate-900/40 dark:shadow-lg dark:shadow-black/30">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-cyan-600/20 dark:stroke-cyan-400/20" />
                </div>
            </div>
            <div class="relative h-full min-h-[12rem] flex-1 overflow-hidden rounded-xl border border-slate-200/95 bg-white/60 shadow-inner shadow-slate-900/5 dark:border-slate-700/50 dark:bg-slate-900/35 dark:shadow-inner dark:shadow-black/40">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-cyan-600/15 dark:stroke-cyan-400/15" />
                <div class="relative z-10 flex h-full items-center justify-center p-6">
                    <flux:text class="max-w-md text-center text-sm text-slate-600 dark:text-slate-500">{{ __('Área reservada para indicadores, mapas ou filas — personalize conforme o CCO.') }}</flux:text>
                </div>
            </div>
        </div>
    @endif
</div>
