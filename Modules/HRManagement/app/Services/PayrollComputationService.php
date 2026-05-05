<?php

declare(strict_types=1);

namespace Modules\HRManagement\Services;

use Illuminate\Support\Facades\DB;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\Employee;
use Modules\HRManagement\Models\PayrollCycle;
use Modules\HRManagement\Models\PayrollItem;
use Modules\HRManagement\Models\PayrollRule;
use Modules\HRManagement\Models\PayrollRuleSet;

final class PayrollComputationService
{
    public function __construct(
        private readonly PayrollComponentBuilderService $componentBuilder,
    ) {}

    public function resolveRuleSetForCycle(Business $business, ?PayrollCycle $cycle = null): ?PayrollRuleSet
    {
        $date = $cycle?->period_end ?? now()->toDateString();

        return $business->payrollRuleSets()
            ->where('is_active', true)
            ->where('effective_from', '<=', $date)
            ->where(static function ($q) use ($date): void {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
            })
            ->orderByDesc('is_default')
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return array{item: PayrollItem, errors: list<string>}
     */
    public function computeEmployee(PayrollCycle $cycle, Employee $employee, array $inputs = []): array
    {
        $ruleSet = $cycle->ruleSet ?: $this->resolveRuleSetForCycle($cycle->business, $cycle);
        if (! $ruleSet) {
            throw new \RuntimeException('No active payroll rule set found for this cycle.');
        }

        $defaultOvertimeRate = round((float) ($employee->basic_salary ?? 0) / 240, 2);
        $ctx = [
            'basic_salary' => (float) ($employee->basic_salary ?? 0),
            'gross_salary' => (float) ($employee->salary ?? 0),
            'overtime_hours' => (float) ($inputs['overtime_hours'] ?? 0),
            'overtime_rate' => (float) ($inputs['overtime_rate'] ?? $defaultOvertimeRate),
            'attendance_days' => (float) ($inputs['attendance_days'] ?? 0),
            'working_days' => (float) ($inputs['working_days'] ?? 0),
            'leave_without_pay_days' => (float) ($inputs['leave_without_pay_days'] ?? 0),
        ];

        $build = $this->componentBuilder->build($ruleSet, $ctx);
        $components = $build['components'];
        $errors = $build['errors'];

        $gross = 0.0;
        $deductions = 0.0;
        $basic = (float) ($employee->basic_salary ?? 0);
        $overtime = 0.0;

        foreach ($components as $c) {
            $amount = (float) ($c['amount'] ?? 0);
            $type = (string) ($c['component_type'] ?? '');
            if (($c['code'] ?? '') === 'BASIC_SALARY') {
                $basic = $amount;
            }
            if (($c['code'] ?? '') === 'OVERTIME') {
                $overtime = $amount;
            }
            if (in_array($type, [PayrollRule::TYPE_EARNING, PayrollRule::TYPE_OVERTIME], true)) {
                $gross += $amount;
            } else {
                $deductions += abs($amount);
            }
        }
        $gross = round($gross, 2);
        $deductions = round($deductions, 2);
        $net = round($gross - $deductions, 2);

        /** @var PayrollItem $item */
        $item = DB::transaction(function () use ($cycle, $employee, $inputs, $components, $errors, $basic, $overtime, $gross, $deductions, $net): PayrollItem {
            $item = PayrollItem::query()->firstOrNew([
                'payroll_cycle_id' => $cycle->id,
                'employee_id' => $employee->id,
            ]);

            $item->fill([
                'status' => $errors === [] ? 'computed' : 'error',
                'basic_salary' => round($basic, 2),
                'overtime_amount' => round($overtime, 2),
                'gross_earnings' => $gross,
                'total_deductions' => $deductions,
                'net_pay' => $net,
                'inputs_json' => $inputs,
                'snapshot_json' => ['errors' => $errors],
            ]);
            $item->save();

            $item->components()->delete();
            foreach ($components as $c) {
                $item->components()->create([
                    'rule_id' => $c['rule_id'] ?? null,
                    'code' => (string) $c['code'],
                    'name' => (string) $c['name'],
                    'component_type' => (string) $c['component_type'],
                    'quantity' => round((float) ($c['quantity'] ?? 1), 4),
                    'rate' => round((float) ($c['rate'] ?? 0), 4),
                    'amount' => round((float) ($c['amount'] ?? 0), 2),
                    'meta_json' => $c['meta_json'] ?? null,
                ]);
            }

            return $item->fresh(['components', 'employee']);
        });

        return ['item' => $item, 'errors' => $errors];
    }

    /**
     * @return array{computed: int, errors: list<string>}
     */
    public function computeCycle(PayrollCycle $cycle): array
    {
        $employees = $cycle->business->employees()->with(['employeeAllowances'])->get();
        $errorBag = [];
        $count = 0;
        foreach ($employees as $employee) {
            $result = $this->computeEmployee($cycle, $employee);
            foreach ($result['errors'] as $err) {
                $errorBag[] = $employee->full_name.': '.$err;
            }
            $count++;
        }

        $cycle->forceFill([
            'status' => $errorBag === [] ? PayrollCycle::STATUS_COMPUTED : PayrollCycle::STATUS_DRAFT,
            'computed_at' => now(),
        ])->save();

        return ['computed' => $count, 'errors' => $errorBag];
    }

    public function finalizeCycle(PayrollCycle $cycle, int $byUserId): void
    {
        $hasErrors = $cycle->items()->where('status', 'error')->exists();
        if ($hasErrors) {
            throw new \RuntimeException('Cannot finalize payroll cycle with errored items.');
        }
        if (! $cycle->items()->exists()) {
            throw new \RuntimeException('Cannot finalize payroll cycle without computed items.');
        }

        $cycle->forceFill([
            'status' => PayrollCycle::STATUS_FINALIZED,
            'finalized_at' => now(),
            'finalized_by_user_id' => $byUserId,
        ])->save();
    }
}
