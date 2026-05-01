<?php

namespace Modules\Account\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Business\Models\Business;

class Loan extends Model
{
    public const INTEREST_RATE_PERCENTAGE = 'percentage';

    public const INTEREST_RATE_FLAT = 'flat';

    public const RECURRING_PER_DAY = 'per_day';

    public const RECURRING_PER_MONTH = 'per_month';

    public const RECURRING_PER_YEAR = 'per_year';

    protected $fillable = [
        'user_id',
        'business_id',
        'bank_id',
        'deduct_account_id',
        'name',
        'description',
        'borrowed_amount',
        'interest_rate_type',
        'interest_rate',
        'recurring_type',
        'first_installment_due_date',
        'loan_ending_date',
        'remind_before_days',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'first_installment_due_date' => 'date',
            'loan_ending_date' => 'date',
            'remind_before_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    public function deductAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deduct_account_id');
    }

    public static function interestRateTypes(): array
    {
        return [
            self::INTEREST_RATE_PERCENTAGE => 'Percentage',
            self::INTEREST_RATE_FLAT => 'Flat',
        ];
    }

    public static function recurringTypes(): array
    {
        return [
            self::RECURRING_PER_DAY => 'Per day',
            self::RECURRING_PER_MONTH => 'Per month',
            self::RECURRING_PER_YEAR => 'Per year',
        ];
    }
}
