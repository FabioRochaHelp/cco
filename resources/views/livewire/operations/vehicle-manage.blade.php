<div class="cco-page-gap">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Viaturas') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Cadastro de unidades móveis (prefixo, placa, integração Traccar).') }}</flux:text>
        </div>
        <flux:button variant="ghost" icon="radio" :href="route('operations.dispatch')" wire:navigate>{{ __('CCO') }}</flux:button>
    </div>

    @if (auth()->user()?->isOperationalCentral())
        <flux:card class="space-y-3">
            <flux:subheading>{{ __('Base (município)') }}</flux:subheading>
            <flux:text class="text-zinc-600">{{ __('Viaturas são gravadas com o `municipio_id` da base escolhida. O mesmo valor é usado na central operacional quando definido aqui.') }}</flux:text>
            <flux:select wire:model.live="selectedOperationalMunicipioId" :label="__('Base')" placeholder="{{ __('Selecione a base') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($operationalMunicipios as $municipio)
                    <flux:select.option value="{{ $municipio->id }}">{{ $municipio->razao_social }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>
    @endif

    @if ($scopeMunicipioId === null)
        <flux:callout variant="warning">{{ __('Selecione a base para listar e cadastrar viaturas neste município.') }}</flux:callout>
    @endif

    @error('scope')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @error('delete')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @if ($message)
        <flux:callout variant="success">{{ $message }}</flux:callout>
    @endif

    @can('create', App\Models\Vehicle::class)
        <flux:card class="space-y-4">
            <flux:subheading>{{ $editingId ? __('Editar viatura') : __('Nova viatura') }}</flux:subheading>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="prefix" :label="__('Prefixo')" />
                <flux:input wire:model="plate" :label="__('Placa')" />
                <flux:input wire:model="make" :label="__('Marca')" />
                <flux:input wire:model="model" :label="__('Modelo')" />
                <flux:input wire:model.number="year" type="number" :label="__('Ano')" />

                @if ($traccarAvailable && $traccarDevices->isNotEmpty())
                    <flux:select wire:model="device_id" :label="__('Device Traccar')" placeholder="{{ __('Nenhum') }}">
                        <flux:select.option value="">{{ __('— sem vínculo —') }}</flux:select.option>
                        @foreach ($traccarDevices as $device)
                            <flux:select.option value="{{ $device['id'] }}">
                                {{ $device['name'] }} ({{ $device['uniqueId'] }})
                                @if ($device['status'] === 'online') ● @endif
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                @else
                    <flux:input wire:model="device_id" :label="__('Device ID (Traccar)')"
                        :description="$traccarAvailable ? __('Nenhum device encontrado no Traccar.') : __('Traccar indisponível — insira o ID manualmente.')" />
                @endif

                <flux:input wire:model.number="status_legacy" type="number" :label="__('Status legado (opcional)')" />
                <div class="flex flex-wrap gap-2 md:col-span-2 lg:col-span-3">
                    <flux:button type="submit" variant="primary">{{ $editingId ? __('Salvar') : __('Incluir') }}</flux:button>
                    @if ($editingId)
                        <flux:button type="button" variant="ghost" wire:click="resetForm">{{ __('Cancelar') }}</flux:button>
                    @endif
                </div>
            </form>
        </flux:card>
    @endcan

    <flux:card class="space-y-4">
        <flux:subheading>{{ __('Viaturas cadastradas') }}</flux:subheading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-start text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Prefixo') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Placa') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Marca / modelo') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Device') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($vehicles as $v)
                        <tr wire:key="v-{{ $v->id }}">
                            <td class="px-4 py-3 font-medium">{{ $v->prefix ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $v->plate ?? '—' }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ trim(implode(' ', array_filter([$v->make, $v->model]))) ?: '—' }}</td>
                            <td class="px-4 py-3 text-xs text-zinc-500">
                            @if ($v->device_id)
                                @php
                                    $dev = $traccarDevices->firstWhere('id', (int) $v->device_id);
                                @endphp
                                @if ($dev)
                                    <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $dev['name'] }}</span>
                                    <span class="ml-1 font-mono text-zinc-400">#{{ $dev['uniqueId'] }}</span>
                                    @if ($dev['status'] === 'online')
                                        <span class="ml-1 inline-block h-2 w-2 rounded-full bg-green-500" title="{{ __('Online') }}"></span>
                                    @else
                                        <span class="ml-1 inline-block h-2 w-2 rounded-full bg-zinc-400" title="{{ $dev['status'] }}"></span>
                                    @endif
                                @else
                                    <span class="font-mono">{{ $v->device_id }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $v)
                                        <x-crud-icon-edit :item-id="$v->id" />
                                    @endcan
                                    @can('delete', $v)
                                        <x-crud-icon-delete :item-id="$v->id" :confirm-message="__('Excluir esta viatura?')" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('Nenhuma viatura neste escopo.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
