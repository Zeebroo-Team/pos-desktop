<?php

declare(strict_types=1);

namespace Modules\HRManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HRManagement\Services\EmployeePortalService;
use Modules\HRManagement\Services\HrPayrollSettingsService;

class HrEmployeePortalController extends Controller
{
    public function __construct(
        private readonly EmployeePortalService $employeePortal,
        private readonly HrPayrollSettingsService $hrPayrollSettings,
    ) {}

    public function showLogin(): View|RedirectResponse
    {
        $hasAccountButNoEmployee = false;
        if (Auth::check()) {
            $employee = $this->employeePortal->linkAndResolve(Auth::user());
            if ($employee !== null) {
                return redirect()->route('hr.portal.dashboard');
            }
            $hasAccountButNoEmployee = true;
        }

        return view('hrmanagement::portal.login', [
            'googleAuthConfigured' => $this->googleOAuthConfigured(),
            'hasAccountButNoEmployee' => $hasAccountButNoEmployee,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $employee = $this->employeePortal->linkAndResolve(Auth::user());

            return $employee !== null
                ? redirect()->route('hr.portal.dashboard')
                : redirect()->route('login')->with('status', __('You are already signed in. Use your workspace, or sign out to switch accounts.'));
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('These credentials do not match our records.')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $employee = $this->employeePortal->linkAndResolve($user);

        if ($employee === null) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'email' => __('No employee profile is linked to this account. Sign in with the same email your HR team has on file, or ask them to connect your login.'),
                ])
                ->onlyInput('email');
        }

        return redirect()->intended(route('hr.portal.dashboard'));
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $employee = $this->employeePortal->linkAndResolve($user);
        if ($employee === null) {
            return redirect()->route('hr.portal.login')
                ->withErrors(['email' => __('Your session no longer has an employee profile.')]);
        }

        $employee->load(['business', 'department', 'jobTitle']);

        $business = $employee->business;
        if ($business === null || ! $this->hrPayrollSettings->optedIn($business)) {
            return view('hrmanagement::portal.unavailable', [
                'employee' => $employee,
                'heading' => __('HR portal'),
            ]);
        }

        $employee->load(['leaveRequests' => fn ($q) => $q->orderByDesc('created_at')->limit(20)]);

        return view('hrmanagement::portal.dashboard', [
            'employee' => $employee,
            'heading' => __('HR portal'),
            'employeePortal' => true,
            'portalEmployerBusiness' => $business,
            'portalEmployee' => $employee,
        ]);
    }

    public function profile(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $employee = $this->employeePortal->linkAndResolve($user);
        if ($employee === null) {
            return redirect()->route('hr.portal.login');
        }

        $employee->load(['business', 'department', 'jobTitle', 'bank']);

        $business = $employee->business;
        if ($business === null || ! $this->hrPayrollSettings->optedIn($business)) {
            return view('hrmanagement::portal.unavailable', [
                'employee' => $employee,
                'heading' => __('HR portal'),
            ]);
        }

        return view('hrmanagement::portal.profile', [
            'employee' => $employee,
            'heading' => __('My profile'),
            'employeePortal' => true,
            'portalEmployerBusiness' => $business,
            'portalEmployee' => $employee,
        ]);
    }

    private function googleOAuthConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
