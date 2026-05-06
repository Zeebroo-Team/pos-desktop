<?php

namespace Modules\Transaction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Account\Models\Account;
use Modules\Account\Models\Bill;
use Modules\Account\Models\Loan;
use Modules\Account\Models\Rental;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\PayrollCycle;

class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';

    protected $fillable = [
        'business_id',
        'user_id',
        'transactionable_type',
        'transactionable_id',
        'deduct_account_id',
        'occurrence_date',
        'period_number',
        'amount',
        'currency',
        'cadence_snapshot',
        'periods_total_snapshot',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_date' => 'date',
            'period_number' => 'integer',
            'amount' => 'decimal:2',
            'periods_total_snapshot' => 'integer',
            'meta' => 'array',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function deductAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'deduct_account_id');
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function sourceKindLabel(): string
    {
        $subject = $this->transactionable;
        if ($subject instanceof Loan) {
            return 'Loan';
        }
        if ($subject instanceof Rental) {
            return 'Rental';
        }
        if ($subject instanceof Bill) {
            return 'Bill';
        }
        if ($subject instanceof PayrollCycle) {
            return 'Payroll';
        }

        return $this->transactionable_type
            ? class_basename($this->transactionable_type)
            : '—';
    }

    public function sourceTitle(): string
    {
        $subject = $this->transactionable;
        if ($subject instanceof Loan) {
            $name = trim((string) $subject->name);

            return $name !== '' ? $name : ('Loan #'.$subject->getKey());
        }
        if ($subject instanceof Rental) {
            $parts = array_filter([
                trim((string) $subject->purpose),
                trim((string) $subject->property_type),
            ]);

            return $parts !== [] ? implode(' · ', $parts) : ('Rental #'.$subject->getKey());
        }
        if ($subject instanceof Bill) {
            $name = trim((string) $subject->name);

            return $name !== '' ? $name : ('Bill #'.$subject->getKey());
        }
        if ($subject instanceof PayrollCycle) {
            $name = trim((string) $subject->name);
            $period = $subject->year.'-'.str_pad((string) $subject->month, 2, '0', STR_PAD_LEFT);

            return $name !== '' ? $name.' · '.$period : ('Payroll #'.$subject->getKey());
        }

        if ($subject !== null) {
            return class_basename($subject::class).' #'.$subject->getKey();
        }

        return '—';
    }

    public function counterpartyBankName(): ?string
    {
        $subject = $this->transactionable;
        if ($subject instanceof Loan) {
            return $subject->bank?->name;
        }

        return null;
    }

    public function borrowedPrincipalSnapshot(): ?float
    {
        $raw = $this->meta['borrowed_principal_snapshot'] ?? null;

        return $raw !== null ? (float) $raw : null;
    }
}
