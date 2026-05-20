<flux:card class="space-y-4">
    <flux:subheading>{{ __('Ocorrências — fila de despacho') }}</flux:subheading>

    @if ($openIncidents->isEmpty())
        <flux:text>{{ __('Nenhuma ocorrência aguardando empenho.') }}</flux:text>
    @else
        <div class="cco-table-shell overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200/95 text-start text-sm dark:divide-slate-800/80">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Talão') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Quando') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Endereço') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Natureza') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/95 dark:divide-slate-800/80">
                    @foreach ($openIncidents as $incident)
                        @php
                            $canDispatch = auth()->user()?->can('dispatchUnit', $incident);

                            $risk = $incident->manchester_risk?->value;
                            $riskBar = match ($risk) {
                                'red' => 'bg-red-500',
                                'orange' => 'bg-orange-500',
                                'yellow' => 'bg-yellow-500',
                                'green' => 'bg-green-500',
                                'blue' => 'bg-blue-500',
                                default => 'bg-slate-300 dark:bg-slate-600',
                            };
                            $riskTint = match ($risk) {
                                'red' => 'bg-red-50/55 dark:bg-red-500/[0.06]',
                                'orange' => 'bg-orange-50/55 dark:bg-orange-500/[0.06]',
                                'yellow' => 'bg-yellow-50/55 dark:bg-yellow-500/[0.06]',
                                'green' => 'bg-green-50/55 dark:bg-green-500/[0.06]',
                                'blue' => 'bg-blue-50/55 dark:bg-blue-500/[0.06]',
                                default => '',
                            };

                            $callTypeInitial = $incident->patient_call_type ?: '—';
                        @endphp
                        <tr
                            wire:key="open-{{ $incident->id }}"
                            @if ($canDispatch)
                                wire:click="openDispatchModal({{ $incident->id }})"
                                class="cursor-pointer transition-colors {{ $riskTint }} hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10"
                            @else
                                class="transition-colors {{ $riskTint }}"
                            @endif
                        >
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="h-6 w-1 rounded-full {{ $riskBar }}" aria-hidden="true"></span>
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 bg-white/80 font-mono text-xs font-semibold text-slate-700 shadow-sm dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-slate-200">
                                        {{ $callTypeInitial }}
                                    </span>
                                    <span class="font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ $incident->talao }}/{{ $incident->dispatch_year }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">{{ $incident->occurred_at->format('d/m/Y H:i') }}</td>
                            <td class="max-w-[22rem] truncate px-4 py-3 text-slate-700 dark:text-slate-300">{{ $incident->address_line ?? '—' }}</td>
                            <td class="max-w-[18rem] truncate px-4 py-3 text-slate-700 dark:text-slate-300">{{ $incident->nature?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</flux:card>
