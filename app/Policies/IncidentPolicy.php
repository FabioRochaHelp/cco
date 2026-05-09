<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

final class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasOperationalAbility('dispatch.view');
    }

    public function view(User $user, Incident $incident): bool
    {
        return $this->viewOperational($user, $incident);
    }

    public function viewOperational(User $user, Incident $incident): bool
    {
        if (! $user->hasOperationalAbility('dispatch.view')) {
            return false;
        }

        if ($incident->municipio_id === null) {
            return true;
        }

        return $user->canAccessOperationalMunicipio((int) $incident->municipio_id);
    }

    public function dispatchUnit(User $user, Incident $incident): bool
    {
        if (! $user->hasOperationalAbility('dispatch.assign_unit')) {
            return false;
        }

        if ($incident->municipio_id === null) {
            return true;
        }

        return $user->canAccessOperationalMunicipio((int) $incident->municipio_id);
    }

    public function advanceStage(User $user, Incident $incident): bool
    {
        if (! $user->hasOperationalAbility('incident.advance_stage')) {
            return false;
        }

        if ($incident->municipio_id === null) {
            return false;
        }

        return $user->canAccessOperationalMunicipio((int) $incident->municipio_id);
    }

    public function releaseUnit(User $user, Incident $incident): bool
    {
        if (! $user->hasOperationalAbility('incident.close')) {
            return false;
        }

        if ($incident->municipio_id === null) {
            return false;
        }

        return $user->canAccessOperationalMunicipio((int) $incident->municipio_id);
    }

    public function createOperational(User $user, ?int $municipioId = null): bool
    {
        if (! $user->hasOperationalAbility('incident.create')) {
            return false;
        }

        if ($municipioId === null) {
            return true;
        }

        return $user->canAccessOperationalMunicipio($municipioId);
    }
}
