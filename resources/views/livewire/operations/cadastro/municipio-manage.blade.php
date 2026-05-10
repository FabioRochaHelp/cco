<div class="cco-page-gap">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Bases') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Cadastro de bases operacionais (`municipios`). Efetivo e viaturas usam o `municipio_id` da base escolhida.') }}</flux:text>
        </div>
        <flux:button variant="ghost" icon="radio" :href="route('operations.dispatch')" wire:navigate>{{ __('CCO') }}</flux:button>
    </div>

    @error('delete')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    @if ($message)
        <flux:callout variant="success">{{ $message }}</flux:callout>
    @endif

    @can('create', App\Models\Municipio::class)
        <flux:card class="space-y-4">
            <flux:subheading>{{ $editingId ? __('Editar base') : __('Nova base') }}</flux:subheading>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <flux:input wire:model="razao_social" :label="__('Razão social')" class="md:col-span-2 lg:col-span-3" />
                <flux:input wire:model="cnpj" :label="__('CNPJ')" />
                <flux:input wire:model="ie" :label="__('IE')" />
                <flux:input wire:model="phone" :label="__('Telefone')" />
                <flux:input wire:model="zipcode" :label="__('CEP')" />
                <flux:input wire:model="address" :label="__('Endereço')" class="md:col-span-2" />
                <flux:input wire:model="number" :label="__('Número')" />
                <flux:input wire:model="district" :label="__('Bairro')" />
                <flux:input wire:model="city" :label="__('Cidade')" />
                <flux:input wire:model="state" :label="__('UF')" />
                <flux:switch wire:model="active" :label="__('Ativa')" align="left" class="md:col-span-2 lg:col-span-3" />
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
        <flux:subheading>{{ __('Bases cadastradas') }}</flux:subheading>
        <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-start text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('Razão social') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Cidade') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('CNPJ') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Ativa') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($bases as $b)
                        <tr wire:key="base-{{ $b->id }}">
                            <td class="px-4 py-3 font-medium">{{ $b->razao_social }}</td>
                            <td class="px-4 py-3 text-zinc-600">{{ $b->city ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $b->cnpj ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $b->active ? __('Sim') : __('Não') }}</td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-1">
                                    @can('update', $b)
                                        <x-crud-icon-edit :item-id="$b->id" />
                                    @endcan
                                    @can('delete', $b)
                                        <x-crud-icon-delete :item-id="$b->id" :confirm-message="__('Excluir esta base?')" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-zinc-500">{{ __('Nenhuma base cadastrada.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>
</div>
