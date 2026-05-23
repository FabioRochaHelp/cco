@php
    $incidentUrlTemplate = url('/operations/incidents/__ID__');
@endphp

<div wire:poll.15s>

    {{-- ── Mapa Leaflet — fixo, preenche viewport abaixo do header ────────── --}}
    <div
        wire:ignore
        x-data="dispatchMap({
            incidents:       {{ Js::from($mapIncidents) }},
            vehicles:        {{ Js::from($mapVehicles) }},
            incidentUrlBase: '{{ $incidentUrlTemplate }}',
            vehiclesUrl:     '{{ route('operations.map.vehicles') }}'
        })"
        style="position:fixed; top:3rem; left:0; right:0; bottom:0; z-index:0"
    >
        <div x-ref="dispatchMapEl" style="width:100%; height:100%"></div>
    </div>

    {{-- ── Painel situação + legenda — fixo, canto superior esquerdo ─────── --}}
    <div
        class="w-52 rounded-xl border border-slate-200/80 bg-white/90 shadow-lg backdrop-blur-sm dark:border-slate-700/60 dark:bg-slate-900/90"
        style="position:fixed; top:calc(3rem + 0.75rem); left:0.75rem; z-index:10"
    >
        <div class="space-y-1.5 p-3">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Situação') }}</p>

            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-red-500"></span>
                <span class="text-xs text-slate-700 dark:text-slate-200">
                    <strong>{{ $mapIncidents->where('status', 'open')->count() }}</strong>
                    {{ __('abertas') }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-blue-600"></span>
                <span class="text-xs text-slate-700 dark:text-slate-200">
                    <strong>{{ $mapIncidents->where('status', '!=', 'open')->count() }}</strong>
                    {{ __('despachada(s)') }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-green-500"></span>
                <span class="text-xs text-slate-700 dark:text-slate-200">
                    <strong>{{ $mapVehicles->where('speed_kmh', '>', 2)->count() }}</strong>
                    {{ __('em movimento') }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full bg-slate-400"></span>
                <span class="text-xs text-slate-700 dark:text-slate-200">
                    <strong>{{ $mapVehicles->where('speed_kmh', '<=', 2)->count() }}</strong>
                    {{ __('parada(s)') }}
                </span>
            </div>

            @if ($mapIncidents->isEmpty() && $mapVehicles->isEmpty())
                <p class="text-xs text-slate-400">{{ __('Sem coordenadas.') }}</p>
            @endif
        </div>

        <div class="mx-3 border-t border-slate-100 dark:border-slate-700/60"></div>

        <div class="space-y-1 p-3">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ __('Legenda') }}</p>
            <div class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300">
                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-red-500"></span>{{ __('Ocorrência aberta') }}
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300">
                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-blue-600"></span>{{ __('Despachada') }}
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300">
                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-green-500"></span>{{ __('Viatura em movimento') }}
            </div>
            <div class="flex items-center gap-2 text-[11px] text-slate-600 dark:text-slate-300">
                <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-slate-400"></span>{{ __('Viatura parada') }}
            </div>
        </div>

        <div class="mx-3 border-t border-slate-100 dark:border-slate-700/60"></div>
        <p class="px-3 py-2 text-[10px] text-slate-400 dark:text-slate-500">{{ __('Atualiza a cada 15s') }}</p>
    </div>

    {{-- ── Lista de ocorrências — fixo, canto inferior direito ────────────── --}}
    @if ($mapIncidents->isNotEmpty())
        <div
            class="w-64 rounded-xl border border-slate-200/80 bg-white/90 shadow-lg backdrop-blur-sm dark:border-slate-700/60 dark:bg-slate-900/90"
            style="position:fixed; bottom:1rem; right:0.75rem; z-index:10"
        >
            <div class="border-b border-slate-100 px-3 py-2 dark:border-slate-700/60">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    {{ __('Ocorrências no mapa') }} ({{ $mapIncidents->count() }})
                </p>
            </div>
            <ul class="max-h-56 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700/50">
                @foreach ($mapIncidents as $inc)
                    <li>
                        <a
                            href="{{ $inc['url'] }}"
                            target="_blank"
                            class="flex items-center gap-2 px-3 py-2 text-xs hover:bg-slate-50 dark:hover:bg-slate-800/60"
                        >
                            <span class="inline-block h-2 w-2 shrink-0 rounded-full {{ $inc['status'] === 'open' ? 'bg-red-500' : 'bg-blue-600' }}"></span>
                            <span class="min-w-0 flex-1 truncate font-medium text-slate-700 dark:text-slate-200">{{ $inc['nature'] }}</span>
                            <span class="shrink-0 tabular-nums text-slate-400">{{ $inc['talao'] }}/{{ $inc['year'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</div>
