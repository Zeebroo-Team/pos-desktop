<?php

namespace Modules\HRManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Account\Models\Bank;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\Department;
use Modules\HRManagement\Models\Employee;
use Modules\HRManagement\Models\EmployeeDocument;
use Modules\HRManagement\Models\JobTitle;
use Modules\HRManagement\Services\DepartmentService;
use Modules\HRManagement\Services\EmployeeDocumentService;
use Modules\HRManagement\Services\EmployeeLeaveBalanceService;
use Modules\HRManagement\Services\EmployeeOverviewMetricsService;
use Modules\HRManagement\Services\EmployeeProfilePhotoService;
use Modules\HRManagement\Services\EmployeeService;
use Modules\HRManagement\Services\HrPayrollSettingsService;
use Modules\HRManagement\Services\JobTitleService;

class HrEmployeeController extends Controller
{
    public function __construct(
        private readonly HrPayrollSettingsService $hrPayrollSettings,
        private readonly EmployeeService $employeeService,
        private readonly DepartmentService $departmentService,
        private readonly JobTitleService $jobTitleService,
        private readonly EmployeeOverviewMetricsService $employeeOverviewMetrics,
        private readonly EmployeeLeaveBalanceService $employeeLeaveBalance,
        private readonly EmployeeProfilePhotoService $employeeProfilePhoto,
        private readonly EmployeeDocumentService $employeeDocumentService,
    ) {}

    public function index(Request $request): RedirectResponse|View
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }

        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);

        return view('hrmanagement::employees.index', [
            'business' => $business,
            'employees' => $this->employeeService->listForBusiness($business),
            'departments' => $business->departments()->get(),
            'jobTitles' => $business->jobTitles()->get(),
            'allowanceTypes' => $business->allowanceTypes()->get(),
            'banks' => Bank::query()->orderBy('name')->get(),
            'employmentTypeLabels' => [
                Employee::EMPLOYMENT_FULL_TIME => 'Full-Time',
                Employee::EMPLOYMENT_PART_TIME => 'Part-Time',
                Employee::EMPLOYMENT_CONTRACT => 'Contract',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }

        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);

        $validator = Validator::make($request->all(), $this->employeeFieldRules($business));

        $validator->after(function ($validator) use ($business): void {
            $data = $validator->getData();

            if (! $validator->errors()->has('basic_salary') && ! $validator->errors()->has('salary')) {
                $basic = round((float) ($data['basic_salary'] ?? 0), 2);
                $allowanceRaw = $data['allowances'] ?? [];
                if (! is_array($allowanceRaw)) {
                    $allowanceRaw = [];
                }
                $expected = $basic;
                $allowanceInputsOk = true;
                foreach ($business->allowanceTypes()->pluck('id')->all() as $typeId) {
                    $raw = $allowanceRaw[(string) $typeId] ?? $allowanceRaw[$typeId] ?? null;
                    if ($raw === null || $raw === '') {
                        continue;
                    }
                    if (! is_numeric($raw)) {
                        $validator->errors()->add('allowances.'.$typeId, __('Enter a valid amount for each allowance.'));
                        $allowanceInputsOk = false;

                        continue;
                    }
                    $expected += round(max(0, (float) $raw), 2);
                }
                if ($allowanceInputsOk) {
                    $declared = round((float) ($data['salary'] ?? 0), 2);
                    if (abs($declared - $expected) > 0.02) {
                        $validator->errors()->add(
                            'salary',
                            __('Monthly gross must equal basic salary plus allowance amounts (expected :expected).', [
                                'expected' => number_format($expected, 2, '.', ''),
                            ])
                        );
                    }
                }
            }

            $departmentChoice = isset($data['department_id']) ? (string) $data['department_id'] : '';
            if ($departmentChoice === '') {
                $validator->errors()->add('department_id', __('Choose a department.'));
            } elseif ($departmentChoice === Employee::SELECT_NEW_ROW) {
                $name = trim((string) ($data['new_department_name'] ?? ''));
                if ($name === '') {
                    $validator->errors()->add('new_department_name', __('Enter the new department name.'));
                } elseif (Department::query()->where('business_id', $business->id)->where('name', $name)->exists()) {
                    $validator->errors()->add('new_department_name', __('That department already exists for this business.'));
                }
            } elseif (! ctype_digit($departmentChoice)) {
                $validator->errors()->add('department_id', __('Choose an existing department or add a new one.'));
            } elseif (! Department::query()->where('business_id', $business->id)->whereKey((int) $departmentChoice)->exists()) {
                $validator->errors()->add('department_id', __('That department does not belong to this business.'));
            }

            $jobTitleChoice = isset($data['job_title_id']) ? (string) $data['job_title_id'] : '';
            if ($jobTitleChoice === '') {
                $validator->errors()->add('job_title_id', __('Choose a job title or designation.'));
            } elseif ($jobTitleChoice === Employee::SELECT_NEW_ROW) {
                $name = trim((string) ($data['new_job_title_name'] ?? ''));
                if ($name === '') {
                    $validator->errors()->add('new_job_title_name', __('Enter the new job title or designation.'));
                } elseif (JobTitle::query()->where('business_id', $business->id)->where('name', $name)->exists()) {
                    $validator->errors()->add('new_job_title_name', __('That job title already exists for this business.'));
                }
            } elseif (! ctype_digit($jobTitleChoice)) {
                $validator->errors()->add('job_title_id', __('Choose an existing job title or add a new one.'));
            } elseif (! JobTitle::query()->where('business_id', $business->id)->whereKey((int) $jobTitleChoice)->exists()) {
                $validator->errors()->add('job_title_id', __('That job title does not belong to this business.'));
            }
        });

        /** @throws ValidationException */
        $validated = $validator->validate();

        $employee = DB::transaction(function () use ($business, $validated): Employee {
            $payload = $validated;

            if ((string) $payload['department_id'] === Employee::SELECT_NEW_ROW) {
                $payload['department_id'] = $this->departmentService->create(
                    $business,
                    (string) $payload['new_department_name'],
                )->id;
            } else {
                $payload['department_id'] = (int) $payload['department_id'];
            }

            if ((string) $payload['job_title_id'] === Employee::SELECT_NEW_ROW) {
                $payload['job_title_id'] = $this->jobTitleService->create(
                    $business,
                    (string) $payload['new_job_title_name'],
                )->id;
            } else {
                $payload['job_title_id'] = (int) $payload['job_title_id'];
            }

            unset($payload['new_department_name'], $payload['new_job_title_name'], $payload['profile_photo']);

            return $this->employeeService->create($business, $payload);
        });

        if ($request->hasFile('profile_photo')) {
            $this->employeeProfilePhoto->store($employee, $request->file('profile_photo'));
        }

        return redirect()->route('hr.employees.index')->with('status', __('Employee created.'));
    }

    public function show(Request $request, Employee $employee): RedirectResponse|View
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }

        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);

        $employee->load(['bank', 'department', 'jobTitle', 'employeeAllowances.allowanceType', 'documents', 'leaveRequests']);

        return view('hrmanagement::employees.show', [
            'business' => $business,
            'employee' => $employee,
            'overviewMetrics' => $this->employeeOverviewMetrics->forEmployee($business, $employee),
            'documentCategories' => EmployeeDocument::CATEGORIES,
            'leaveBalanceSummary' => $this->employeeLeaveBalance->yearlySummary($business, $employee),
        ]);
    }

    public function storeProfilePhoto(Request $request, Employee $employee): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);
        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);

        $request->validate([
            'profile_photo' => ['required', 'image', 'max:4096'],
        ]);

        $this->employeeProfilePhoto->store($employee, $request->file('profile_photo'));

        return redirect()->route('hr.employees.show', $employee)->with('status', __('Profile photo updated.'));
    }

    public function destroyProfilePhoto(Request $request, Employee $employee): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);
        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);

        $this->employeeProfilePhoto->delete($employee);

        return redirect()->route('hr.employees.show', $employee)->with('status', __('Profile photo removed.'));
    }

    public function storeDocument(Request $request, Employee $employee): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);
        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);

        $validated = $request->validate([
            'document_category' => ['required', 'string', Rule::in(EmployeeDocument::CATEGORIES)],
            'document_file' => [
                'required',
                'file',
                'max:15360',
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp,txt,rtf,xlsx,xls,ppt,pptx,csv',
            ],
        ]);

        $this->employeeDocumentService->store(
            $employee,
            $request->file('document_file'),
            $validated['document_category'],
            $request->user()->id,
        );

        return redirect()->to(route('hr.employees.show', $employee).'#documents')
            ->with('status', __('Document uploaded.'));
    }

    public function downloadDocument(Request $request, Employee $employee, EmployeeDocument $document)
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);
        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);
        abort_unless((int) $document->business_id === (int) $business->id, 404);
        abort_unless(Storage::disk('public')->exists($document->stored_path), 404);

        return Storage::disk('public')->download($document->stored_path, $document->original_filename);
    }

    public function destroyDocument(Request $request, Employee $employee, EmployeeDocument $document): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);
        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }
        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_if((int) $employee->business_id !== (int) $business->id, 404);
        abort_unless((int) $document->employee_id === (int) $employee->id, 404);
        abort_unless((int) $document->business_id === (int) $business->id, 404);

        $this->employeeDocumentService->delete($document);

        return redirect()->to(route('hr.employees.show', $employee).'#documents')
            ->with('status', __('Document removed.'));
    }

    /** @return array<string, mixed> */
    private function employeeFieldRules(Business $business): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'profile_photo' => ['nullable', 'image', 'max:4096'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nic_passport_number' => [
                'required', 'string', 'max:64',
                Rule::unique('hr_employees', 'nic_passport_number')->where(
                    fn ($query) => $query->where('business_id', $business->id)
                ),
            ],
            'permanent_address' => ['required', 'string', 'max:5000'],
            'current_address' => ['required', 'string', 'max:5000'],
            'phone_number' => ['required', 'string', 'max:40'],
            'personal_email' => ['required', 'email', 'max:255'],

            'employee_id' => [
                'required', 'string', 'max:64',
                Rule::unique('hr_employees', 'employee_id')->where(
                    fn ($query) => $query->where('business_id', $business->id)
                ),
            ],
            'job_title_id' => ['required', 'string', 'max:64'],
            'new_job_title_name' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'string', 'max:64'],
            'new_department_name' => ['nullable', 'string', 'max:255'],
            'date_of_joining' => ['required', 'date'],
            'employment_type' => ['required', 'string', Rule::in(Employee::EMPLOYMENT_TYPES)],

            'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'salary' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'allowances' => ['nullable', 'array'],

            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_relationship' => ['required', 'string', 'max:120'],
            'emergency_contact_phone' => ['required', 'string', 'max:40'],

            'bank_account_holder_name' => ['required', 'string', 'max:255'],
            'bank_id' => ['required', 'integer', 'exists:banks,id'],
            'bank_branch' => ['required', 'string', 'max:255'],
            'bank_account_number' => ['required', 'string', 'max:64'],

            'epf_number' => ['nullable', 'string', 'max:80'],
            'etf_number' => ['nullable', 'string', 'max:80'],
            'tax_tin' => ['nullable', 'string', 'max:80'],
        ];
    }
}
