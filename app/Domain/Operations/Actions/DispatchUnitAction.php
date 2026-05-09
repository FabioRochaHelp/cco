<?php

declare(strict_types=1);

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\DTOs\DispatchUnitDTO;
use App\Domain\Operations\Enums\DispatchStage;
use App\Domain\Operations\Enums\IncidentStatus;
use App\Domain\Operations\Enums\ShiftStatus;
use App\Domain\Operations\Events\UnitDispatched;
use App\Domain\Operations\Services\IncidentTimelineRecorder;
use App\Models\Incident;
use App\Models\IncidentDispatch;
use App\Models\Shift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DispatchUnitAction
{
    public function __construct(
        private IncidentTimelineRecorder $timeline,
    ) {}

    public function execute(DispatchUnitDTO $dto): IncidentDispatch
    {
        $lock = Cache::lock('dispatch:vehicle:'.$dto->vehicleId, 15);

        return $lock->block(10, function () use ($dto): IncidentDispatch {
            return DB::transaction(function () use ($dto): IncidentDispatch {
                /** @var Incident $incident */
                $incident = Incident::query()->findOrFail($dto->incidentId);

                if ($incident->status !== IncidentStatus::Open) {
                    throw new RuntimeException('Somente ocorrências abertas podem ser despachadas.');
                }

                $existingActive = IncidentDispatch::query()
                    ->where('incident_id', $incident->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($existingActive) {
                    throw new RuntimeException('Ocorrência já possui despacho ativo.');
                }

                $shiftQuery = Shift::query()
                    ->where('vehicle_id', $dto->vehicleId)
                    ->operationalAvailability();

                if ($incident->municipio_id !== null) {
                    $shiftQuery->where('municipio_id', $incident->municipio_id);
                }

                $shift = $shiftQuery->first();

                if ($shift === null) {
                    throw new RuntimeException('Nenhum turno disponível para esta viatura.');
                }

                if ($incident->municipio_id !== null
                    && (int) $incident->municipio_id !== (int) $shift->municipio_id) {
                    throw new RuntimeException('Viatura não pertence à base desta ocorrência.');
                }

                $busy = IncidentDispatch::query()
                    ->where('shift_id', $shift->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if ($busy) {
                    throw new RuntimeException('Turno já empenhado em outra ocorrência.');
                }

                $now = now();

                $dispatch = IncidentDispatch::create([
                    'municipio_id' => $shift->municipio_id,
                    'incident_id' => $incident->id,
                    'shift_id' => $shift->id,
                    'stage' => DispatchStage::Dispatched,
                    'stage_position' => DispatchStage::Dispatched->index() + 1,
                ]);

                $shift->update(['status' => ShiftStatus::Assigned, 'status_legacy' => 2]);

                $incident->update([
                    'municipio_id' => $shift->municipio_id,
                    'status' => IncidentStatus::Dispatched,
                    'primary_shift_id' => $shift->id,
                    'dispatched_at' => $now,
                ]);

                $this->timeline->record($incident, 'unit_dispatched', [
                    'shift_id' => $shift->id,
                    'vehicle_id' => $dto->vehicleId,
                    'operator_user_id' => $dto->operatorUserId,
                    'note' => $dto->note,
                ]);

                UnitDispatched::dispatch($incident->fresh(), $dispatch->fresh());

                return $dispatch->fresh();
            });
        });
    }
}
