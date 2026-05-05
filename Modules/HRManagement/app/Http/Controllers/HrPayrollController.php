<?php

namespace Modules\HRManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\PayrollCycle;
use Modules\HRManagement\Models\PayrollItem;
use Modules\HRManagement\Models\PayrollRule;
use Modules\HRManagement\Models\PayrollRuleSet;
use Modules\HRManagement\Services\HrPayrollSettingsService;
use Modules\HRManagement\Services\PayrollComputationService;
use Modules\Settings\Services\SettingsService;

class HrPayrollController extends Controller
{
    private const TEMPLATE_SL_STANDARD = 'sri_lankan_employee_standard';

    public function __construct(
        private readonly HrPayrollSettingsService $hrPayrollSettings,
        private readonly PayrollComputationService $payrollComputation,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);
        $ruleSets = $this->loadRuleSets($business);

        $cycles = $business->payrollCycles()
            ->with(['ruleSet', 'finalizedBy'])
            ->withCount('items')
            ->get();

        return view('hrmanagement::payroll.index', [
            'business' => $business,
            'ruleSets' => $ruleSets,
            'cycles' => $cycles,
            'defaultRuleSetId' => optional($this->payrollComputation->resolveRuleSetForCycle($business))->id,
        ]);
    }

    public function regionalTemplate(Request $request): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);

        return view('hrmanagement::payroll.regional-template', [
            'business' => $business,
            ...$this->payrollTemplateViewData($business),
        ]);
    }

    public function ruleSets(Request $request): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);
        $ruleSets = $this->loadRuleSets($business);
        $ruleSets->load([
            'rules' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        return view('hrmanagement::payroll.rule-sets', [
            'business' => $business,
            'ruleSets' => $ruleSets,
        ]);
    }

    public function applyTemplate(Request $request): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        $validated = $request->validate([
            'template' => ['required', 'string'],
        ]);
        $template = (string) $validated['template'];

        if ($template !== self::TEMPLATE_SL_STANDARD) {
            return back()->withErrors(['template' => __('Unsupported payroll template.')]);
        }

        $ruleSet = PayrollRuleSet::query()
            ->where('business_id', $business->id)
            ->where('name', 'Sri Lanka payroll starter')
            ->first();

        if (! $ruleSet) {
            $ruleSet = $this->createStarterRuleSet($business);
        }

        $ruleSet->forceFill([
            'currency' => (string) (get_settings('business.currency', 'LKR', $business) ?: 'LKR'),
            'effective_from' => now()->toDateString(),
            'is_default' => true,
            'is_active' => true,
            'notes' => 'Template: Sri Lankan employee standard',
        ])->save();

        $ruleSet->rules()->delete();
        $this->attachSriLankanStandardRules($ruleSet);

        $this->settings->setMany($business, [
            'hr.payroll.template' => self::TEMPLATE_SL_STANDARD,
            'hr.payroll.cycle.default_name' => 'Monthly Payroll',
            'hr.payroll.cycle.default_working_days' => 26,
            'hr.payroll.statutory.epf.employee.percent' => 8,
            'hr.payroll.statutory.epf.employer.percent' => 12,
            'hr.payroll.statutory.etf.employer.percent' => 3,
            'hr.payroll.statutory.apit.enabled' => true,
        ]);

        return redirect()->route('hr.payroll.regional-template')->with('status', __('Sri Lankan employee standard template applied. EPF, ETF, APIT, and payroll defaults are configured.'));
    }

    public function storeRuleSet(Request $request): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'currency' => ['nullable', 'string', 'max:16'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_default' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $ruleSet = new PayrollRuleSet;
        $ruleSet->fill([
            'business_id' => $business->id,
            'name' => $validated['name'],
            'currency' => (string) ($validated['currency'] ?? 'LKR'),
            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'is_default' => (bool) ($validated['is_default'] ?? false),
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);
        $ruleSet->save();

        return redirect()->route('hr.payroll.rule-sets.index')->with('status', __('Payroll rule set created.'));
    }

    public function storeRule(Request $request, PayrollRuleSet $ruleSet): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $ruleSet->business_id !== (int) $business->id, 404);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:140'],
            'component_type' => ['required', 'string', 'max:32'],
            'calculation_mode' => ['required', 'string', 'max:24'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_statutory' => ['nullable', 'boolean'],
            'config_json' => ['nullable', 'string'],
        ]);

        $config = [];
        if (isset($validated['config_json']) && trim((string) $validated['config_json']) !== '') {
            $decoded = json_decode((string) $validated['config_json'], true);
            if (! is_array($decoded)) {
                return back()->withErrors(['config_json' => __('Config JSON must be valid JSON object/array.')])->withInput();
            }
            $config = $decoded;
        }

        $ruleSet->rules()->create([
            'code' => strtoupper((string) $validated['code']),
            'name' => $validated['name'],
            'component_type' => $validated['component_type'],
            'calculation_mode' => $validated['calculation_mode'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_taxable' => (bool) ($validated['is_taxable'] ?? false),
            'is_statutory' => (bool) ($validated['is_statutory'] ?? false),
            'is_active' => true,
            'config_json' => $config,
        ]);

        return redirect()->route('hr.payroll.rule-sets.index')->with('status', __('Payroll rule added.'));
    }

    public function updateRule(Request $request, PayrollRule $payrollRule): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $payrollRule->ruleSet?->business_id !== (int) $business->id, 404);

        $validated = $request->validate([
            'config_json' => ['required', 'string'],
        ]);

        $decoded = json_decode((string) $validated['config_json'], true);
        if (! is_array($decoded)) {
            return back()->withErrors(['config_json' => __('Config JSON must be valid JSON object/array.')])->withInput();
        }

        $payrollRule->config_json = $decoded;
        $payrollRule->save();

        return redirect()->route('hr.payroll.rule-sets.index')->with('status', __('Rule configuration updated.'));
    }

    public function storeCycle(Request $request): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        $validated = $request->validate([
            'rule_set_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:140'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $ruleSet = PayrollRuleSet::query()
            ->where('business_id', $business->id)
            ->whereKey((int) $validated['rule_set_id'])
            ->firstOrFail();

        PayrollCycle::query()->create([
            'business_id' => $business->id,
            'rule_set_id' => $ruleSet->id,
            'name' => $validated['name'],
            'year' => (int) $validated['year'],
            'month' => (int) $validated['month'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => PayrollCycle::STATUS_DRAFT,
        ]);

        return redirect()->route('hr.payroll.index')->with('status', __('Payroll cycle created.'));
    }

    public function showCycle(Request $request, PayrollCycle $cycle): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);

        $cycle->load(['ruleSet', 'items.employee', 'items.components.rule']);

        $summary = $this->buildCycleSummary($cycle);

        return view('hrmanagement::payroll.cycle', [
            'business' => $business,
            'cycle' => $cycle,
            'summary' => $summary,
        ]);
    }

    public function generateSalarySheet(Request $request, PayrollCycle $cycle): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);

        if ($cycle->items()->count() === 0 || ! $cycle->isFinalized()) {
            $result = $this->payrollComputation->computeCycle($cycle->fresh('business'));
            if ($result['errors'] !== []) {
                return back()->with('warning', __('Salary sheet generated with some computation warnings. Please review this cycle.'));
            }
        }

        return redirect()
            ->route('hr.payroll.cycles.salary-sheet', $cycle)
            ->with('status', __('Monthly salary sheet generated for all employees.'));
    }

    public function showSalarySheet(Request $request, PayrollCycle $cycle): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);

        $cycle->load(['ruleSet', 'items.employee', 'items.components']);
        $sheetRows = $this->buildSalarySheetRows($cycle);
        $summary = $this->buildCycleSummary($cycle);

        return view('hrmanagement::payroll.salary-sheet', [
            'business' => $business,
            'cycle' => $cycle,
            'rows' => $sheetRows,
            'summary' => $summary,
        ]);
    }

    public function computeCycle(Request $request, PayrollCycle $cycle): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);
        if ($cycle->isFinalized()) {
            return back()->withErrors(['cycle' => __('Finalized payroll cycle cannot be recomputed.')]);
        }

        $result = $this->payrollComputation->computeCycle($cycle->fresh('business'));
        if ($result['errors'] !== []) {
            return back()->with('warning', __('Computed with some errors. Review cycle details.'));
        }

        return back()->with('status', __('Payroll cycle computed for :count employees.', ['count' => $result['computed']]));
    }

    public function recomputeEmployee(Request $request, PayrollCycle $cycle, PayrollItem $item): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);
        abort_if((int) $item->payroll_cycle_id !== (int) $cycle->id, 404);
        if ($cycle->isFinalized()) {
            return back()->withErrors(['cycle' => __('Finalized payroll cycle cannot be changed.')]);
        }

        $validated = $request->validate([
            'overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'attendance_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'working_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
            'leave_without_pay_days' => ['nullable', 'numeric', 'min:0', 'max:31'],
        ]);

        $this->payrollComputation->computeEmployee($cycle, $item->employee, $validated);

        return back()->with('status', __('Employee payroll recomputed.'));
    }

    public function finalizeCycle(Request $request, PayrollCycle $cycle): RedirectResponse
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);

        try {
            $this->payrollComputation->finalizeCycle($cycle->fresh('items'), (int) $request->user()->id);
        } catch (\Throwable $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }

        return back()->with('status', __('Payroll cycle finalized.'));
    }

    public function showPayslip(Request $request, PayrollCycle $cycle, PayrollItem $item): RedirectResponse|View
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);
        abort_if((int) $item->payroll_cycle_id !== (int) $cycle->id, 404);
        $item->load(['employee', 'components']);

        return view('hrmanagement::payroll.payslip', [
            'business' => $business,
            'cycle' => $cycle,
            'item' => $item,
        ]);
    }

    public function downloadPayslip(Request $request, PayrollCycle $cycle, PayrollItem $item)
    {
        $business = $this->resolveBusiness($request);
        abort_if((int) $cycle->business_id !== (int) $business->id, 404);
        abort_if((int) $item->payroll_cycle_id !== (int) $cycle->id, 404);

        $item->load(['employee', 'components']);
        $html = view('hrmanagement::payroll.payslip', [
            'business' => $business,
            'cycle' => $cycle,
            'item' => $item,
            'isDownload' => true,
        ])->render();

        $filename = sprintf(
            'payslip-%s-%s-%s.html',
            $item->employee?->employee_id ?: 'employee',
            $cycle->year,
            str_pad((string) $cycle->month, 2, '0', STR_PAD_LEFT)
        );

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    /**
     * @return array{payrollTemplateCards: list<array{key: string, title: string, description: string, highlights: list<string>}>, selectedPayrollTemplate: string}
     */
    private function payrollTemplateViewData(Business $business): array
    {
        $selected = (string) ($this->settings->get($business, 'hr.payroll.template', self::TEMPLATE_SL_STANDARD) ?: self::TEMPLATE_SL_STANDARD);

        return [
            'payrollTemplateCards' => [
                [
                    'key' => self::TEMPLATE_SL_STANDARD,
                    'title' => __('Sri Lankan employee standard'),
                    'description' => __('Regional defaults for Sri Lanka payroll: statutory components, tax slabs, and starter cycle presets. Applying replaces rules on the linked starter rule set.'),
                    'highlights' => [
                        __('EPF employee 8%, employer 12%; ETF employer 3%'),
                        __('APIT slabs on taxable earnings'),
                        __('Overtime rate reference + monthly cycle defaults'),
                    ],
                ],
            ],
            'selectedPayrollTemplate' => $selected,
        ];
    }

    private function resolveBusiness(Request $request): Business
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            abort(403);
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);

        return $business;
    }

    private function createStarterRuleSet(Business $business): PayrollRuleSet
    {
        return PayrollRuleSet::query()->create([
            'business_id' => $business->id,
            'name' => 'Sri Lanka payroll starter',
            'currency' => (string) (get_settings('business.currency', 'LKR', $business) ?: 'LKR'),
            'effective_from' => now()->toDateString(),
            'is_default' => true,
            'is_active' => true,
            'notes' => 'Editable starter template with EPF/ETF/APIT style components.',
        ]);
    }

    private function attachSriLankanStandardRules(PayrollRuleSet $ruleSet): void
    {
        if ($ruleSet->rules()->exists()) {
            return;
        }

        $ruleSet->rules()->createMany([
            [
                'code' => 'EPF_EMPLOYEE',
                'name' => 'EPF employee contribution',
                'component_type' => PayrollRule::TYPE_STATUTORY,
                'calculation_mode' => PayrollRule::MODE_PERCENTAGE,
                'sort_order' => 10,
                'is_taxable' => false,
                'is_statutory' => true,
                'is_active' => true,
                'config_json' => ['base_field' => 'basic_salary', 'percent' => 8],
            ],
            [
                'code' => 'ETF_EMPLOYER',
                'name' => 'ETF employer contribution (tracking)',
                'component_type' => PayrollRule::TYPE_STATUTORY,
                'calculation_mode' => PayrollRule::MODE_PERCENTAGE,
                'sort_order' => 20,
                'is_taxable' => false,
                'is_statutory' => true,
                'is_active' => true,
                'config_json' => ['base_field' => 'basic_salary', 'percent' => 3],
            ],
            [
                'code' => 'EPF_EMPLOYER',
                'name' => 'EPF employer contribution (tracking)',
                'component_type' => PayrollRule::TYPE_STATUTORY,
                'calculation_mode' => PayrollRule::MODE_PERCENTAGE,
                'sort_order' => 25,
                'is_taxable' => false,
                'is_statutory' => true,
                'is_active' => true,
                'config_json' => ['base_field' => 'basic_salary', 'percent' => 12],
            ],
            [
                'code' => 'APIT',
                'name' => 'APIT',
                'component_type' => PayrollRule::TYPE_DEDUCTION,
                'calculation_mode' => PayrollRule::MODE_SLAB,
                'sort_order' => 30,
                'is_taxable' => false,
                'is_statutory' => true,
                'is_active' => true,
                'config_json' => [
                    'input_field' => 'taxable_earnings',
                    'slabs' => [
                        ['from' => 0, 'to' => 100000, 'percent' => 0],
                        ['from' => 100000, 'to' => 141667, 'percent' => 6],
                        ['from' => 141667, 'to' => 183333, 'percent' => 12],
                        ['from' => 183333, 'to' => 225000, 'percent' => 18],
                        ['from' => 225000, 'to' => 266667, 'percent' => 24],
                        ['from' => 266667, 'to' => 308333, 'percent' => 30],
                        ['from' => 308333, 'to' => null, 'percent' => 36],
                    ],
                ],
            ],
            [
                'code' => 'OT_RATE_FORMULA',
                'name' => 'Overtime rate helper (reference)',
                'component_type' => PayrollRule::TYPE_OVERTIME,
                'calculation_mode' => PayrollRule::MODE_FORMULA,
                'sort_order' => 40,
                'is_taxable' => true,
                'is_statutory' => false,
                'is_active' => true,
                'config_json' => ['formula' => '(basic_salary/26/8)*1.5'],
            ],
        ]);
    }

    private function loadRuleSets(Business $business)
    {
        $ruleSets = $business->payrollRuleSets()->withCount('rules')->get();
        if ($ruleSets->isEmpty()) {
            $seed = $this->createStarterRuleSet($business);
            $this->attachSriLankanStandardRules($seed);
            $ruleSets = $business->payrollRuleSets()->withCount('rules')->get();
        }

        return $ruleSets;
    }

    /**
     * @return array{total_gross: float,total_deductions: float,total_net: float,epf: float,etf: float,apit: float,employee_rows: array<int, array<string, mixed>>}
     */
    private function buildCycleSummary(PayrollCycle $cycle): array
    {
        $items = $cycle->items;
        $totals = [
            'total_gross' => round((float) $items->sum('gross_earnings'), 2),
            'total_deductions' => round((float) $items->sum('total_deductions'), 2),
            'total_net' => round((float) $items->sum('net_pay'), 2),
            'epf' => 0.0,
            'etf' => 0.0,
            'apit' => 0.0,
            'employee_rows' => [],
        ];

        foreach ($items as $item) {
            $row = [
                'item_id' => $item->id,
                'employee_name' => $item->employee?->full_name,
                'employee_id' => $item->employee?->employee_id,
                'net_pay' => round((float) $item->net_pay, 2),
            ];
            $totals['employee_rows'][] = $row;

            foreach ($item->components as $c) {
                $code = strtoupper((string) $c->code);
                $amount = abs((float) $c->amount);
                if ($code === 'EPF_EMPLOYEE') {
                    $totals['epf'] = round($totals['epf'] + $amount, 2);
                } elseif ($code === 'ETF_EMPLOYER') {
                    $totals['etf'] = round($totals['etf'] + $amount, 2);
                } elseif ($code === 'APIT') {
                    $totals['apit'] = round($totals['apit'] + $amount, 2);
                }
            }
        }

        return $totals;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSalarySheetRows(PayrollCycle $cycle): array
    {
        return $cycle->items->map(function (PayrollItem $item): array {
            $epfEmployee = 0.0;
            $epfEmployer = 0.0;
            $etfEmployer = 0.0;
            $apit = 0.0;

            foreach ($item->components as $component) {
                $code = strtoupper((string) $component->code);
                $amount = abs((float) $component->amount);
                if ($code === 'EPF_EMPLOYEE') {
                    $epfEmployee = round($epfEmployee + $amount, 2);
                } elseif ($code === 'EPF_EMPLOYER') {
                    $epfEmployer = round($epfEmployer + $amount, 2);
                } elseif ($code === 'ETF_EMPLOYER') {
                    $etfEmployer = round($etfEmployer + $amount, 2);
                } elseif ($code === 'APIT') {
                    $apit = round($apit + $amount, 2);
                }
            }

            return [
                'employee_id' => (string) ($item->employee?->employee_id ?? ''),
                'employee_name' => (string) ($item->employee?->full_name ?? __('Unknown employee')),
                'basic_salary' => round((float) $item->basic_salary, 2),
                'overtime_amount' => round((float) $item->overtime_amount, 2),
                'gross_earnings' => round((float) $item->gross_earnings, 2),
                'total_deductions' => round((float) $item->total_deductions, 2),
                'net_pay' => round((float) $item->net_pay, 2),
                'epf_employee' => $epfEmployee,
                'epf_employer' => $epfEmployer,
                'etf_employer' => $etfEmployer,
                'apit' => $apit,
                'status' => ucfirst((string) $item->status),
            ];
        })->values()->all();
    }
}
