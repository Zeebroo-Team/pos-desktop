@extends('theme::layouts.app', ['title' => 'HR · '.$business->name, 'heading' => 'Human resources'])

@section('content')
    @if(session('status'))
        <div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,color-mix(in srgb,#22c55e 14%,transparent),transparent);border-color:color-mix(in srgb,#22c55e 45%,var(--border));max-width:none;">
            <strong style="color:color-mix(in srgb,#22c55e 85%,var(--text));">{{ session('status') }}</strong>
        </div>
    @endif

    <div class="card" style="max-width:none;">
        <h2 style="margin:0 0 10px;font-size:clamp(1.08rem,2vw,1.25rem);">HR hub — {{ $business->name }}</h2>
        @if($employeeBandLabel ?? null)
            <p class="muted" style="margin:0 0 14px;line-height:1.45;font-size:13px;">Headcount tier: <strong>{{ $employeeBandLabel }}</strong></p>
        @endif
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
            <a href="{{ route('hr.employees.index') }}" style="border:1px solid var(--border);border-radius:14px;padding:18px;text-decoration:none;color:inherit;display:block;transition:border-color .2s ease;background:color-mix(in srgb,var(--card) 92%,transparent);"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <strong style="font-size:14px;display:flex;align-items:center;gap:8px;"><i class="fa fa-user-group"></i> Employees</strong>
                <span class="muted" style="margin-top:6px;display:block;font-size:12px;line-height:1.4;">Register and list staff for payroll.</span>
            </a>
            <a href="{{ route('hr.payroll.index') }}" style="border:1px solid var(--border);border-radius:14px;padding:18px;text-decoration:none;color:inherit;display:block;transition:border-color .2s ease;background:color-mix(in srgb,var(--card) 92%,transparent);"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <strong style="font-size:14px;display:flex;align-items:center;gap:8px;"><i class="fa fa-money-check-dollar"></i> Payroll</strong>
                <span class="muted" style="margin-top:6px;display:block;font-size:12px;line-height:1.4;">Runs, payslips, approvals — coming soon.</span>
            </a>
            <a href="{{ route('hr.departments.index') }}" style="border:1px solid var(--border);border-radius:14px;padding:18px;text-decoration:none;color:inherit;display:block;transition:border-color .2s ease;background:color-mix(in srgb,var(--card) 92%,transparent);"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <strong style="font-size:14px;display:flex;align-items:center;gap:8px;"><i class="fa fa-folder-tree"></i> Departments</strong>
                <span class="muted" style="margin-top:6px;display:block;font-size:12px;line-height:1.4;">Teams and divisions—used on employee profiles.</span>
            </a>
            <a href="{{ route('hr.job-titles.index') }}" style="border:1px solid var(--border);border-radius:14px;padding:18px;text-decoration:none;color:inherit;display:block;transition:border-color .2s ease;background:color-mix(in srgb,var(--card) 92%,transparent);"
               onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='var(--border)'">
                <strong style="font-size:14px;display:flex;align-items:center;gap:8px;"><i class="fa fa-id-badge"></i> Designations</strong>
                <span class="muted" style="margin-top:6px;display:block;font-size:12px;line-height:1.4;">Job titles for this business.</span>
            </a>
        </div>
    </div>
@endsection
