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
                        <th class="px-4 py-3 font-medium">{{ __('Endereço / cidade') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Descrição') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Base') }}</th>
                        <th class="px-4 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/95 dark:divide-slate-800/80">
                    @foreach ($openIncidents as $incident)
                        @php
                            $canDispatch = auth()->user()?->can('dispatchUnit', $incident);
                        @endphp
                        <tr
                            wire:key="open-{{ $incident->id }}"
                            @if ($canDispatch)
                                wire:click="openDispatchModal({{ $incident->id }})"
                                class="cursor-pointer transition-colors hover:bg-cyan-500/5 dark:hover:bg-cyan-500/10"
                            @else
                                class="transition-colors"
                            @endif
                        >
                            <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ $incident->talao }}/{{ $incident->dispatch_year }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-400">{{ $incident->occurred_at->format('d/m/Y H:i') }}</td>
                            <td class="max-w-[14rem] px-4 py-3 text-slate-700 dark:text-slate-300">
                                {{ $incident->address_line ?? '—' }}
                                @if ($incident->district || $incident->city)
                                    <span class="block text-xs text-slate-500">{{ trim(implode(' · ', array_filter([$incident->district, $incident->city]))) }}</span>
                                @endif
                            </td>
                            <td class="max-w-md truncate px-4 py-3 text-slate-700 dark:text-slate-300">{{ $incident->description ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ $incident->municipio?->razao_social ?? ('#'.$incident->municipio_id) }}</td>
                            <td class="px-4 py-3 text-end" wire:click.stop>
                                @can('view', $incident)
                                    <flux:button size="sm" variant="ghost" :href="route('operations.incidents.show', $incident)" wire:navigate>
                                        {{ __('Painel da ocorrência') }}
                                    </flux:button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</flux:card>
