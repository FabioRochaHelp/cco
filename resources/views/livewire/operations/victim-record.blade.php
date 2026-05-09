<div class="cco-page-gap">
    <div class="flex flex-wrap items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('operations.incidents.show', $incident)" wire:navigate>
            {{ __('Voltar à ocorrência') }}
        </flux:button>
    </div>

    <div>
        <flux:heading size="xl">{{ $victimModel ? __('Editar vítima') : __('Nova vítima') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-600 dark:text-zinc-400">
            {{ __('Talão :talao/:ano — registro clínico (vitima + procedimentos, acessórios, ferimentos e sinais vitais).', ['talao' => $incident->talao, 'ano' => $incident->dispatch_year]) }}
        </flux:text>
    </div>

    @error('save')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    <form wire:submit="save" class="grid gap-6">
        <flux:card class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <flux:subheading class="md:col-span-2 lg:col-span-3">{{ __('Identificação e classificação') }}</flux:subheading>

            <flux:input wire:model="name" :label="__('Nome')" class="md:col-span-2" />

            <flux:select wire:model="sex" :label="__('Sexo')" placeholder="{{ __('Opcional') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Masculino') }}</flux:select.option>
                <flux:select.option value="2">{{ __('Feminino') }}</flux:select.option>
                <flux:select.option value="3">{{ __('Outro') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="age" type="number" :label="__('Idade')" />
            <flux:input wire:model="rg" :label="__('RG')" />
            <flux:input wire:model="ssp" :label="__('SSP / órgão emissor')" />

            <flux:select wire:model="victim_type_id" :label="__('Tipo de vítima')" placeholder="{{ __('Opcional') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($victimTypes as $vt)
                    <flux:select.option value="{{ $vt->id }}">{{ $vt->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="care_local_id" :label="__('Local auxiliar (cadastro)')" placeholder="{{ __('Opcional') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach ($careLocals as $cl)
                    <flux:select.option value="{{ $cl->id }}">{{ $cl->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="situacao" :label="__('Situação')" placeholder="{{ __('Obrigatório') }}">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Atendida') }}</flux:select.option>
                <flux:select.option value="3">{{ __('Recusa de atendimento') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="status" type="number" :label="__('Status (legado numérico)')" placeholder="{{ __('Opcional') }}" />
        </flux:card>

        <flux:card class="grid gap-4 md:grid-cols-2">
            <flux:subheading class="md:col-span-2">{{ __('Destino e US') }}</flux:subheading>
            <flux:input wire:model="hospital" :label="__('Hospital')" />
            <flux:input wire:model="transporte" :label="__('Transporte')" />
            <flux:input wire:model="unidade_saude" :label="__('Unidade de saúde')" class="md:col-span-2" />
            <flux:input wire:model="medico_us" :label="__('Médico na US')" />
            <flux:input wire:model="crm_medico_us" :label="__('CRM médico US')" />
        </flux:card>

        <flux:card class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <flux:subheading class="md:col-span-2 lg:col-span-3">{{ __('Condições / trauma (legado vitima)') }}</flux:subheading>

            <flux:select wire:model="fall_height" :label="__('Queda de altura')">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Sim') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Não') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model="halito_etilico" :label="__('Hálito etílico')">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Sim') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Não') }}</flux:select.option>
            </flux:select>
            <flux:select wire:model="burn" :label="__('Queimadura')">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                <flux:select.option value="1">{{ __('Sim') }}</flux:select.option>
                <flux:select.option value="0">{{ __('Não') }}</flux:select.option>
            </flux:select>
            <flux:input wire:model="vehicle_role" :label="__('Papel no veículo')" placeholder="{{ __('Ex.: condutor, passageiro') }}" />
            <flux:input wire:model="accident_type" :label="__('Tipo de acidente')" class="md:col-span-2" />
            <flux:textarea wire:model="pupil_notes" :label="__('Pupilas / observações')" rows="2" class="md:col-span-2 lg:col-span-3" />
        </flux:card>

        <flux:card class="grid gap-4 md:grid-cols-2">
            <flux:subheading class="md:col-span-2">{{ __('Recusa / óbito (quando aplicável)') }}</flux:subheading>
            <flux:input wire:model="witness_name" :label="__('Testemunha')" />
            <flux:input wire:model="witness_rg" :label="__('RG testemunha')" />
            <flux:input wire:model="witness_ssp" :label="__('SSP testemunha')" />
            <flux:input wire:model="death_where" :label="__('Óbito — onde')" />
            <flux:textarea wire:model="death_notes" :label="__('Óbito — parecer / notas')" rows="3" class="md:col-span-2" />
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Procedimentos (vitima_has_procedimento)') }}</flux:subheading>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($procedures as $p)
                    <flux:checkbox wire:model="procedure_ids" value="{{ $p->id }}" :label="$p->name" />
                @empty
                    <flux:text size="sm">{{ __('Cadastre procedimentos em Parâmetros.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Acessórios (vitima_has_acessorio)') }}</flux:subheading>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($accessories as $a)
                    <flux:checkbox wire:model="accessory_ids" value="{{ $a->id }}" :label="$a->name" />
                @empty
                    <flux:text size="sm">{{ __('Cadastre acessórios em Parâmetros.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <flux:subheading>{{ __('Locais de ferimento (vitima_has_ferimento)') }}</flux:subheading>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($injurySites as $s)
                    <flux:checkbox wire:model="injury_site_ids" value="{{ $s->id }}" :label="$s->name" />
                @empty
                    <flux:text size="sm">{{ __('Cadastre locais de ferimento em Parâmetros.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <flux:subheading>{{ __('Sinais vitais seriados (vitima_has_sinais)') }}</flux:subheading>
                <flux:button type="button" size="sm" variant="ghost" wire:click="addVitalRow" icon="plus">{{ __('Adicionar linha') }}</flux:button>
            </div>
            @foreach ($vital_rows as $idx => $row)
                <div wire:key="vit-{{ $idx }}" class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <flux:text size="sm" class="font-medium">{{ __('Medição :n', ['n' => $idx + 1]) }}</flux:text>
                        @if (count($vital_rows) > 1)
                            <flux:button type="button" size="sm" variant="ghost" wire:click="removeVitalRow({{ $idx }})">{{ __('Remover') }}</flux:button>
                        @endif
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <flux:input wire:model="vital_rows.{{ $idx }}.recorded_at" type="datetime-local" :label="__('Data/hora')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.blood_pressure_systolic" type="number" :label="__('PAS')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.blood_pressure_diastolic" type="number" :label="__('PAD')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.heart_rate" type="number" :label="__('FC (bpm)')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.respiratory_rate" type="number" :label="__('FR')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.spo2" type="number" :label="__('SpO₂ %')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.temperature" type="number" step="0.1" :label="__('Temp. °C')" />
                        <flux:input wire:model="vital_rows.{{ $idx }}.glasgow_total" type="number" :label="__('Glasgow (3–15)')" />
                        <flux:select wire:model="vital_rows.{{ $idx }}.dominant_side" :label="__('Lateralidade')">
                            <flux:select.option value="">{{ __('—') }}</flux:select.option>
                            <flux:select.option value="L">{{ __('Esquerdo') }}</flux:select.option>
                            <flux:select.option value="R">{{ __('Direito') }}</flux:select.option>
                        </flux:select>
                        <flux:textarea wire:model="vital_rows.{{ $idx }}.neurological_notes" :label="__('Neurológico')" rows="2" class="sm:col-span-2 lg:col-span-4" />
                    </div>
                </div>
            @endforeach
        </flux:card>

        <flux:card>
            <flux:textarea wire:model="dados_complementares" :label="__('Dados complementares')" rows="4" />
        </flux:card>

        <div class="flex flex-wrap gap-2">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">{{ __('Salvar') }}</flux:button>
            <flux:button variant="ghost" type="button" :href="route('operations.incidents.show', $incident)" wire:navigate>{{ __('Cancelar') }}</flux:button>
        </div>
    </form>
</div>
