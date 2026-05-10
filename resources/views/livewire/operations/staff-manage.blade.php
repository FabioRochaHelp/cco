<div class="cco-page-gap">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Efetivo') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Equipe operacional (documentos, contato, cargo legado; 2 = médico prescrição na doc).') }}</flux:text>
        </div>
        <flux:button variant="ghost" icon="radio" :href="route('operations.dispatch')" wire:navigate>{{ __('CCO') }}</flux:button>
    </div>

    @if (auth()->user()?->isOperationalCentral())
        <flux:card class="space-y-3">
            <flux:subheading>{{ __('Base (município)') }}</flux:subheading>
            <flux:text class="text-zinc-600">{{ __('O efetivo é gravado com o `municipio_id` da base escolhida.') }}</flux:text>
            <flux:select wire:model.live="selectedOperationalMunicipioId" :label="__('Base')" placeholder="{{ __('Selecione a base') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($operationalMunicipios as $municipio)
                    <flux:select.option value="{{ $municipio->id }}">{{ $municipio->razao_social }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>
    @endif

    @if ($scopeMunicipioId === null)
        <flux:callout variant="warning">{{ __('Selecione a base para listar e cadastrar efetivo neste município.') }}</flux:callout>
    @endif

    @error('scope')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @if ($message)
        <flux:callout variant="success">{{ $message }}</flux:callout>
    @endif

    @can('create', App\Models\Staff::class)
        <flux:card class="space-y-4">
            <flux:subheading>{{ $editingId ? __('Editar registro') : __('Novo efetivo') }}</flux:subheading>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="name" :label="__('Nome')" class="md:col-span-2" />
                <flux:input wire:model.number="cargo" type="number" :label="__('Cargo (código legado)')" />
                <flux:input wire:model="document_type" :label="__('Tipo documento')" />
                <flux:input wire:model="document_number" :label="__('Nº documento')" />
                <flux:input wire:model="cpf" :label="__('CPF')" />
                <flux:input wire:model="email" type="email" :label="__('E-mail')" />
                <flux:input wire:model="phone" :label="__('Telefone')" />
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
        <flux:subheading>{{ __('Efetivo cadastrado') }}</flux:subheading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-start text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Nome') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Cargo') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('CPF') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Contato') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($staffMembers as $s)
                        <tr wire:key="s-{{ $s->id }}">
                            <td class="px-4 py-3 font-medium">{{ $s->name }}</td>
                            <td class="px-4 py-3 tabular-nums">{{ $s->cargo ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $s->cpf ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-600">{{ $s->phone ?? $s->email ?? '—' }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $s)
                                        <x-crud-icon-edit :item-id="$s->id" />
                                    @endcan
                                    @can('delete', $s)
                                        <x-crud-icon-delete :item-id="$s->id" :confirm-message="__('Excluir este registro?')" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('Nenhum registro neste escopo.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
