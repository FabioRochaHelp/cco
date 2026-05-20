@php
    use App\Domain\Operations\Enums\IncidentStatus;
    use App\Models\Prescription;
    use App\Support\Operations\TimelineEventLabels;
@endphp

<div class="cco-page-gap" wire:poll.30s="refreshOperationalState">
    <div class="flex flex-wrap items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('operations.dispatch')" wire:navigate>{{ __('Voltar ao CCO') }}</flux:button>
        <flux:button variant="ghost" icon="rectangle-stack" :href="route('operations.incidents.index')" wire:navigate>{{ __('Lista de ocorrências') }}</flux:button>
    </div>

    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <flux:heading size="xl" class="tabular-nums">
                {{ __('Ocorrência') }} #{{ $incident->talao }}/{{ $incident->dispatch_year }}
            </flux:heading>
            <flux:text class="mt-1">{{ $incident->occurred_at->format('d/m/Y H:i:s') }}</flux:text>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-2">
            <x-incident.status-badge :status="$incident->status" size="lg" />
            <x-incident.manchester-badge :risk="$incident->manchester_risk" size="lg" />
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <flux:card class="space-y-2 lg:col-span-2">
            <flux:subheading>{{ __('Endereço e referência') }}</flux:subheading>
            <flux:text>{{ trim(implode(', ', array_filter([$incident->address_line, $incident->number, $incident->district, $incident->city]))) ?: '—' }}</flux:text>
            @if ($incident->reference_notes)
                <flux:text size="sm" class="text-zinc-600 dark:text-zinc-400">{{ $incident->reference_notes }}</flux:text>
            @endif
            @if ($incident->latitude && $incident->longitude)
                <flux:text size="sm" class="font-mono text-zinc-500">{{ $incident->latitude }}, {{ $incident->longitude }}</flux:text>
            @endif
        </flux:card>
        <flux:card class="space-y-2">
            <flux:subheading>{{ __('Natureza e solicitante') }}</flux:subheading>
            <flux:text>{{ $incident->nature?->name ?? '—' }}</flux:text>
            <flux:text size="sm">{{ $incident->caller_name ?? '—' }} · {{ $incident->caller_phone ?? '—' }}</flux:text>
        </flux:card>
    </div>

    @if ($incident->description)
        <flux:card>
            <flux:subheading>{{ __('Descrição') }}</flux:subheading>
            <flux:text>{{ $incident->description }}</flux:text>
        </flux:card>
    @endif

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:subheading>{{ __('Vítimas') }} ({{ $incident->victims->count() }})</flux:subheading>
            @can('recordVictim', $incident)
                <flux:button size="sm" variant="primary" icon="user-plus" :href="route('operations.incidents.victims.create', $incident)" wire:navigate>
                    {{ __('Registrar vítima') }}
                </flux:button>
            @endcan
        </div>
        @if ($incident->victims->isEmpty())
            <flux:text size="sm" class="text-zinc-500">{{ __('Nenhuma vítima registrada.') }}</flux:text>
        @else
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($incident->victims as $v)
                    <li wire:key="vic-{{ $v->id }}" class="flex flex-wrap items-center justify-between gap-2 py-3">
                        <div>
                            <flux:text class="font-medium">{{ $v->name ?: __('Sem nome') }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ __('Situação') }}:
                                @if ((int) $v->situacao === 1)
                                    {{ __('Atendida') }}
                                @elseif ((int) $v->situacao === 3)
                                    {{ __('Recusa') }}
                                @else
                                    —
                                @endif
                                @if ($v->age)
                                    · {{ $v->age }} {{ __('anos') }}
                                @endif
                            </flux:text>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @can('create', [Prescription::class, $v])
                                <flux:button size="sm" variant="ghost" :href="route('operations.victims.prescriptions.create', $v)" wire:navigate>
                                    {{ __('Prescrever') }}
                                </flux:button>
                            @endcan
                            @can('update', $v)
                                <flux:button size="sm" variant="ghost" :href="route('operations.incidents.victims.edit', [$incident, $v])" wire:navigate>
                                    {{ __('Editar') }}
                                </flux:button>
                            @endcan
                        </div>
                        @if ($v->prescriptions->isNotEmpty())
                            <div class="basis-full ps-0 md:ps-4">
                                <ul class="mt-2 space-y-1">
                                    @foreach ($v->prescriptions as $prescription)
                                        <li wire:key="prescription-{{ $prescription->id }}" class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                            <span>{{ __('Prescrição #:id', ['id' => $prescription->id]) }}</span>
                                            <flux:badge size="sm" color="{{ $prescription->status->value === 'approved' ? 'green' : 'amber' }}">{{ $prescription->status->label() }}</flux:badge>
                                            <a class="text-blue-600 hover:underline dark:text-blue-400" href="{{ route('operations.prescriptions.approval', $prescription) }}" wire:navigate>
                                                {{ __('ver validação') }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </flux:card>

    @if ($activeDispatch)
        <flux:card class="border-s-4 border-s-blue-500 dark:border-s-blue-400">
            <flux:subheading>{{ __('Despacho ativo') }}</flux:subheading>
            <div class="mt-2 flex flex-wrap gap-4">
                <flux:text>
                    <span class="font-medium">{{ __('Etapa') }}:</span>
                    {{ $activeDispatch->stage->label() }}
                </flux:text>
                @if ($activeDispatch->shift?->vehicle)
                    <flux:text>
                        <span class="font-medium">{{ __('Viatura') }}:</span>
                        {{ $activeDispatch->shift->vehicle->prefix }} · {{ $activeDispatch->shift->vehicle->plate ?? __('Sem placa') }}
                    </flux:text>
                @endif
            </div>
            @if ($activeDispatch->shift?->vehicle?->device_id)
                <flux:text size="sm" class="mt-2 font-mono text-zinc-500">Device ID: {{ $activeDispatch->shift->vehicle->device_id }}</flux:text>
            @endif
        </flux:card>
    @endif

    @can('fillNurseReport', $incident)
        <flux:card class="border-s-4 border-s-teal-500 dark:border-s-teal-400">
            <flux:subheading>{{ __('Relatório de enfermagem') }}</flux:subheading>
            @if ($incident->nurseReport)
                <flux:text size="sm" class="mt-2 text-zinc-600 dark:text-zinc-400">
                    {{ __('Registrado por :nome em :data.', [
                        'nome' => $incident->nurseReport->filledBy?->name ?? '—',
                        'data' => $incident->nurseReport->submitted_at->format('d/m/Y H:i'),
                    ]) }}
                </flux:text>
                <div class="mt-4 flex flex-wrap gap-2">
                    <flux:button variant="ghost" size="sm" icon="document-text" :href="route('operations.incidents.nurse-report', $incident)" wire:navigate>
                        {{ __('Editar relatório') }}
                    </flux:button>
                </div>
            @else
                @if ($incident->status === IncidentStatus::PendingNurseReport)
                    <flux:callout variant="warning" class="mt-3">
                        {{ __('A unidade retornou à base. A ocorrência permanece pendente até o envio deste relatório.') }}
                    </flux:callout>
                @else
                    <flux:callout variant="warning" class="mt-3">
                        {{ __('Complete o relatório assistencial desta ocorrência.') }}
                    </flux:callout>
                @endif
                <div class="mt-4">
                    <flux:button variant="primary" size="sm" icon="document-plus" :href="route('operations.incidents.nurse-report', $incident)" wire:navigate>
                        {{ __('Preencher relatório') }}
                    </flux:button>
                </div>
            @endif
        </flux:card>
    @endcan

    <flux:card>
        <flux:subheading class="mb-4">{{ __('Marcos horários operacionais') }}</flux:subheading>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ([
                ['label' => __('Empenho'), 'value' => $incident->dispatched_at],
                ['label' => __('Saída da base (QTI)'), 'value' => $incident->departed_base_at],
                ['label' => __('Chegada ao local'), 'value' => $incident->arrived_scene_at],
                ['label' => __('Saída do local'), 'value' => $incident->left_scene_at],
                ['label' => __('Chegada na US'), 'value' => $incident->arrived_hospital_at],
                ['label' => __('Saída da US'), 'value' => $incident->released_hospital_at],
                ['label' => __('Retorno à base'), 'value' => $incident->returned_base_at],
            ] as $row)
                <div class="rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                    <flux:text size="sm" class="text-zinc-500">{{ $row['label'] }}</flux:text>
                    <flux:text class="tabular-nums">{{ $row['value']?->format('d/m H:i:s') ?? '—' }}</flux:text>
                </div>
            @endforeach
        </div>
    </flux:card>

    <div class="grid gap-4 xl:grid-cols-2">
        <flux:card class="relative flex min-h-[16rem] flex-col justify-center">
            <flux:subheading class="mb-2">{{ __('Mapa da ocorrência') }}</flux:subheading>
            <flux:text size="sm" class="mb-4 text-zinc-600 dark:text-zinc-400">
                {{ __('Rota e markers — Leaflet + Traccar quando device_id e intervalo estiverem completos.') }}
            </flux:text>
            <div
                class="flex flex-1 flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50/50 dark:border-zinc-600 dark:bg-zinc-900/30"
            >
                <flux:icon.map class="size-10 text-zinc-400" />
                <flux:text class="mt-2 text-zinc-500">{{ __('Camada de mapa') }}</flux:text>
            </div>
        </flux:card>

        <flux:card>
            <flux:subheading class="mb-4">{{ __('Timeline auditável') }}</flux:subheading>
            @if ($incident->timelineEvents->isEmpty())
                <flux:text size="sm">{{ __('Sem eventos.') }}</flux:text>
            @else
                <ul class="max-h-[28rem] space-y-4 overflow-y-auto pe-1">
                    @foreach ($incident->timelineEvents as $event)
                        <li wire:key="det-tl-{{ $event->id }}" class="border-s-2 border-zinc-300 ps-3 dark:border-zinc-600">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <flux:text class="font-medium">{{ TimelineEventLabels::for($event->event_key) }}</flux:text>
                                <flux:text size="sm" class="tabular-nums text-zinc-500">{{ $event->recorded_at->format('d/m/Y H:i:s') }}</flux:text>
                            </div>
                            @if ($event->payload)
                                <pre class="mt-2 max-h-32 overflow-auto rounded-lg bg-zinc-100 p-2 font-mono text-xs dark:bg-zinc-900">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @endif
                            @if ($event->actor)
                                <flux:text size="sm" class="text-zinc-500">{{ __('Operador') }}: {{ $event->actor->name }}</flux:text>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </flux:card>
    </div>
</div>
