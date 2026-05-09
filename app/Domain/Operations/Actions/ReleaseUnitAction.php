<?php

declare(strict_types=1);

namespace App\Domain\Operations\Actions;

use App\Domain\Operations\DTOs\ReleaseUnitDTO;
use App\Domain\Operations\Enums\DispatchStage;
use App\Domain\Operations\Enums\IncidentStatus;
use App\Domain\Operations\Enums\ShiftStatus;
use App\Domain\Operations\Events\UnitReleased;
use App\Domain\Operations\Services\IncidentTimelineRecorder;
use App\Models\Incident;
use App\Models\IncidentDispatch;
use App\Models\Shift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class ReleaseUnitAction
{
    public function __construct(
        private IncidentTimelineRecorder $timeline,
    ) {}

    public function execute(ReleaseUnitDTO $dto): void
    {
        $lock = Cache::lock('dispatch:vehicle:'.$dto->vehicleId, 15);

        $lock->block(10, function () use ($dto): void {
            DB::transaction(function () use ($dto): void {
                /** @var Incident $incident */
                $incident = Incident::query()->findOrFail($dto->incidentId);

                /** @var Shift|null $shift */
                $shift = Shift::query()
                    ->where('vehicle_id', $dto->vehicleId)
                    ->where('municipio_id', $incident->municipio_id)
                    ->where('ends_at', '>=', now())
                    ->first();

                if ($shift === null) {
                    throw new RuntimeException('Turno ativo não encontrado para a viatura.');
                }

                /** @var IncidentDispatch|null $dispatch */
                $dispatch = IncidentDispatch::query()
                    ->where('incident_id', $incident->id)
                    ->where('shift_id', $shift->id)
                    ->whereNull('deleted_at')
                    ->latest('id')
                    ->first();

                if ($dispatch === null) {
                    throw new RuntimeException('Despacho ativo não encontrado.');
                }

                if ($dispatch->stage !== DispatchStage::ReleasedHospital) {
                    throw new RuntimeException('Encerramento só é permitido após etapa "liberada da US".');
                }

                $now = now();

                $dispatch->delete();

                $shift->update(['status' => ShiftStatus::Available, 'status_legacy' => 1]);

                $incident->update([
                    'status' => IncidentStatus::PendingNurseReport,
                    'returned_base_at' => $now,
                    'primary_shift_id' => null,
                ]);

                $this->timeline->record($incident, 'unit_released', [
                    'shift_id' => $shift->id,
                    'vehicle_id' => $dto->vehicleId,
                    'operator_user_id' => $dto->operatorUserId,
                ]);

                UnitReleased::dispatch($incident->fresh());
            });
        });
    }
}
