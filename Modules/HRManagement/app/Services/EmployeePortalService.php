<?php

declare(strict_types=1);

namespace Modules\HRManagement\Services;

use App\Models\User;
use Modules\HRManagement\Models\Employee;

class EmployeePortalService
{
    /**
     * Resolve linked employee, or auto-link when the user's email matches employee personal_email (case-insensitive).
     */
    public function linkAndResolve(User $user): ?Employee
    {
        $linked = Employee::findForPortalUser($user);
        if ($linked !== null) {
            return $linked;
        }

        $email = strtolower(trim($user->email));
        if ($email === '') {
            return null;
        }

        $match = Employee::query()
            ->whereNull('user_id')
            ->whereRaw('LOWER(personal_email) = ?', [$email])
            ->orderBy('id')
            ->first();

        if ($match === null) {
            return null;
        }

        $match->forceFill(['user_id' => $user->id])->save();

        return $match->fresh();
    }

    public function requireEmployee(User $user): Employee
    {
        $employee = $this->linkAndResolve($user);
        if ($employee === null) {
            abort(403, 'No employee profile for this account.');
        }

        return $employee;
    }
}
