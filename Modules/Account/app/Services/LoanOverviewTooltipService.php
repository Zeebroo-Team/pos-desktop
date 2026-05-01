<?php

namespace Modules\Account\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\Account\Models\Loan;
use Modules\Business\Models\Business;

class LoanOverviewTooltipService
{
    public function forUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $business = Business::currentForNavbar($user);
        if (!$business) {
            return null;
        }

        $currency = (string) (get_settings('business.currency', '', $business) ?: '');

        $loans = Loan::query()
            ->with('bank')
            ->where('business_id', $business->id)
            ->latest()
            ->get();

        $recurringLabels = Loan::recurringTypes();
        $rateTypeLabels = Loan::interestRateTypes();

        if ($loans->isEmpty()) {
            return [
                'hasLoans' => false,
                'businessName' => $business->name,
                'loanCount' => 0,
                'loans' => [],
                'totalApproxMonthly' => 0,
                'formattedTotalMonthly' => '0.00',
                'currency' => $currency,
            ];
        }

        $rows = [];
        $totalMonthly = 0.0;

        foreach ($loans as $loan) {
            $n = $this->resolvePeriodCount($loan);
            $periodsSource = $this->periodSourceLabel($loan, $n);
            $payment = $this->paymentPerPeriod($loan, $n);
            $monthlyApprox = $this->approxMonthlyEquivalent($payment, $loan->recurring_type);
            $totalMonthly += $monthlyApprox;

            $ratePct = number_format((float) $loan->interest_rate, 4, '.', '');
            $ratePct = rtrim(rtrim($ratePct, '0'), '.');

            $rows[] = [
                'name' => $loan->name,
                'bankName' => $loan->bank?->name ?? '—',
                'principalFormatted' => number_format((float) $loan->borrowed_amount, 2, '.', ''),
                'rateTypeLabel' => $rateTypeLabels[$loan->interest_rate_type] ?? $loan->interest_rate_type,
                'rateDisplay' => $ratePct . ($loan->interest_rate_type === Loan::INTEREST_RATE_PERCENTAGE ? '%' : ''),
                'cadenceLabel' => $recurringLabels[$loan->recurring_type] ?? $loan->recurring_type,
                'periods' => $n,
                'periodsSource' => $periodsSource,
                'installmentFormatted' => number_format($payment, 2, '.', ''),
                'approxMonthlyFormatted' => number_format($monthlyApprox, 2, '.', ''),
                'firstDue' => $loan->first_installment_due_date?->format('M j, Y'),
                'ending' => $loan->loan_ending_date?->format('M j, Y'),
            ];
        }

        return [
            'hasLoans' => true,
            'businessName' => $business->name,
            'loanCount' => count($rows),
            'loans' => $rows,
            'totalApproxMonthly' => round($totalMonthly, 2),
            'formattedTotalMonthly' => number_format($totalMonthly, 2, '.', ''),
            'locale' => app()->getLocale(),
            'currency' => $currency,
        ];
    }

    /**
     * Compact figures for Loan Management list cards (same amortization rules as preview).
     *
     * @return array{period_count:int, period_source:string, payment_per_period:float, payment_formatted:string, approx_monthly:float, approx_monthly_formatted:string, cadence_label:string}
     */
    public function summarizeLoan(Loan $loan): array
    {
        $n = $this->resolvePeriodCount($loan);
        $payment = $this->paymentPerPeriod($loan, $n);
        $monthlyApprox = $this->approxMonthlyEquivalent($payment, $loan->recurring_type);
        $cadenceLabels = Loan::recurringTypes();

        return [
            'period_count' => $n,
            'period_source' => $this->periodSourceLabel($loan, $n),
            'payment_per_period' => $payment,
            'payment_formatted' => number_format($payment, 2, '.', ','),
            'approx_monthly' => $monthlyApprox,
            'approx_monthly_formatted' => number_format($monthlyApprox, 2, '.', ','),
            'cadence_label' => $cadenceLabels[$loan->recurring_type] ?? $loan->recurring_type,
        ];
    }

    /** Payment per scheduled period using same nominal-APR amortization vs flat totals as loan management preview (PHP side). */
    public function paymentPerPeriod(Loan $loan, int $n): float
    {
        $principal = (float) $loan->borrowed_amount;
        if ($n <= 0) {
            return 0.0;
        }

        if ($loan->interest_rate_type === Loan::INTEREST_RATE_PERCENTAGE) {
            $i = $this->periodicInterestDecimal((float) $loan->interest_rate, $loan->recurring_type);
            return $this->amortPayment($principal, $i, $n);
        }

        $flatTotalInterest = $principal * ((float) $loan->interest_rate / 100);
        $total = $principal + $flatTotalInterest;

        return $total / $n;
    }

    private function amortPayment(float $principal, float $i, int $n): float
    {
        if ($n <= 0) {
            return 0.0;
        }

        if ($i <= 0.0) {
            return $principal / $n;
        }

        $pow = pow(1 + $i, $n);

        return ($principal * $i * $pow) / ($pow - 1);
    }

    /** Nominal APR (%) mapped to periodic rate for cadence */
    private function periodicInterestDecimal(float $annualPercent, string $recurringType): float
    {
        $periodsPerYear = $this->periodsPerYear($recurringType);
        if ($periodsPerYear <= 0) {
            return 0.0;
        }

        return ($annualPercent / 100.0) / $periodsPerYear;
    }

    private function periodsPerYear(string $recurring): int
    {
        return match ($recurring) {
            Loan::RECURRING_PER_MONTH => 12,
            Loan::RECURRING_PER_DAY => 365,
            Loan::RECURRING_PER_YEAR => 1,
            default => 12,
        };
    }

    /** Rough monthly budgeting equivalent (daily×30, yearly÷12). */
    private function approxMonthlyEquivalent(float $paymentPerPeriod, string $recurring): float
    {
        return match ($recurring) {
            Loan::RECURRING_PER_MONTH => $paymentPerPeriod,
            Loan::RECURRING_PER_DAY => $paymentPerPeriod * 30.0,
            Loan::RECURRING_PER_YEAR => $paymentPerPeriod / 12.0,
            default => $paymentPerPeriod,
        };
    }

    private function resolvePeriodCount(Loan $loan): int
    {
        $first = $loan->first_installment_due_date;
        $last = $loan->loan_ending_date;

        if ($first instanceof Carbon && $last instanceof Carbon) {
            $count = $this->inclusivePeriodCount($first, $last, $loan->recurring_type);
            if ($count > 0) {
                return $count;
            }
        }

        return $this->assumedPeriodCount($loan->recurring_type);
    }

    private function periodSourceLabel(Loan $loan, int $resolvedN): string
    {
        $first = $loan->first_installment_due_date;
        $last = $loan->loan_ending_date;
        if ($first && $last) {
            $cal = $this->inclusivePeriodCount($first, $last, $loan->recurring_type);
            if ($cal > 0) {
                return 'from first & end dates';
            }
        }

        return 'default assumed term (' . $resolvedN . ' periods)';
    }

    private function assumedPeriodCount(string $recur): int
    {
        return match ($recur) {
            Loan::RECURRING_PER_MONTH => 12,
            Loan::RECURRING_PER_DAY => 30,
            Loan::RECURRING_PER_YEAR => 5,
            default => 12,
        };
    }

    private function inclusivePeriodCount(Carbon $first, Carbon $end, string $recur): int
    {
        $start = $first->copy()->startOfDay();
        $boundary = $end->copy()->startOfDay();
        if ($boundary->lt($start)) {
            return 0;
        }

        $n = 0;
        $d = $start->copy();

        while ($d->lte($boundary)) {
            $n++;
            match ($recur) {
                Loan::RECURRING_PER_DAY => $d->addDay(),
                Loan::RECURRING_PER_MONTH => $d->addMonth(),
                Loan::RECURRING_PER_YEAR => $d->addYear(),
                default => $d->addMonth(),
            };
        }

        return $n;
    }
}
