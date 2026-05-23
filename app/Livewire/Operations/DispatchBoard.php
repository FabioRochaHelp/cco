<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Domain\Operations\Actions\AdvanceDispatchStageAction;
use App\Domain\Operations\Actions\CancelIncidentAction;
use App\Domain\Operations\Actions\CreateOperationalIncidentAction;
use App\Domain\Operations\Actions\DispatchUnitAction;
use App\Domain\Operations\Actions\ReleaseUnitAction;
use App\Domain\Operations\DTOs\AdvanceDispatchStageDTO;
use App\Domain\Operations\DTOs\CreateIncidentDTO;
use App\Domain\Operations\DTOs\DispatchUnitDTO;
use App\Domain\Operations\DTOs\ReleaseUnitDTO;
use App\Domain\Operations\Services\IncidentTimelineRecorder;
use App\Domain\Operations\Enums\CallType;
use App\Domain\Operations\Enums\DispatchStage;
use App\Domain\Operations\Enums\IncidentReportModality;
use App\Domain\Operations\Enums\IncidentStatus;
use App\Models\Incident;
use App\Models\IncidentDispatch;
use App\Models\IncidentEvent;
use App\Models\Municipio;
use App\Models\Nature;
use App\Models\Shift;
use App\Models\Vehicle;
use App\Support\Operations\OperationalIncidentVisibility;
use App\Support\Operations\OperationalMunicipioSelection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

#[Layout('layouts.app')]
#[Title('Central operacional')]
final class DispatchBoard extends Component
{
    /** Modal de empenho: ocorrência escolhida na fila. */
    public bool $showDispatchModal = false;

    public ?int $dispatchingIncidentId = null;

    public ?int $modalVehicleId = null;

    /** Modais de ação na fila: cancelar, observação, detalhe. */
    public bool $showCancelModal = false;

    public bool $showObservationModal = false;

    public bool $showDetailModal = false;

    public ?int $actionIncidentId = null;

    public string $cancelReason = '';

    public string $observationText = '';

    /** ID numérico em `municipios` para usuários centrais (sessão). */
    public ?string $selectedOperationalMunicipioId = null;

    public string $boardMessage = '';

    public function mount(): void
    {
        $user = Auth::user();
        if ($user !== null && $user->isOperationalCentral()) {
            $this->selectedOperationalMunicipioId = session('operational_municipio_id') !== null
                ? (string) session('operational_municipio_id')
                : null;
        }
    }

    public function updatedSelectedOperationalMunicipioId(?string $value): void
    {
        if ($value === null || $value === '') {
            session()->forget('operational_municipio_id');
        } else {
            session(['operational_municipio_id' => (int) $value]);
        }
    }

    public function resolveOperationalMunicipioId(): ?int
    {
        return OperationalMunicipioSelection::current(Auth::user());
    }

    public function createDemoIncident(CreateOperationalIncidentAction $action): void
    {
        $this->resetErrorBag();
        $this->boardMessage = '';

        $nature = Nature::query()->orderBy('id')->first();
        if ($nature === null) {
            $this->addError('tenant', 'Cadastre ao menos uma natureza.');

            return;
        }

        Gate::authorize('createOperational');

        $dto = new CreateIncidentDTO(
            municipioId: null,
            natureId: $nature->id,
            description: 'Ocorrência demonstrativa (CCO)',
            addressLine: null,
            number: null,
            district: null,
            city: null,
            callerName: 'Central',
            callerPhone: null,
            patientAge: null,
            patientSex: null,
            latitude: null,
            longitude: null,
            referenceNotes: null,
            callType: CallType::Normal,
            expectedVictimTotal: null,
            createdByUserId: Auth::id(),
        );

        try {
            $action->execute($dto);
            $this->boardMessage = 'Ocorrência registrada.';
        } catch (RuntimeException $e) {
            $this->addError('board', $e->getMessage());
        }
    }

    public function openDispatchModal(int $incidentId): void
    {
        $this->resetErrorBag();

        /** @var Incident|null $incident */
        $incident = Incident::query()->find($incidentId);
        if ($incident === null || $incident->status !== IncidentStatus::Open) {
            return;
        }

        Gate::authorize('dispatchUnit', $incident);

        $this->dispatchingIncidentId = $incident->id;
        $this->modalVehicleId = null;
        $this->showDispatchModal = true;
    }

    public function closeDispatchModal(): void
    {
        $this->showDispatchModal = false;
        $this->dispatchingIncidentId = null;
        $this->modalVehicleId = null;
    }

    /** Modal PBX é tratado por `OperationalCallIntakeBridge` no layout; aqui só atualizamos o aviso da Central. */
    #[On('call-intake-incident-saved')]
    public function onCallIntakeIncidentSaved(int $incidentId): void
    {
        $this->boardMessage = __('Ocorrência registrada (#:id).', ['id' => $incidentId]);
    }

    public function confirmDispatch(DispatchUnitAction $action): void
    {
        $this->resetErrorBag();
        $this->boardMessage = '';

        $this->validate(
            [
                'modalVehicleId' => ['required', 'integer'],
            ],
            [
                'modalVehicleId.required' => __('Selecione a viatura em turno.'),
            ],
        );

        if ($this->dispatchingIncidentId === null) {
            $this->closeDispatchModal();

            return;
        }

        /** @var Incident|null $incident */
        $incident = Incident::query()->find($this->dispatchingIncidentId);
        if ($incident === null || $incident->status !== IncidentStatus::Open) {
            $this->closeDispatchModal();

            return;
        }

        Gate::authorize('dispatchUnit', $incident);

        try {
            $action->execute(new DispatchUnitDTO(
                incidentId: $incident->id,
                vehicleId: $this->modalVehicleId,
                note: null,
                operatorUserId: Auth::id(),
            ));
            $this->boardMessage = __('Equipe empenhada.');
            $this->closeDispatchModal();
        } catch (RuntimeException $e) {
            $this->addError('board', $e->getMessage());
        }
    }

    public function advanceStage(int $dispatchId, AdvanceDispatchStageAction $action): void
    {
        $this->resetErrorBag();
        $this->boardMessage = '';

        /** @var IncidentDispatch|null $dispatch */
        $dispatch = IncidentDispatch::query()->find($dispatchId);
        if ($dispatch === null) {
            return;
        }

        Gate::authorize('advanceStage', $dispatch->incident);

        $target = $dispatch->stage->next();
        if ($target === null) {
            return;
        }

        try {
            $action->execute(new AdvanceDispatchStageDTO(
                incidentDispatchId: $dispatch->id,
                targetStage: $target,
                operatorUserId: Auth::id(),
            ));
            $this->boardMessage = 'Etapa atualizada.';
        } catch (RuntimeException $e) {
            $this->addError('board', $e->getMessage());
        }
    }

    public function releaseIncident(int $incidentId, int $vehicleId, ReleaseUnitAction $action): void
    {
        $this->resetErrorBag();
        $this->boardMessage = '';

        /** @var Incident|null $incident */
        $incident = Incident::query()->find($incidentId);
        if ($incident === null) {
            return;
        }

        Gate::authorize('releaseUnit', $incident);

        try {
            $action->execute(new ReleaseUnitDTO(
                incidentId: $incident->id,
                vehicleId: $vehicleId,
                operatorUserId: Auth::id(),
            ));
            $this->boardMessage = 'Viatura liberada / ocorrência encerrada.';
        } catch (RuntimeException $e) {
            $this->addError('board', $e->getMessage());
        }
    }

    public function openCancelModal(int $incidentId): void
    {
        $incident = Incident::query()->find($incidentId);
        if ($incident === null) {
            return;
        }

        Gate::authorize('cancel', $incident);

        $this->actionIncidentId = $incidentId;
        $this->cancelReason = '';
        $this->resetErrorBag();
        $this->showCancelModal = true;
    }

    public function cancelIncident(CancelIncidentAction $action): void
    {
        $this->resetErrorBag();

        $this->validate(
            ['cancelReason' => ['required', 'string', 'min:5', 'max:500']],
            ['cancelReason.required' => __('Informe o motivo do cancelamento.'), 'cancelReason.min' => __('O motivo deve ter ao menos 5 caracteres.')],
        );

        if ($this->actionIncidentId === null) {
            $this->showCancelModal = false;
            return;
        }

        /** @var Incident|null $incident */
        $incident = Incident::query()->find($this->actionIncidentId);
        if ($incident === null) {
            $this->showCancelModal = false;
            return;
        }

        Gate::authorize('cancel', $incident);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $action->execute($incident, $this->cancelReason, $user);

        $this->boardMessage = __('Ocorrência cancelada.');
        $this->showCancelModal = false;
        $this->actionIncidentId = null;
        $this->cancelReason = '';
    }

    public function openObservationModal(int $incidentId): void
    {
        $incident = Incident::query()->find($incidentId);
        if ($incident === null) {
            return;
        }

        Gate::authorize('addObservation', $incident);

        $this->actionIncidentId = $incidentId;
        $this->observationText = '';
        $this->resetErrorBag();
        $this->showObservationModal = true;
    }

    public function saveObservation(IncidentTimelineRecorder $recorder): void
    {
        $this->resetErrorBag();

        $this->validate(
            ['observationText' => ['required', 'string', 'min:3', 'max:2000']],
            ['observationText.required' => __('Informe o texto da observação.')],
        );

        if ($this->actionIncidentId === null) {
            $this->showObservationModal = false;
            return;
        }

        /** @var Incident|null $incident */
        $incident = Incident::query()->find($this->actionIncidentId);
        if ($incident === null) {
            $this->showObservationModal = false;
            return;
        }

        Gate::authorize('addObservation', $incident);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $recorder->record($incident, 'incident_observation', ['text' => $this->observationText], $user);

        $this->boardMessage = __('Observação registrada.');
        $this->showObservationModal = false;
        $this->actionIncidentId = null;
        $this->observationText = '';
    }

    public function openDetailModal(int $incidentId): void
    {
        $incident = Incident::query()->find($incidentId);
        if ($incident === null) {
            return;
        }

        Gate::authorize('view', $incident);

        $this->actionIncidentId = $incidentId;
        $this->showDetailModal = true;
    }

    public function closeActionModals(): void
    {
        $this->showCancelModal = false;
        $this->showObservationModal = false;
        $this->showDetailModal = false;
        $this->actionIncidentId = null;
        $this->cancelReason = '';
        $this->observationText = '';
    }

    public function render(): View
    {
        $municipioOptions = Auth::user()?->isOperationalCentral()
            ? Municipio::query()->orderBy('razao_social')->get()
            : collect();

        $mid = OperationalMunicipioSelection::current(Auth::user());

        $openIncidentsQuery = Incident::query()
            ->with(['nature', 'municipio'])
            ->where('status', IncidentStatus::Open);

        OperationalIncidentVisibility::constrainListing($openIncidentsQuery, Auth::user());

        $openIncidents = $openIncidentsQuery->clone()->orderByDesc('occurred_at')->get();

        $availableShiftsQuery = Shift::query()
            ->with(['vehicle', 'municipio', 'staff:id,name,cargo'])
            ->withCount('staff')
            ->operationalAvailability()
            ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid));

        $availableShifts = $availableShiftsQuery->clone()->orderBy('id')->get();

        /** Viaturas cadastradas sem turno ainda vigente (`ends_at >= now()`). */
        $vehiclesWithoutShiftQuery = Vehicle::query()
            ->with('municipio')
            ->whereDoesntHave(
                'shifts',
                fn ($q) => $q->where('ends_at', '>=', now()),
            )
            ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid));

        $vehiclesWithoutShift = $vehiclesWithoutShiftQuery->orderBy('prefix')->limit(120)->get();

        $kanbanDispatches = IncidentDispatch::query()
            ->with(['incident.nature', 'shift.vehicle'])
            ->whereNull('deleted_at')
            ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (IncidentDispatch $d) => $d->stage->value);

        $dispatchFireMeta = $kanbanDispatches->flatten()->mapWithKeys(function (IncidentDispatch $d): array {
            $modality = $d->incident?->nature?->report_modality;
            $closesAtLeftScene = $modality instanceof IncidentReportModality && $modality->closesAtLeftScene();

            return [$d->id => [
                'closesAtLeftScene' => $closesAtLeftScene,
                'releaseStage' => $closesAtLeftScene ? DispatchStage::LeftScene : DispatchStage::ReleasedHospital,
            ]];
        });

        $recentTimeline = IncidentEvent::query()
            ->with(['incident', 'actor'])
            ->when($mid !== null, fn ($q) => $q->where(static function ($w) use ($mid): void {
                $w->where('municipio_id', $mid)->orWhereNull('municipio_id');
            }))
            ->latest('recorded_at')
            ->limit(25)
            ->get();

        $stats = [
            'open_incidents' => $openIncidentsQuery->clone()->count(),
            'active_dispatches' => IncidentDispatch::query()
                ->whereNull('deleted_at')
                ->when($mid !== null, fn ($q) => $q->where('municipio_id', $mid))
                ->count(),
            'available_units' => $availableShiftsQuery->clone()->count(),
            'idle_vehicles' => $vehiclesWithoutShiftQuery->clone()->count(),
        ];

        $modalIncident = $this->dispatchingIncidentId !== null
            ? Incident::query()->with('municipio')->find($this->dispatchingIncidentId)
            : null;

        $modalShifts = $modalIncident !== null
            ? (
                $modalIncident->municipio_id === null
                    ? $availableShifts
                    : $availableShifts->filter(
                        fn (Shift $s): bool => (int) $s->municipio_id === (int) $modalIncident->municipio_id,
                    )->values()
            )
            : collect();

        $actionIncident = $this->actionIncidentId !== null
            ? Incident::query()->with(['nature', 'municipio'])->find($this->actionIncidentId)
            : null;

        return view('livewire.operations.dispatch-board', [
            'municipioOptions' => $municipioOptions,
            'openIncidents' => $openIncidents,
            'availableShifts' => $availableShifts,
            'vehiclesWithoutShift' => $vehiclesWithoutShift,
            'modalIncident' => $modalIncident,
            'modalShifts' => $modalShifts,
            'kanbanDispatches' => $kanbanDispatches,
            'dispatchFireMeta' => $dispatchFireMeta,
            'orderedStages' => DispatchStage::ordered(),
            'recentTimeline' => $recentTimeline,
            'stats' => $stats,
            'actionIncident' => $actionIncident,
        ]);
    }
}
