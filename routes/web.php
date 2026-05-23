<?php

declare(strict_types=1);

use App\Http\Controllers\Operations\IncidentCallIntakeWebhookController;
use App\Http\Controllers\Operations\IncidentRouteController;
use App\Http\Controllers\Operations\MapVehiclesController;
use App\Livewire\Dashboard;
use App\Livewire\Operations\Admin\SystemUserManage;
use App\Livewire\Operations\Cadastro\MunicipioManage;
use App\Livewire\Operations\Cadastro\ShiftManage;
use App\Livewire\Operations\DispatchBoard;
use App\Livewire\Operations\FleetShifts;
use App\Livewire\Operations\IncidentCallStart;
use App\Livewire\Operations\IncidentCreate;
use App\Livewire\Operations\IncidentIndex;
use App\Livewire\Operations\IncidentFinalReport;
use App\Livewire\Operations\IncidentNurseReport;
use App\Livewire\Operations\IncidentOperationalDetail;
use App\Livewire\Operations\Parameters\AccessoryParameterManage;
use App\Livewire\Operations\Parameters\CareLocalParameterManage;
use App\Livewire\Operations\Parameters\HealthUnitParameterManage;
use App\Livewire\Operations\Parameters\InjurySiteParameterManage;
use App\Livewire\Operations\Parameters\NatureParameterManage;
use App\Livewire\Operations\Parameters\OperationalSupportParameterManage;
use App\Livewire\Operations\Parameters\ProcedureParameterManage;
use App\Livewire\Operations\Parameters\VictimTypeParameterManage;
use App\Livewire\Operations\StaffManage;
use App\Livewire\Operations\VehicleManage;
use App\Livewire\Operations\VictimRecord;
use App\Livewire\Operations\Victims\PrescriptionApproval;
use App\Livewire\Operations\Victims\PrescriptionForm;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('integrations/calls/incident-intake', IncidentCallIntakeWebhookController::class)
    ->name('integrations.calls.incident-intake');

Route::middleware(['operational.tenant'])
    ->prefix('operations')
    ->group(function (): void {
        Route::get('/incidents/create', IncidentCreate::class)->name('operations.incidents.create');
        Route::view('/incidents/registered', 'operations.guest-incident-registered')->name('operations.incidents.registered-guest');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::middleware(['operational.tenant', 'operational.central'])
        ->prefix('operations/parameters')
        ->group(function (): void {
            Route::get('/acessorios', AccessoryParameterManage::class)->name('operations.parameters.accessories');
            Route::get('/apoios', OperationalSupportParameterManage::class)->name('operations.parameters.operational-supports');
            Route::get('/locais', CareLocalParameterManage::class)->name('operations.parameters.care-locals');
            Route::get('/locais-ferimento', InjurySiteParameterManage::class)->name('operations.parameters.injury-sites');
            Route::get('/naturezas', NatureParameterManage::class)->name('operations.parameters.natures');
            Route::get('/procedimentos', ProcedureParameterManage::class)->name('operations.parameters.procedures');
            Route::get('/tipos-vitima', VictimTypeParameterManage::class)->name('operations.parameters.victim-types');
            Route::get('/unidades-atendimento', HealthUnitParameterManage::class)->name('operations.parameters.health-units');
        });

    Route::middleware(['operational.tenant', 'operational.central'])
        ->prefix('operations/cadastro')
        ->group(function (): void {
            Route::get('/bases', MunicipioManage::class)->name('operations.cadastro.bases');
        });

    Route::middleware(['operational.tenant'])
        ->prefix('operations/cadastro')
        ->group(function (): void {
            Route::get('/viaturas', VehicleManage::class)->name('operations.cadastro.vehicles');
            Route::get('/efetivo', StaffManage::class)->name('operations.cadastro.staff');
            Route::get('/turnos', ShiftManage::class)->name('operations.cadastro.shifts');
        });

    Route::middleware(['operational.tenant'])
        ->prefix('operations/admin')
        ->group(function (): void {
            Route::get('/usuarios', SystemUserManage::class)->name('operations.admin.users');
        });

    Route::middleware(['operational.tenant'])
        ->prefix('operations')
        ->group(function (): void {
            Route::redirect('catalog/vehicles', '/operations/cadastro/viaturas')->name('operations.catalog.vehicles');
            Route::redirect('catalog/staff', '/operations/cadastro/efetivo')->name('operations.catalog.staff');

            Route::get('/dispatch', DispatchBoard::class)->name('operations.dispatch');
            Route::get('/incidents', IncidentIndex::class)->name('operations.incidents.index');
            Route::get('/incidents/start', IncidentCallStart::class)->name('operations.incidents.start');
            Route::get('/incidents/{incident}/victims/create', VictimRecord::class)->name('operations.incidents.victims.create');
            Route::get('/incidents/{incident}/victims/{victim}/edit', VictimRecord::class)->name('operations.incidents.victims.edit');
            Route::get('/victims/{victim}/prescriptions/create', PrescriptionForm::class)->name('operations.victims.prescriptions.create');
            Route::get('/prescriptions/{prescription}/approval', PrescriptionApproval::class)->name('operations.prescriptions.approval');
            Route::get('/incidents/{incident}/nurse-report', IncidentNurseReport::class)->name('operations.incidents.nurse-report');
            Route::get('/incidents/{incident}/final-report', IncidentFinalReport::class)->name('operations.incidents.final-report');
            Route::get('/map/vehicles', MapVehiclesController::class)->name('operations.map.vehicles');
            Route::get('/incidents/{incident}/route', IncidentRouteController::class)->name('operations.incidents.route');
            Route::get('/incidents/{incident}', IncidentOperationalDetail::class)->name('operations.incidents.show');
            Route::get('/fleet', FleetShifts::class)->name('operations.fleet');
        });
});

require __DIR__.'/settings.php';
