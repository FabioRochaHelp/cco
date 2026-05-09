<div class="cco-page-gap">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Nova ocorrência') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Cadastro conforme domínio legado (local, solicitante, natureza, tipo de chamada).') }}</flux:text>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" icon="rectangle-stack" :href="route('operations.incidents.index')" wire:navigate>{{ __('Lista') }}</flux:button>
            <flux:button variant="ghost" icon="radio" :href="route('operations.dispatch')" wire:navigate>{{ __('CCO') }}</flux:button>
        </div>
    </div>

    <flux:callout variant="info">
        {{ __('O cadastro não vincula a ocorrência a uma base (município). A base é definida no empenho, ao escolher o turno/viatura no CCO.') }}
    </flux:callout>

    @if ($errors->any())
        <flux:callout variant="danger">
            <ul class="mt-1 list-inside list-disc space-y-1 text-sm">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    <flux:card>
        <form wire:submit="save" class="grid gap-4 lg:grid-cols-2">
            <flux:input wire:model="occurred_at" type="datetime-local" :label="__('Data/hora da ocorrência')" />
            <flux:input wire:model="call_received_at" type="datetime-local" :label="__('Hora da chamada (opcional)')" />

            <flux:select wire:model="nature_id" :label="__('Natureza')" placeholder="{{ __('Selecione') }}" class="lg:col-span-2">
                @foreach ($natures as $n)
                    <flux:select.option value="{{ $n->id }}">{{ $n->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea wire:model="description" :label="__('Descrição')" rows="3" class="lg:col-span-2" />

            <flux:input wire:model="address_line" :label="__('Endereço')" />
            <flux:input wire:model="number" :label="__('Número')" />
            <flux:input wire:model="district" :label="__('Bairro')" />
            <flux:input wire:model="city" :label="__('Cidade')" />
            <flux:textarea wire:model="reference_notes" :label="__('Referência')" rows="2" class="lg:col-span-2" />

            <flux:input wire:model="caller_name" :label="__('Solicitante')" />
            <flux:input wire:model="caller_phone" :label="__('Telefone')" />

            <flux:input wire:model="patient_name" :label="__('Paciente (nome)')" />
            <flux:input wire:model.number="patient_age" type="number" :label="__('Idade')" />
            <flux:input wire:model="patient_sex" :label="__('Sexo')" />

            <flux:select wire:model="patient_call_type" :label="__('Tipo de chamada')">
                @foreach ($callTypes as $ct)
                    <flux:select.option value="{{ $ct->value }}">{{ $ct->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.number="expected_victim_total" type="number" :label="__('Total de vítimas (estimado)')" />

            <flux:input wire:model="latitude" :label="__('Latitude')" />
            <flux:input wire:model="longitude" :label="__('Longitude')" />

            <flux:input wire:model.number="total_death_count" type="number" :label="__('Óbitos (total)')" />

            <div class="flex items-center gap-2 lg:col-span-2">
                <flux:checkbox wire:model.boolean="is_qta" :label="__('QTA (sem atendimento / flag operacional)')" />
            </div>

            <div class="lg:col-span-2">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Registrar ocorrência') }}</flux:button>
            </div>
        </form>
    </flux:card>
</div>
