<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Domain\Operations\Actions\CreateOperationalIncidentAction;
use App\Domain\Operations\DTOs\CreateIncidentDTO;
use App\Domain\Operations\Enums\CallType;
use App\Models\Incident;
use App\Models\Nature;
use App\Support\Operations\IncidentPhoneNormalizer;
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

    /** Fluxo PBX/webhook: formulário sem login (URL assinada + sessão até expirar). */
    public bool $guest_intake = false;

    /** Incorporado no modal da Central (dados via Reverb + preenchimento; operador autenticado). */
    public bool $embeddedInModal = false;

    public function mount(): void
    {
        $this->occurred_at = now()->format('Y-m-d\TH:i');

        if ($this->embeddedInModal) {
            session()->forget('operations.incident_create_guest');
            $this->guest_intake = false;

            $user = Auth::user();
            abort_unless($user !== null, 403);
            abort_unless(Gate::forUser($user)->allows('viewAny', Incident::class), 403);
            abort_unless($user->hasOperationalAbility('incident.create'), 403);

            $this->hydrateEmbeddedPrefillFromProps();

            return;
        }

        if (request()->hasValidSignature()) {
            $this->hydrateFromSignedQuery(request()->query());
            session()->put('operations.incident_create_guest', [
                'expires_at' => (int) request()->query('expires', 0),
            ]);
            $this->guest_intake = true;

            return;
        }

        if ($this->guestSignedLinkSessionValid()) {
            $this->guest_intake = true;

            return;
        }

        session()->forget('operations.incident_create_guest');

        $user = Auth::user();
        abort_unless($user !== null, 403);
        abort_unless(Gate::forUser($user)->allows('viewAny', Incident::class), 403);
        abort_unless($user->hasOperationalAbility('incident.create'), 403);

        if ($intake = session()->pull('operations.incident_intake')) {
            $this->hydrateFromSessionIntake($intake);
        }
    }

    /** @param  array<string, mixed>  $query */
    private function hydrateFromSignedQuery(array $query): void
    {
        if (isset($query['phone'])) {
            $this->caller_phone = IncidentPhoneNormalizer::normalize((string) $query['phone']);
        }
        if (! empty($query['name'])) {
            $this->caller_name = (string) $query['name'];
        }
        if (isset($query['lat']) && (string) $query['lat'] !== '') {
            $this->latitude = (string) $query['lat'];
        }
        if (isset($query['lng']) && (string) $query['lng'] !== '') {
            $this->longitude = (string) $query['lng'];
        }
        if (! empty($query['received_at'])) {
            try {
                $this->call_received_at = CarbonImmutable::parse((string) $query['received_at'])->format('Y-m-d\TH:i');
            } catch (Throwable) {
                //
            }
        }
        if (! empty($query['ref'])) {
            $this->reference_notes = (string) $query['ref'];
        }
    }

    /** @param  array<string, mixed>  $intake */
    private function hydrateFromSessionIntake(array $intake): void
    {
        if (! empty($intake['caller_phone'])) {
            $this->caller_phone = IncidentPhoneNormalizer::normalize((string) $intake['caller_phone']);
        }
    }

    private function hydrateEmbeddedPrefillFromProps(): void
    {
        $this->caller_phone = IncidentPhoneNormalizer::normalize((string) ($this->caller_phone ?? ''));

        if ($this->call_received_at !== null && $this->call_received_at !== '') {
            try {
                $this->call_received_at = CarbonImmutable::parse((string) $this->call_received_at)->format('Y-m-d\TH:i');
            } catch (Throwable) {
                $this->call_received_at = '';
            }
        }
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

        $callerPhone = IncidentPhoneNormalizer::normalize((string) ($attributes['caller_phone'] ?? ''));

        return array_merge($attributes, [
            'call_received_at' => ($callReceivedAt === '' || $callReceivedAt === null) ? null : $callReceivedAt,
            'caller_phone' => $callerPhone,
            'nature_id' => $natureId,
        ]);
    }

    public function save(CreateOperationalIncidentAction $action): void
    {
        $this->resetErrorBag();

        if ($this->guest_intake && ! $this->guestSignedLinkSessionValid()) {
            $this->addError('scope', __('O tempo deste formulário expirou. Peça um novo link pela central ou entre no sistema.'));

            return;
        }

        if ($this->latitude === '') {
            $this->latitude = null;
        }
        if ($this->longitude === '') {
            $this->longitude = null;
        }

        if (! $this->guestSignedLinkSessionValid()) {
            if (! Gate::allows('createOperational')) {
                $this->addError('scope', __('Sem permissão para registrar ocorrência.'));

                return;
            }
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
            'caller_phone' => ['required', 'string', 'min:8', 'max:64'],
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

        if ($this->embeddedInModal) {
            $this->dispatch('call-intake-incident-saved', incidentId: $incident->id);

            return;
        }

        if ($this->guestSignedLinkSessionValid()) {
            session()->forget('operations.incident_create_guest');
            $this->guest_intake = false;
            session()->flash('registered_incident', [
                'talao' => $incident->talao,
                'dispatch_year' => $incident->dispatch_year,
            ]);
            $this->redirect(route('operations.incidents.registered-guest'), navigate: true);

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

    private function guestSignedLinkSessionValid(): bool
    {
        $guest = session()->get('operations.incident_create_guest');

        return is_array($guest)
            && isset($guest['expires_at'])
            && (int) $guest['expires_at'] >= now()->timestamp;
    }
}
