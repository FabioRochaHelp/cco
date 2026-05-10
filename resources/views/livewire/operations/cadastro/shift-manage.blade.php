<div class="cco-page-gap">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Turnos de serviço') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Abertura e gestão de turnos por viatura e efetivo, conforme docs/migracao (turno).') }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="truck" :href="route('operations.fleet')" wire:navigate>{{ __('Painel turnos/viaturas') }}</flux:button>
            <flux:button variant="ghost" icon="radio" :href="route('operations.dispatch')" wire:navigate>{{ __('CCO') }}</flux:button>
        </div>
    </div>

    @if (auth()->user()?->isOperationalCentral())
        <flux:card class="space-y-3">
            <flux:subheading>{{ __('Base (município)') }}</flux:subheading>
            <flux:text class="text-slate-600 dark:text-slate-400">{{ __('Operadores da central podem cadastrar turnos para qualquer base após selecioná-la. Operadores municipais ficam restritos à própria base.') }}</flux:text>
            <flux:select wire:model.live="selectedOperationalMunicipioId" :label="__('Base')" placeholder="{{ __('Selecione a base') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($operationalMunicipios as $municipio)
                    <flux:select.option value="{{ $municipio->id }}">{{ $municipio->razao_social }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>
    @else
        <flux:callout variant="info">{{ __('Turnos serão criados apenas para a sua base (município vinculado ao usuário).') }}</flux:callout>
    @endif

    @if ($scopeMunicipioId === null)
        <flux:callout variant="warning">{{ __('Selecione a base para listar viaturas, efetivo e turnos.') }}</flux:callout>
    @endif

    @error('scope')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @error('delete')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @error('vehicle_id')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @if ($message)
        <flux:callout variant="success">{{ $message }}</flux:callout>
    @endif

    @can('create', App\Models\Shift::class)
        <flux:card class="space-y-4">
            <flux:subheading>{{ $editingId ? __('Editar turno') : __('Novo turno') }}</flux:subheading>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <flux:select wire:model="vehicle_id" :label="__('Viatura')" placeholder="{{ __('Selecione') }}" class="md:col-span-2 lg:col-span-3">
                    <flux:select.option value="">{{ __('—') }}</flux:select.option>
                    @foreach ($vehicles as $v)
                        <flux:select.option value="{{ $v->id }}">{{ $v->prefix ?? __('Sem prefixo') }} · {{ $v->plate ?? __('Sem placa') }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="starts_at" type="datetime-local" :label="__('Início')" />
                <flux:input wire:model="ends_at" type="datetime-local" :label="__('Fim')" />
                <flux:select wire:model="status" :label="__('Estado operacional')">
                    @foreach ($statusCases as $st)
                        <flux:select.option value="{{ $st->value }}">{{ $st->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
                <div class="md:col-span-2 lg:col-span-3">
                    <flux:fieldset :legend="__('Efetivo no turno (opcional)')">
                        @forelse ($staffMembers as $s)
                            <flux:checkbox wire:model="staffIds" value="{{ $s->id }}" :label="$s->name" />
                        @empty
                            <flux:text size="sm" class="text-slate-500">{{ __('Nenhum efetivo cadastrado nesta base.') }}</flux:text>
                        @endforelse
                    </flux:fieldset>
                </div>
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
        <flux:subheading>{{ __('Turnos cadastrados') }}</flux:subheading>
        <div class="cco-table-shell">
            <table class="min-w-full divide-y divide-slate-200 text-start text-sm dark:divide-slate-800/80">
                <thead>
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('ID') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Viatura') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Estado') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Início') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Fim') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Equipe') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800/80">
                    @forelse ($shifts as $shift)
                        <tr wire:key="shift-row-{{ $shift->id }}">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400">#{{ $shift->id }}</td>
                            <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-100">
                                {{ $shift->vehicle?->prefix ?? '—' }} · {{ $shift->vehicle?->plate ?? '—' }}
                            </td>
                            <td class="px-4 py-3">{{ $shift->status->label() }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600 dark:text-slate-400">{{ $shift->starts_at->format('d/m/Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600 dark:text-slate-400">{{ $shift->ends_at->format('d/m/Y H:i') }}</td>
                            <td class="max-w-[14rem] px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                {{ $shift->staff->pluck('name')->implode(', ') ?: '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $shift)
                                        <x-crud-icon-edit :item-id="$shift->id" />
                                    @endcan
                                    @can('delete', $shift)
                                        <x-crud-icon-delete :item-id="$shift->id" :confirm-message="__('Excluir este turno?')" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500">{{ __('Nenhum turno neste escopo.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
