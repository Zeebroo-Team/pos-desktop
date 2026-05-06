<?php

declare(strict_types=1);

namespace Modules\HRManagement\Services;

use Modules\Business\Models\Business;
use Modules\HRManagement\Models\Employee;
use Modules\HRManagement\Models\PayrollCycle;
use Modules\HRManagement\Models\PayrollItem;

/** Snapshot metrics for the HR hub dashboard. */
final class HrHubSummaryService
{
    /** @return array<string, mixed> */
    public function forBusiness(Business $business): array
    {
        $currency = trim((string) get_settings('business.currency', '', $business));

        $departmentCount = $business->departments()->count();
        $employeeCount = $business->employees()->count();
        $designationCount = $business->jobTitles()->count();
        $holidayCount = $business->hrHolidays()->count();

        $salarySum = (float) (Employee::query()
            ->where('business_id', $business->id)
            ->whereNotNull('salary')
            ->sum('salary') ?? 0);

        $basicSum = (float) (Employee::query()
            ->where('business_id', $business->id)
            ->whereNotNull('basic_salary')
            ->sum('basic_salary') ?? 0);

        $employeesWithSalary = Employee::query()
            ->where('business_id', $business->id)
            ->whereNotNull('salary')
            ->count();

        $employeesMissingSalary = $employeeCount > 0
            ? Employee::query()
                ->where('business_id', $business->id)
                ->whereNull('salary')
                ->count()
            : 0;

        $annualLeaveDays = get_settings('hr.leave.annual_days', null, $business);
        $casualLeaveDays = get_settings('hr.leave.casual_days', null, $business);
        $workdaysPerMonth = get_settings('hr.workdays_per_month', null, $business);

        $latestCycle = PayrollCycle::query()
            ->where('business_id', $business->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->first();

        $latestPayrollRun = null;
        if ($latestCycle !== null) {
            $agg = PayrollItem::query()
                ->where('payroll_cycle_id', $latestCycle->id)
                ->selectRaw('COALESCE(SUM(gross_earnings), 0) as sum_gross, COALESCE(SUM(net_pay), 0) as sum_net, COUNT(*) as n')
                ->first();
            $latestPayrollRun = [
                'cycle_id' => $latestCycle->id,
                'name' => $latestCycle->name,
                'year' => $latestCycle->year,
                'month' => $latestCycle->month,
                'status' => $latestCycle->status,
                'employee_rows' => $agg ? (int) $agg->n : 0,
                'total_gross' => $agg ? round((float) $agg->sum_gross, 2) : 0.0,
                'total_net' => $agg ? round((float) $agg->sum_net, 2) : 0.0,
            ];
        }

        return [
            'currency' => $currency,
            'department_count' => $departmentCount,
            'employee_count' => $employeeCount,
            'designation_count' => $designationCount,
            'holiday_count' => $holidayCount,
            'monthly_salary_total' => $salarySum,
            'monthly_basic_total' => $basicSum,
            'employees_with_salary' => $employeesWithSalary,
            'employees_missing_salary' => $employeesMissingSalary,
            'latest_payroll_run' => $latestPayrollRun,
            'annual_leave_days' => is_numeric($annualLeaveDays) ? (int) $annualLeaveDays : null,
            'casual_leave_days' => is_numeric($casualLeaveDays) ? (int) $casualLeaveDays : null,
            'workdays_per_month' => is_numeric($workdaysPerMonth) ? (int) $workdaysPerMonth : null,
        ];
    }
}
