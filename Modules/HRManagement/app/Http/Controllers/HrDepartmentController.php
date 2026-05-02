<?php

namespace Modules\HRManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Business\Models\Business;
use Modules\HRManagement\Models\Department;
use Modules\HRManagement\Services\DepartmentService;
use Modules\HRManagement\Services\HrPayrollSettingsService;

class HrDepartmentController extends Controller
{
    public function __construct(
        private readonly HrPayrollSettingsService $hrPayrollSettings,
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(Request $request): RedirectResponse|View
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }

        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);

        return view('hrmanagement::departments.index', [
            'business' => $business,
            'departments' => $business->departments()->withCount('employees')->orderBy('name')->orderBy('id')->get(),
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

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('hr_departments', 'name')->where(
                    fn ($query) => $query->where('business_id', $business->id)
                ),
            ],
        ]);

        $this->departmentService->create($business, $validated['name']);

        return redirect()->route('hr.departments.index')->with('status', __('Department saved.'));
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $business = Business::currentForNavbar($request->user());
        abort_if($business === null, 403);

        if (! $this->hrPayrollSettings->optedIn($business)) {
            return redirect()->route('hr.onboarding');
        }

        abort_unless($request->user()->businesses()->whereKey($business->id)->exists(), 403);
        abort_unless((int) $department->business_id === (int) $business->id, 403);

        if ($department->employees()->exists()) {
            return redirect()->route('hr.departments.index')->withErrors([
                'department' => __('Cannot delete a department that still has employees assigned.'),
            ]);
        }

        $department->delete();

        return redirect()->route('hr.departments.index')->with('status', __('Department deleted.'));
    }
}
