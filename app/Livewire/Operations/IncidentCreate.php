<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Domain\Operations\Actions\CreateOperationalIncidentAction;
use App\Domain\Operations\DTOs\CreateIncidentDTO;
use App\Domain\Operations\Enums\CallType;
use App\Models\Incident;
use App\Models\Nature;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/** Cadastro de ocorrência (equivalente a rotas legadas `ocorrencia/create`). */
#[Layout('layouts.app')]
#[Title('Nova ocorrência')]
final class IncidentCreate extends Component
{
    public string $occurred_at = '';

    public ?string $call_received_at = '';

    public ?int $nature_id = null;

    public string $description = '';

    public ?string $address_line = '';

    public ?string $number = '';

    public ?string $district = '';

    public ?string $city = '';

    public ?string $reference_notes = '';

    public ?string $caller_name = '';

    public ?string $caller_phone = '';

    public ?string $patient_name = '';

    public ?int $patient_age = null;

    public ?string $patient_sex = '';

    public string $patient_call_type = 'N';

    public ?int $expected_victim_total = null;

    public bool $is_qta = false;

    public ?int $total_death_count = null;

    public ?string $latitude = '';

    public ?string $longitude = '';

    public string $message = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Incident::class);
        abort_unless(Auth::user()?->hasOperationalAbility('incident.create'), 403);
        $this->occurred_at = now()->format('Y-m-d\TH:i');
    }

    /**
     * Normaliza entradas vazias para nullable antes das regras (`date`, etc.).
     *
     * @param  array<string, mixed>  $attributes  Dados vindos das propriedades públicas do componente.
     * @return array<string, mixed>
     */
    protected function prepareForValidation($attributes)
    {
        $natureId = $attributes['nature_id'] ?? null;
        if ($natureId === '' || $natureId === null) {
            $natureId = null;
        } else {
            $natureId = (int) $natureId;
        }

        $callReceivedAt = $attributes['call_received_at'] ?? null;

        return array_merge($attributes, [
            'call_received_at' => ($callReceivedAt === '' || $callReceivedAt === null) ? null : $callReceivedAt,
            'nature_id' => $natureId,
        ]);
    }

    public function save(CreateOperationalIncidentAction $action): void
    {
        $this->resetErrorBag();

        if ($this->latitude === '') {
            $this->latitude = null;
        }
        if ($this->longitude === '') {
            $this->longitude = null;
        }

        if (! Gate::allows('createOperational')) {
            $this->addError('scope', __('Sem permissão para registrar ocorrência.'));

            return;
        }

        $validated = $this->validate([
            'occurred_at' => ['required', 'date'],
            'call_received_at' => ['nullable', 'date'],
            'nature_id' => ['required', 'integer', Rule::exists('natures', 'id')],
            'description' => ['required', 'string', 'max:5000'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:64'],
            'district' => ['nullable', 'string', 'max:128'],
            'city' => ['nullable', 'string', 'max:128'],
            'reference_notes' => ['nullable', 'string', 'max:2000'],
            'caller_name' => ['nullable', 'string', 'max:255'],
            'caller_phone' => ['nullable', 'string', 'max:64'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'patient_age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'patient_sex' => ['nullable', 'string', 'max:16'],
            'patient_call_type' => ['required', Rule::enum(CallType::class)],
            'expected_victim_total' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_qta' => ['boolean'],
            'total_death_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $occurred = CarbonImmutable::parse($validated['occurred_at']);
        $callReceived = isset($validated['call_received_at']) && $validated['call_received_at']
            ? CarbonImmutable::parse($validated['call_received_at'])
            : null;

        $enumCallType = CallType::from($validated['patient_call_type']);

        $dto = new CreateIncidentDTO(
            municipioId: null,
            natureId: $validated['nature_id'],
            description: $validated['description'],
            addressLine: $validated['address_line'] ?: null,
            number: $validated['number'] ?: null,
            district: $validated['district'] ?: null,
            city: $validated['city'] ?: null,
            callerName: $validated['caller_name'] ?: null,
            callerPhone: $validated['caller_phone'] ?: null,
            patientAge: $validated['patient_age'],
            patientSex: $validated['patient_sex'] ?: null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            referenceNotes: $validated['reference_notes'] ?: null,
            callType: $enumCallType,
            expectedVictimTotal: $validated['expected_victim_total'],
            createdByUserId: Auth::id(),
            patientName: $validated['patient_name'] ?: null,
            protectedAreaId: null,
            isQta: $validated['is_qta'],
            totalDeathCount: $validated['total_death_count'],
            occurredAt: $occurred,
            callReceivedAt: $callReceived,
        );

        try {
            $incident = $action->execute($dto);
        } catch (QueryException $e) {
            report($e);
            $this->addError('save', __('Não foi possível salvar (dados duplicados ou violação no banco). Verifique o talão ou tente novamente.'));

            return;
        } catch (Throwable $e) {
            report($e);
            $this->addError('save', __('Não foi possível salvar a ocorrência. Tente novamente ou contate o suporte.'));

            return;
        }

        $this->redirect(route('operations.incidents.show', $incident), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.operations.incident-create', [
            'natures' => Nature::query()->orderBy('name')->get(),
            'callTypes' => CallType::cases(),
        ]);
    }
}
