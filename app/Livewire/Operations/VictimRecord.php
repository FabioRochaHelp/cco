<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Domain\Operations\Actions\SaveVictimRecordAction;
use App\Models\Accessory;
use App\Models\CareLocal;
use App\Models\Incident;
use App\Models\InjurySite;
use App\Models\Procedure;
use App\Models\User;
use App\Models\Victim;
use App\Models\VictimType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário de vítima alinhado a vitima + vitima_has_* (docs/migracao/banco-dados.md).
 */
#[Layout('layouts.app')]
#[Title('Registro de vítima')]
final class VictimRecord extends Component
{
    public Incident $incident;

    public ?Victim $victimModel = null;

    public string $name = '';

    /** @var numeric-string|'' */
    public string $sex = '';

    public string $rg = '';

    /** @var numeric-string|'' */
    public string $age = '';

    public string $ssp = '';

    /** @var numeric-string|'' situacao 1 ou 3 */
    public string $situacao = '';

    /** @var numeric-string|'' */
    public string $status = '';

    public string $hospital = '';

    public string $transporte = '';

    public string $unidade_saude = '';

    public string $medico_us = '';

    public string $crm_medico_us = '';

    public string $dados_complementares = '';

    /** @var numeric-string|'' */
    public string $victim_type_id = '';

    /** @var numeric-string|'' */
    public string $care_local_id = '';

    /** '' | '1' | '0' */
    public string $fall_height = '';

    public string $halito_etilico = '';

    public string $burn = '';

    public string $vehicle_role = '';

    public string $accident_type = '';

    public string $pupil_notes = '';

    public string $witness_name = '';

    public string $witness_rg = '';

    public string $witness_ssp = '';

    public string $death_where = '';

    public string $death_notes = '';

    /** @var list<int|string> */
    public array $procedure_ids = [];

    /** @var list<int|string> */
    public array $accessory_ids = [];

    /** @var list<int|string> */
    public array $injury_site_ids = [];

    /** @var list<array<string, mixed>> */
    public array $vital_rows = [];

    public function mount(Incident $incident, ?Victim $victim = null): void
    {
        Gate::authorize('view', $incident);

        $this->incident = $incident;

        if ($victim !== null) {
            abort_unless((int) $victim->incident_id === (int) $incident->id, 404);
            Gate::authorize('update', $victim);
            $this->victimModel = $victim->load(['procedures', 'accessories', 'injurySites', 'vitalSigns']);
            $this->hydrateFromVictim($this->victimModel);
        } else {
            Gate::authorize('recordVictim', $incident);
            $this->vital_rows = [$this->emptyVitalRow()];
        }
    }

    /** @return array<string, mixed> */
    private function emptyVitalRow(): array
    {
        return [
            'recorded_at' => now()->format('Y-m-d\TH:i'),
            'blood_pressure_systolic' => '',
            'blood_pressure_diastolic' => '',
            'heart_rate' => '',
            'respiratory_rate' => '',
            'spo2' => '',
            'temperature' => '',
            'glasgow_total' => '',
            'neurological_notes' => '',
            'dominant_side' => '',
        ];
    }

    private function hydrateFromVictim(Victim $v): void
    {
        $this->name = (string) ($v->name ?? '');
        $this->sex = $v->sex !== null ? (string) $v->sex : '';
        $this->rg = (string) ($v->rg ?? '');
        $this->age = $v->age !== null ? (string) $v->age : '';
        $this->ssp = (string) ($v->ssp ?? '');
        $this->situacao = $v->situacao !== null ? (string) $v->situacao : '';
        $this->status = $v->status !== null ? (string) $v->status : '';
        $this->hospital = (string) ($v->hospital ?? '');
        $this->transporte = (string) ($v->transporte ?? '');
        $this->unidade_saude = (string) ($v->unidade_saude ?? '');
        $this->medico_us = (string) ($v->medico_us ?? '');
        $this->crm_medico_us = (string) ($v->crm_medico_us ?? '');
        $this->dados_complementares = (string) ($v->dados_complementares ?? '');
        $this->victim_type_id = $v->victim_type_id !== null ? (string) $v->victim_type_id : '';
        $this->care_local_id = $v->care_local_id !== null ? (string) $v->care_local_id : '';
        $this->fall_height = $this->boolToTriState($v->fall_height);
        $this->halito_etilico = $this->boolToTriState($v->halito_etilico);
        $this->burn = $this->boolToTriState($v->burn);
        $this->vehicle_role = (string) ($v->vehicle_role ?? '');
        $this->accident_type = (string) ($v->accident_type ?? '');
        $this->pupil_notes = (string) ($v->pupil_notes ?? '');
        $this->witness_name = (string) ($v->witness_name ?? '');
        $this->witness_rg = (string) ($v->witness_rg ?? '');
        $this->witness_ssp = (string) ($v->witness_ssp ?? '');
        $this->death_where = (string) ($v->death_where ?? '');
        $this->death_notes = (string) ($v->death_notes ?? '');

        $this->procedure_ids = $v->procedures->modelKeys();
        $this->accessory_ids = $v->accessories->modelKeys();
        $this->injury_site_ids = $v->injurySites->modelKeys();

        $this->vital_rows = $v->vitalSigns->map(function ($vs): array {
            return [
                'recorded_at' => $vs->recorded_at->format('Y-m-d\TH:i'),
                'blood_pressure_systolic' => $vs->blood_pressure_systolic !== null ? (string) $vs->blood_pressure_systolic : '',
                'blood_pressure_diastolic' => $vs->blood_pressure_diastolic !== null ? (string) $vs->blood_pressure_diastolic : '',
                'heart_rate' => $vs->heart_rate !== null ? (string) $vs->heart_rate : '',
                'respiratory_rate' => $vs->respiratory_rate !== null ? (string) $vs->respiratory_rate : '',
                'spo2' => $vs->spo2 !== null ? (string) $vs->spo2 : '',
                'temperature' => $vs->temperature !== null ? (string) $vs->temperature : '',
                'glasgow_total' => $vs->glasgow_total !== null ? (string) $vs->glasgow_total : '',
                'neurological_notes' => (string) ($vs->neurological_notes ?? ''),
                'dominant_side' => (string) ($vs->dominant_side ?? ''),
            ];
        })->values()->all();

        if ($this->vital_rows === []) {
            $this->vital_rows = [$this->emptyVitalRow()];
        }
    }

    private function boolToTriState(?bool $v): string
    {
        if ($v === null) {
            return '';
        }

        return $v ? '1' : '0';
    }

    public function addVitalRow(): void
    {
        $this->vital_rows[] = $this->emptyVitalRow();
    }

    public function removeVitalRow(int $index): void
    {
        unset($this->vital_rows[$index]);
        $this->vital_rows = array_values($this->vital_rows);
        if ($this->vital_rows === []) {
            $this->vital_rows = [$this->emptyVitalRow()];
        }
    }

    public function save(SaveVictimRecordAction $action): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        Gate::authorize('view', $this->incident);

        if ($this->victimModel !== null) {
            Gate::authorize('update', $this->victimModel);
        } else {
            Gate::authorize('recordVictim', $this->incident);
        }

        $payload = $this->validationPayload();

        $validated = Validator::make($payload, [
            'name' => ['nullable', 'string', 'max:255'],
            'sex' => ['nullable', Rule::in(['1', '2', '3'])],
            'rg' => ['nullable', 'string', 'max:64'],
            'age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'ssp' => ['nullable', 'string', 'max:64'],
            'situacao' => ['required', Rule::in(['1', '3'])],
            'status' => ['nullable', 'integer', 'min:0', 'max:255'],
            'hospital' => ['nullable', 'string', 'max:255'],
            'transporte' => ['nullable', 'string', 'max:255'],
            'unidade_saude' => ['nullable', 'string', 'max:255'],
            'medico_us' => ['nullable', 'string', 'max:255'],
            'crm_medico_us' => ['nullable', 'string', 'max:64'],
            'dados_complementares' => ['nullable', 'string', 'max:10000'],
            'victim_type_id' => ['nullable', 'integer', 'exists:victim_types,id'],
            'care_local_id' => ['nullable', 'integer', 'exists:care_locals,id'],
            'fall_height' => ['nullable', Rule::in(['', '0', '1'])],
            'halito_etilico' => ['nullable', Rule::in(['', '0', '1'])],
            'burn' => ['nullable', Rule::in(['', '0', '1'])],
            'vehicle_role' => ['nullable', 'string', 'max:64'],
            'accident_type' => ['nullable', 'string', 'max:128'],
            'pupil_notes' => ['nullable', 'string', 'max:2000'],
            'witness_name' => ['nullable', 'string', 'max:255'],
            'witness_rg' => ['nullable', 'string', 'max:64'],
            'witness_ssp' => ['nullable', 'string', 'max:64'],
            'death_where' => ['nullable', 'string', 'max:255'],
            'death_notes' => ['nullable', 'string', 'max:5000'],
            'procedure_ids' => ['array'],
            'procedure_ids.*' => ['integer', 'exists:procedures,id'],
            'accessory_ids' => ['array'],
            'accessory_ids.*' => ['integer', 'exists:accessories,id'],
            'injury_site_ids' => ['array'],
            'injury_site_ids.*' => ['integer', 'exists:injury_sites,id'],
            'vital_rows' => ['array'],
            'vital_rows.*.recorded_at' => ['nullable', 'date'],
            'vital_rows.*.blood_pressure_systolic' => ['nullable', 'integer', 'min:0', 'max:400'],
            'vital_rows.*.blood_pressure_diastolic' => ['nullable', 'integer', 'min:0', 'max:400'],
            'vital_rows.*.heart_rate' => ['nullable', 'integer', 'min:0', 'max:400'],
            'vital_rows.*.respiratory_rate' => ['nullable', 'integer', 'min:0', 'max:200'],
            'vital_rows.*.spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'vital_rows.*.temperature' => ['nullable', 'numeric', 'between:30,45'],
            'vital_rows.*.glasgow_total' => ['nullable', 'integer', 'min:3', 'max:15'],
            'vital_rows.*.neurological_notes' => ['nullable', 'string', 'max:2000'],
            'vital_rows.*.dominant_side' => ['nullable', Rule::in(['', 'L', 'R'])],
        ], [], [
            'situacao' => __('Situação'),
        ])->validate();

        $attributes = [
            'name' => $validated['name'] ?: null,
            'sex' => isset($validated['sex']) && $validated['sex'] !== '' ? (int) $validated['sex'] : null,
            'rg' => $validated['rg'] ?: null,
            'age' => isset($validated['age']) ? (int) $validated['age'] : null,
            'ssp' => $validated['ssp'] ?: null,
            'situacao' => (int) $validated['situacao'],
            'status' => isset($validated['status']) ? (int) $validated['status'] : null,
            'hospital' => $validated['hospital'] ?: null,
            'transporte' => $validated['transporte'] ?: null,
            'unidade_saude' => $validated['unidade_saude'] ?: null,
            'medico_us' => $validated['medico_us'] ?: null,
            'crm_medico_us' => $validated['crm_medico_us'] ?: null,
            'dados_complementares' => $validated['dados_complementares'] ?: null,
            'victim_type_id' => $validated['victim_type_id'] ?? null,
            'care_local_id' => $validated['care_local_id'] ?? null,
            'fall_height' => $this->triStateToBool($validated['fall_height'] ?? ''),
            'halito_etilico' => $this->triStateToBool($validated['halito_etilico'] ?? ''),
            'burn' => $this->triStateToBool($validated['burn'] ?? ''),
            'vehicle_role' => $validated['vehicle_role'] ?: null,
            'accident_type' => $validated['accident_type'] ?: null,
            'pupil_notes' => $validated['pupil_notes'] ?: null,
            'witness_name' => $validated['witness_name'] ?: null,
            'witness_rg' => $validated['witness_rg'] ?: null,
            'witness_ssp' => $validated['witness_ssp'] ?: null,
            'death_where' => $validated['death_where'] ?: null,
            'death_notes' => $validated['death_notes'] ?: null,
        ];

        $vitalFiltered = [];
        foreach ($validated['vital_rows'] ?? [] as $row) {
            if (! empty($row['recorded_at'])) {
                $vitalFiltered[] = $row;
            }
        }

        try {
            $action->execute(
                $this->incident->fresh(),
                $user,
                $this->victimModel,
                $attributes,
                array_map('intval', $validated['procedure_ids'] ?? []),
                array_map('intval', $validated['accessory_ids'] ?? []),
                array_map('intval', $validated['injury_site_ids'] ?? []),
                $vitalFiltered,
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('save', __('Não foi possível salvar o registro da vítima.'));

            return;
        }

        $this->redirect(route('operations.incidents.show', $this->incident), navigate: true);
    }

    /** @return array<string, mixed> */
    private function validationPayload(): array
    {
        $normEmptyInt = fn (?string $v): ?int => ($v === null || $v === '') ? null : (int) $v;

        $vitalNormalized = [];
        foreach ($this->vital_rows as $row) {
            $vitalNormalized[] = [
                'recorded_at' => ($row['recorded_at'] ?? '') === '' ? null : $row['recorded_at'],
                'blood_pressure_systolic' => $normEmptyInt($row['blood_pressure_systolic'] ?? null),
                'blood_pressure_diastolic' => $normEmptyInt($row['blood_pressure_diastolic'] ?? null),
                'heart_rate' => $normEmptyInt($row['heart_rate'] ?? null),
                'respiratory_rate' => $normEmptyInt($row['respiratory_rate'] ?? null),
                'spo2' => $normEmptyInt($row['spo2'] ?? null),
                'temperature' => ($row['temperature'] ?? '') === '' ? null : $row['temperature'],
                'glasgow_total' => $normEmptyInt($row['glasgow_total'] ?? null),
                'neurological_notes' => $row['neurological_notes'] ?? '',
                'dominant_side' => $row['dominant_side'] ?? '',
            ];
        }

        return [
            'name' => $this->name,
            'sex' => $this->sex === '' ? null : $this->sex,
            'rg' => $this->rg,
            'age' => $normEmptyInt($this->age),
            'ssp' => $this->ssp,
            'situacao' => $this->situacao,
            'status' => $normEmptyInt($this->status),
            'hospital' => $this->hospital,
            'transporte' => $this->transporte,
            'unidade_saude' => $this->unidade_saude,
            'medico_us' => $this->medico_us,
            'crm_medico_us' => $this->crm_medico_us,
            'dados_complementares' => $this->dados_complementares,
            'victim_type_id' => $normEmptyInt($this->victim_type_id),
            'care_local_id' => $normEmptyInt($this->care_local_id),
            'fall_height' => $this->fall_height,
            'halito_etilico' => $this->halito_etilico,
            'burn' => $this->burn,
            'vehicle_role' => $this->vehicle_role,
            'accident_type' => $this->accident_type,
            'pupil_notes' => $this->pupil_notes,
            'witness_name' => $this->witness_name,
            'witness_rg' => $this->witness_rg,
            'witness_ssp' => $this->witness_ssp,
            'death_where' => $this->death_where,
            'death_notes' => $this->death_notes,
            'procedure_ids' => array_values(array_unique(array_map('intval', $this->procedure_ids))),
            'accessory_ids' => array_values(array_unique(array_map('intval', $this->accessory_ids))),
            'injury_site_ids' => array_values(array_unique(array_map('intval', $this->injury_site_ids))),
            'vital_rows' => $vitalNormalized,
        ];
    }

    private function triStateToBool(?string $v): ?bool
    {
        return match ($v ?? '') {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    public function render(): View
    {
        return view('livewire.operations.victim-record', [
            'victimTypes' => VictimType::query()->orderBy('name')->get(),
            'careLocals' => CareLocal::query()->orderBy('name')->get(),
            'procedures' => Procedure::query()->orderBy('name')->get(),
            'accessories' => Accessory::query()->orderBy('name')->get(),
            'injurySites' => InjurySite::query()->orderBy('name')->get(),
        ]);
    }
}
