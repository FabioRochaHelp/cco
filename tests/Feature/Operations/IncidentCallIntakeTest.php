<?php

declare(strict_types=1);

use App\Domain\Operations\Events\OperationalCallIntakeReceived;
use App\Livewire\Operations\IncidentCallStart;
use App\Livewire\Operations\IncidentCreate;
use App\Livewire\Operations\OperationalCallIntakeBridge;
use App\Models\Nature;
use App\Models\User;
use Database\Seeders\OperationalDemoSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(OperationalDemoSeeder::class);
});

test('webhook rejects request without valid secret', function (): void {
    config(['operations.call_webhook_secret' => 'configured']);

    $this->postJson('/integrations/calls/incident-intake', [
        'phone' => '11987654321',
    ], ['X-Webhook-Secret' => 'wrong'])
        ->assertForbidden();
});

test('webhook returns signed form url and create page shows phone', function (): void {
    Event::fake([OperationalCallIntakeReceived::class]);
    config(['operations.call_webhook_secret' => 'test-secret']);

    $response = $this->postJson('/integrations/calls/incident-intake', [
        'phone' => '+55 (11) 98765-4321',
        'caller_name' => 'Maria',
        'latitude' => -23.55,
        'longitude' => -46.63,
        'external_reference' => 'PBX-999',
    ], ['X-Webhook-Secret' => 'test-secret']);

    $response->assertOk()
        ->assertJsonStructure(['form_url', 'expires_at']);

    Event::assertDispatched(function (OperationalCallIntakeReceived $e): bool {
        return $e->callerName === 'Maria'
            && $e->latitude === '-23.55'
            && $e->longitude === '-46.63'
            && $e->externalReference === 'PBX-999';
    });

    $url = (string) $response->json('form_url');
    expect($url)->toContain('signature=');

    $this->get($url)
        ->assertOk()
        ->assertSee('11987654321', false)
        ->assertSee('Maria', false)
        ->assertSee('PBX-999', false);
});

test('guest can submit incident from signed webhook url without login', function (): void {
    Event::fake([OperationalCallIntakeReceived::class]);
    config(['operations.call_webhook_secret' => 'test-secret']);

    $response = $this->postJson('/integrations/calls/incident-intake', [
        'phone' => '11998877666',
    ], ['X-Webhook-Secret' => 'test-secret']);

    $url = (string) $response->json('form_url');
    $this->get($url)->assertOk();

    /** @var Nature $nature */
    $nature = Nature::query()->firstOrFail();

    Livewire::test(IncidentCreate::class)
        ->set('occurred_at', now()->format('Y-m-d\\TH:i'))
        ->set('nature_id', $nature->id)
        ->set('description', 'Paciente com dor torácica.')
        ->set('caller_phone', '11998877666')
        ->call('saveWithCallType', 'U')
        ->assertHasNoErrors()
        ->assertRedirect(route('operations.incidents.registered-guest'));

    $this->get(route('operations.incidents.registered-guest'))
        ->assertOk()
        ->assertSee('Talão', false);
});

test('manual call start stores phone in session for create form', function (): void {
    /** @var User $central */
    $central = User::query()->where('email', 'central@example.com')->firstOrFail();

    Livewire::actingAs($central)
        ->test(IncidentCallStart::class)
        ->set('caller_phone', '(11) 3333-4444')
        ->call('continueToForm')
        ->assertHasNoErrors()
        ->assertRedirect(route('operations.incidents.create'));

    $this->actingAs($central)
        ->get(route('operations.incidents.create'))
        ->assertOk()
        ->assertSee('1133334444', false);
});

test('operational bridge accepts Laravel broadcast socket extra argument', function (): void {
    /** @var User $central */
    $central = User::query()->where('email', 'central@example.com')->firstOrFail();

    Livewire::actingAs($central)
        ->test(OperationalCallIntakeBridge::class)
        ->call(
            'openOperationalCallIntakeFromBroadcast',
            form_url: 'https://example.test/signed',
            phone: '11888877777',
            expires_at: now()->addMinutes(30)->toIso8601String(),
            socket: 'mock-socket-id',
        )
        ->assertSet('callIntakePrefill.phone', '11888877777')
        ->assertSet('showCallIntakeModal', true);
});

test('operational bridge normalizes numeric phone from echo-shaped payload', function (): void {
    /** @var User $central */
    $central = User::query()->where('email', 'central@example.com')->firstOrFail();

    Livewire::actingAs($central)
        ->test(OperationalCallIntakeBridge::class)
        ->call('openOperationalCallIntakeFromBroadcast', phone: 11_988_877_777)
        ->assertSet('callIntakePrefill.phone', '11988877777');
});

test('operational bridge opens modal when receiving operational call intake payload', function (): void {
    /** @var User $central */
    $central = User::query()->where('email', 'central@example.com')->firstOrFail();

    Livewire::actingAs($central)
        ->test(OperationalCallIntakeBridge::class)
        ->call(
            'openOperationalCallIntakeFromBroadcast',
            form_url: 'https://example.test/signed',
            phone: '11888877777',
            expires_at: now()->addMinutes(30)->toIso8601String(),
            caller_name: 'João',
            latitude: '-10.5',
            longitude: '-20.25',
            call_received_at: now()->toIso8601String(),
            external_reference: 'PBX-X',
        )
        ->assertSet('showCallIntakeModal', true)
        ->assertSet('callIntakePrefill.phone', '11888877777')
        ->assertSet('callIntakePrefill.caller_name', 'João');
});

test('incident create embedded in modal receives caller phone from props', function (): void {
    /** @var User $central */
    $central = User::query()->where('email', 'central@example.com')->firstOrFail();

    Livewire::actingAs($central)
        ->test(IncidentCreate::class, [
            'embeddedInModal' => true,
            'caller_phone' => '11988776655',
        ])
        ->assertSet('caller_phone', '11988776655')
        ->assertSet('embeddedInModal', true);
});
