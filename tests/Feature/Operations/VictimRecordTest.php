<?php

declare(strict_types=1);

use App\Domain\Operations\Enums\IncidentStatus;
use App\Livewire\Operations\VictimRecord;
use App\Models\Incident;
use App\Models\Municipio;
use App\Models\Nature;
use App\Models\Procedure;
use App\Models\User;
use App\Models\Victim;
use Database\Seeders\OperationalDemoSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(OperationalDemoSeeder::class);
});

function victimRecordTestIncident(): Incident
{
    /** @var Municipio $municipio */
    $municipio = Municipio::query()->firstOrFail();
    $nature = Nature::query()->firstOrFail();

    return Incident::query()->create([
        'municipio_id' => $municipio->id,
        'dispatch_year' => (int) now()->format('Y'),
        'talao' => 790000 + random_int(1, 999),
        'status' => IncidentStatus::Open,
        'nature_id' => $nature->id,
        'occurred_at' => now(),
        'patient_call_type' => 'N',
        'description' => 'Ocorrência para teste de vítima',
        'caller_phone' => '11999996666',
    ]);
}

test('guest is redirected from victim registration route', function (): void {
    $incident = victimRecordTestIncident();

    $this->get(route('operations.incidents.victims.create', $incident))
        ->assertRedirect();
});

test('nurse profile cannot open victim registration screen', function (): void {
    $incident = victimRecordTestIncident();

    /** @var User $nurse */
    $nurse = User::query()->where('email', 'enfermeiro@example.com')->firstOrFail();

    $this->actingAs($nurse)
        ->get(route('operations.incidents.victims.create', $incident))
        ->assertForbidden();
});

test('nurse profile cannot edit victim record', function (): void {
    $incident = victimRecordTestIncident();
    /** @var Municipio $municipio */
    $municipio = Municipio::query()->firstOrFail();

    $victim = Victim::query()->create([
        'municipio_id' => $municipio->id,
        'incident_id' => $incident->id,
        'name' => 'Paciente teste',
        'situacao' => 1,
    ]);

    /** @var User $nurse */
    $nurse = User::query()->where('email', 'enfermeiro@example.com')->firstOrFail();

    $this->actingAs($nurse)
        ->get(route('operations.incidents.victims.edit', [$incident, $victim]))
        ->assertForbidden();
});

test('municipal operator can open victim registration screen', function (): void {
    $incident = victimRecordTestIncident();

    /** @var User $municipal */
    $municipal = User::query()->where('email', 'municipal@example.com')->firstOrFail();

    $this->actingAs($municipal)
        ->get(route('operations.incidents.victims.create', $incident))
        ->assertOk();
});

test('municipal operator can save victim with procedures and timeline records event', function (): void {
    $incident = victimRecordTestIncident();
    $procedure = Procedure::query()->firstOrFail();

    /** @var User $municipal */
    $municipal = User::query()->where('email', 'municipal@example.com')->firstOrFail();

    Livewire::actingAs($municipal)
        ->test(VictimRecord::class, ['incident' => $incident])
        ->set('name', 'Maria Silva')
        ->set('situacao', '1')
        ->set('procedure_ids', [(string) $procedure->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('operations.incidents.show', $incident));

    $victim = Victim::query()->where('incident_id', $incident->id)->first();
    expect($victim)->not->toBeNull()
        ->and($victim->name)->toBe('Maria Silva')
        ->and($victim->procedures()->pluck('procedures.id')->all())->toContain($procedure->id);

    expect($incident->fresh()->incidentEvents()->where('event_key', 'victim_recorded')->exists())->toBeTrue();
});

test('victim edit route returns 404 when victim belongs to another incident', function (): void {
    $incidentA = victimRecordTestIncident();
    $incidentB = victimRecordTestIncident();
    /** @var Municipio $municipio */
    $municipio = Municipio::query()->firstOrFail();

    $victimOnA = Victim::query()->create([
        'municipio_id' => $municipio->id,
        'incident_id' => $incidentA->id,
        'name' => 'Só na A',
        'situacao' => 1,
    ]);

    /** @var User $municipal */
    $municipal = User::query()->where('email', 'municipal@example.com')->firstOrFail();

    $this->actingAs($municipal)
        ->get(route('operations.incidents.victims.edit', [$incidentB, $victimOnA]))
        ->assertNotFound();
});
