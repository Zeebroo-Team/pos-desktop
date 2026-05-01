<?php

namespace Modules\Account\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Account\Models\Loan;
use Modules\Business\Models\Business;

class LoanService
{
    public function listForBusiness(?Business $business): Collection
    {
        if (!$business) {
            return new Collection([]);
        }

        return Loan::with(['bank', 'deductAccount.bank', 'deductAccount.bankType'])
            ->where('business_id', $business->id)
            ->latest()
            ->get();
    }

    public function create(User $user, Business $business, array $data): Loan
    {
        $data['user_id'] = $user->id;
        $data['business_id'] = $business->id;

        return Loan::create($data);
    }

    public function deleteForUser(User $user, Loan $loan): bool
    {
        $businessIds = $user->businesses()->pluck('id')->all();
        if ($loan->user_id !== $user->id || !in_array($loan->business_id, $businessIds, true)) {
            return false;
        }

        $loan->delete();

        return true;
    }
}
