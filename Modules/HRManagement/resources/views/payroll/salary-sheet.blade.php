@extends('theme::layouts.app', ['title' => __('Salary sheet'), 'heading' => __('Salary sheet')])

@section('content')
    <style>
        .salary-sheet{max-width:1180px;display:grid;gap:12px}
        .salary-sheet__card{border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:var(--card);padding:12px 14px}
        .salary-sheet__head{display:flex;flex-wrap:wrap;gap:8px;justify-content:space-between;align-items:flex-start}
        .salary-sheet__title{margin:0;font-size:1rem;font-weight:800}
        .salary-sheet__sub{margin:4px 0 0;font-size:12px;color:var(--muted)}
        .salary-sheet__actions{display:flex;gap:8px;flex-wrap:wrap}
        .salary-sheet__kpis{display:grid;gap:10px;grid-template-columns:repeat(4,minmax(0,1fr))}
        .salary-sheet__kpi{padding:9px;border:1px solid color-mix(in srgb,var(--border)88%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)96%,transparent)}
        .salary-sheet__kpi small{font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:var(--muted)}
        .salary-sheet__kpi strong{display:block;margin-top:4px;font-size:14px}
        .salary-sheet__table-wrap{overflow:auto;border:1px solid color-mix(in srgb,var(--border)88%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)98%,transparent)}
        .salary-sheet__table{width:100%;min-width:1120px;border-collapse:separate;border-spacing:0}
        .salary-sheet__table thead th{background:color-mix(in srgb,var(--card)94%,transparent);color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:800;padding:8px;border-bottom:1px solid color-mix(in srgb,var(--border)82%,transparent);white-space:nowrap}
        .salary-sheet__table tbody td{padding:8px;border-bottom:1px solid color-mix(in srgb,var(--border)74%,transparent);vertical-align:top}
        .salary-sheet__table tbody tr:nth-child(even) td{background:color-mix(in srgb,var(--card)97%,transparent)}
        .salary-sheet__table tbody tr:last-child td{border-bottom:none}
        .salary-sheet__table th + th,.salary-sheet__table td + td{border-left:1px solid color-mix(in srgb,var(--border)68%,transparent)}
        .salary-sheet__name{font-size:13px;font-weight:700;line-height:1.3}
        .salary-sheet__meta{font-size:11px;color:var(--muted)}
        .salary-sheet__num{text-align:right;font-variant-numeric:tabular-nums}
        .salary-sheet__center{text-align:center}
        @media (max-width:980px){.salary-sheet__kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
    </style>

    @if(session('status'))
        <p class="emp-show__flash" role="status" style="max-width:1120px;">{{ session('status') }}</p>
    @endif
    @if(session('warning'))
        <p class="emp-show__err" role="alert" style="max-width:1120px;">{{ session('warning') }}</p>
    @endif
    @if($errors->any())
        <div class="emp-show__err" role="alert" style="max-width:1120px;">
            <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="salary-sheet">
        <section class="salary-sheet__card">
            <div class="salary-sheet__head">
                <div>
                    <h2 class="salary-sheet__title">{{ __('Monthly salary sheet') }} — {{ $cycle->name }}</h2>
                    <p class="salary-sheet__sub">{{ __('Period') }}: {{ $cycle->period_start?->format('Y-m-d') }} → {{ $cycle->period_end?->format('Y-m-d') }} · {{ __('Rule set') }}: {{ $cycle->ruleSet?->name ?? '—' }}</p>
                </div>
                <div class="salary-sheet__actions">
                    <a href="{{ route('hr.payroll.cycles.show', $cycle) }}" class="emp-docs-action">{{ __('Back to cycle') }}</a>
                    <a href="{{ route('hr.payroll.index') }}" class="emp-docs-action">{{ __('Payroll home') }}</a>
                </div>
            </div>
        </section>

        <section class="salary-sheet__card">
            <div class="salary-sheet__kpis">
                <article class="salary-sheet__kpi"><small>{{ __('Employees') }}</small><strong>{{ count($rows) }}</strong></article>
                <article class="salary-sheet__kpi"><small>{{ __('Total gross') }}</small><strong>{{ number_format((float) $summary['total_gross'], 2) }}</strong></article>
                <article class="salary-sheet__kpi"><small>{{ __('Total deductions') }}</small><strong>{{ number_format((float) $summary['total_deductions'], 2) }}</strong></article>
                <article class="salary-sheet__kpi"><small>{{ __('Total net') }}</small><strong>{{ number_format((float) $summary['total_net'], 2) }}</strong></article>
            </div>
        </section>

        <section class="salary-sheet__card">
            <div class="salary-sheet__table-wrap">
                <table class="salary-sheet__table">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Basic') }}</th>
                            <th>{{ __('OT') }}</th>
                            <th>{{ __('Gross') }}</th>
                            <th>{{ __('EPF Emp.') }}</th>
                            <th>{{ __('EPF Emplr.') }}</th>
                            <th>{{ __('ETF Emplr.') }}</th>
                            <th>{{ __('APIT') }}</th>
                            <th>{{ __('Deductions') }}</th>
                            <th>{{ __('Net pay') }}</th>
                            <th>{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><span class="salary-sheet__name">{{ $row['employee_name'] }}</span><br><span class="salary-sheet__meta">{{ $row['employee_id'] }}</span></td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['basic_salary'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['overtime_amount'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['gross_earnings'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['epf_employee'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['epf_employer'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['etf_employer'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['apit'], 2) }}</td>
                                <td class="salary-sheet__num">{{ number_format((float) $row['total_deductions'], 2) }}</td>
                                <td class="salary-sheet__num"><strong>{{ number_format((float) $row['net_pay'], 2) }}</strong></td>
                                <td class="salary-sheet__center">{{ $row['status'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="muted">{{ __('No salary sheet rows found. Generate this cycle first.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
