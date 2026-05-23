<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Integrations\Traccar\TraccarService;
use App\Models\Municipio;
use App\Models\Vehicle;
use App\Support\Operations\OperationalMunicipioSelection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Cadastro de viaturas (docs/migracao/entidades.md — viatura). */
#[Layout('layouts.app')]
#[Title('Viaturas')]
final class VehicleManage extends Component
{
    public ?string $plate = '';

    public ?string $prefix = '';

    public ?string $make = '';

    public ?string $model = '';

    public ?int $year = null;

    public ?string $device_id = '';

    public ?int $status_legacy = null;

    public ?int $editingId = null;

    public string $message = '';

    /** @var array<int, array{id: int, name: string, uniqueId: string, status: string}> */
    public array $traccarDeviceList = [];

    public bool $traccarAvailable = false;

    /** Base escolhida na central (espelha sessão `operational_municipio_id`). */
    public ?string $selectedOperationalMunicipioId = null;

    public function mount(TraccarService $traccar): void
    {
        Gate::authorize('viewAny', Vehicle::class);

        $user = Auth::user();
        if ($user !== null && $user->isOperationalCentral()) {
            $this->selectedOperationalMunicipioId = session('operational_municipio_id') !== null
                ? (string) session('operational_municipio_id')
                : null;
        }

        try {
            $this->traccarDeviceList = $traccar->devices()
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'uniqueId' => $d->uniqueId,
                    'status' => $d->status,
                ])
                ->all();
            $this->traccarAvailable = true;
        } catch (\Throwable) {
            $this->traccarDeviceList = [];
            $this->traccarAvailable = false;
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

    private function municipioId(): ?int
    {
        $user = Auth::user();
        if ($user?->municipio_id !== null) {
            return (int) $user->municipio_id;
        }
        if ($user?->isOperationalCentral()) {
            return $this->selectedOperationalMunicipioId !== null && $this->selectedOperationalMunicipioId !== ''
                ? (int) $this->selectedOperationalMunicipioId
                : null;
        }

        return OperationalMunicipioSelection::current($user);
    }

    public function resetForm(): void
    {
        $this->plate = '';
        $this->prefix = '';
        $this->make = '';
        $this->model = '';
        $this->year = null;
        $this->device_id = '';
        $this->status_legacy = null;
        $this->editingId = null;
    }

    public function edit(int $id): void
    {
        $this->resetErrorBag();
        $v = Vehicle::query()->findOrFail($id);
        $this->authorize('update', $v);
        $this->editingId = $v->id;
        $this->plate = $v->plate;
        $this->prefix = $v->prefix;
        $this->make = $v->make;
        $this->model = $v->model;
        $this->year = $v->year;
        $this->device_id = $v->device_id ?? '';
        $this->status_legacy = $v->status_legacy;
    }

    public function save(): void
    {
        $this->resetErrorBag();
        $mid = $this->municipioId();
        if ($mid === null) {
            $this->addError('scope', __('Defina o município na central ou use um usuário municipal.'));

            return;
        }

        $validated = $this->validate([
            'plate' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('vehicles', 'plate')->where(fn ($q) => $q->where('municipio_id', $mid))->ignore($this->editingId),
            ],
            'prefix' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('vehicles', 'prefix')->where(fn ($q) => $q->where('municipio_id', $mid))->ignore($this->editingId),
            ],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'status_legacy' => ['nullable', 'integer', 'min:0', 'max:255'],
        ]);

        $payload = [
            'municipio_id' => $mid,
            'plate' => $validated['plate'] ?: null,
            'prefix' => $validated['prefix'] ?: null,
            'make' => $validated['make'] ?: null,
            'model' => $validated['model'] ?: null,
            'year' => $validated['year'],
            'device_id' => $validated['device_id'] ?: null,
            'status_legacy' => $validated['status_legacy'],
        ];

        if ($this->editingId !== null) {
            $v = Vehicle::query()->findOrFail($this->editingId);
            $this->authorize('update', $v);
            $v->update($payload);
            $this->message = __('Viatura atualizada.');
        } else {
            $this->authorize('create', Vehicle::class);
            Vehicle::query()->create($payload);
            $this->message = __('Viatura criada.');
        }

        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $this->resetErrorBag();
        $v = Vehicle::query()->findOrFail($id);
        $this->authorize('delete', $v);
        if ($v->shifts()->where('ends_at', '>=', now())->exists()) {
            $this->addError('delete', __('Existem turnos ainda vigentes para esta viatura.'));

            return;
        }
        $v->delete();
        $this->message = __('Viatura excluída.');
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function render(): View
    {
        $mid = $this->municipioId();
        $q = Vehicle::query()->orderBy('prefix');
        if ($mid !== null) {
            $q->where('municipio_id', $mid);
        }

        return view('livewire.operations.vehicle-manage', [
            'scopeMunicipioId' => $mid,
            'vehicles' => $q->get(),
            'operationalMunicipios' => Auth::user()?->isOperationalCentral()
                ? Municipio::query()->where('active', true)->orderBy('razao_social')->get()
                : collect(),
            'traccarDevices' => collect($this->traccarDeviceList),
            'traccarAvailable' => $this->traccarAvailable,
        ]);
    }
}
