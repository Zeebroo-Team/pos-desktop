@extends('theme::layouts.app', ['title' => __('Payroll cycle'), 'heading' => __('Payroll cycle')])

@section('content')
    <style>
        .payroll-cycle{max-width:1120px;display:grid;gap:11px}
        .payroll-cycle__card{border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:var(--card);padding:12px 14px}
        .payroll-cycle__head{display:flex;flex-wrap:wrap;justify-content:space-between;gap:8px;align-items:flex-start}
        .payroll-cycle__title{margin:0;font-size:.98rem;font-weight:800}
        .payroll-cycle__sub{margin:4px 0 0;font-size:12px;color:var(--muted)}
        .payroll-cycle__actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
        .payroll-kpi-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .payroll-kpi{padding:9px;border:1px solid color-mix(in srgb,var(--border)88%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)96%,transparent)}
        .payroll-kpi small{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.06em}
        .payroll-kpi strong{display:block;margin-top:4px;font-size:15px}
        .payroll-table-wrap{overflow:auto;border:1px solid color-mix(in srgb,var(--border)88%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)98%,transparent)}
        .payroll-table{width:100%;min-width:980px;border-collapse:separate;border-spacing:0}
        .payroll-table thead th{
            background:color-mix(in srgb,var(--card)94%,transparent);
            color:var(--muted);
            font-size:10px;
            text-transform:uppercase;
            letter-spacing:.06em;
            font-weight:800;
            padding:8px;
            border-bottom:1px solid color-mix(in srgb,var(--border)82%,transparent);
            white-space:nowrap;
        }
        .payroll-table tbody td{
            padding:8px 8px;
            border-bottom:1px solid color-mix(in srgb,var(--border)74%,transparent);
            vertical-align:top;
        }
        .payroll-table tbody tr:nth-child(even) td{background:color-mix(in srgb,var(--card)97%,transparent)}
        .payroll-table tbody tr:hover td{background:color-mix(in srgb,var(--primary)6%,transparent)}
        .payroll-table tbody tr:last-child td{border-bottom:none}
        .payroll-table th + th,.payroll-table td + td{border-left:1px solid color-mix(in srgb,var(--border)68%,transparent)}
        .payroll-td--num{text-align:right;font-variant-numeric:tabular-nums}
        .payroll-td--center{text-align:center}
        .payroll-name{font-size:13px;font-weight:700;line-height:1.3}
        .payroll-mini-form{display:grid;gap:4px;min-width:190px}
        .payroll-input{
            width:100%;box-sizing:border-box;
            border:1px solid color-mix(in srgb,var(--border)90%,transparent);
            background:color-mix(in srgb,var(--card)96%,transparent);
            color:var(--text);
            border-radius:8px;
            padding:7px 8px;
            font-size:12px;
            line-height:1.35;
            outline:none;
        }
        .payroll-input:focus{
            border-color:color-mix(in srgb,var(--primary)48%,var(--border));
            box-shadow:0 0 0 3px color-mix(in srgb,var(--primary)14%,transparent);
        }
        @media (max-width:900px){.payroll-kpi-grid{grid-template-columns:1fr}}
    </style>
    @if(session('status'))
        <p class="emp-show__flash" role="status" style="max-width:1080px;">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <div class="emp-show__err" role="alert" style="max-width:1080px;">
            <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="payroll-cycle">
        <section class="payroll-cycle__card">
            <div class="payroll-cycle__head">
                <div>
                    <h2 class="payroll-cycle__title">{{ $cycle->name }}</h2>
                    <p class="payroll-cycle__sub">
                        {{ __('Period') }}: {{ $cycle->period_start?->format('Y-m-d') }} → {{ $cycle->period_end?->format('Y-m-d') }}
                        · {{ __('Status') }}: <strong>{{ ucfirst($cycle->status) }}</strong>
                        · {{ __('Rule set') }}: {{ $cycle->ruleSet?->name ?? '—' }}
                    </p>
                </div>
                <div class="payroll-cycle__actions">
                    <a href="{{ route('hr.payroll.index') }}" class="emp-docs-action">{{ __('Back') }}</a>
                    <form method="post" action="{{ route('hr.payroll.cycles.salary-sheet.generate', $cycle) }}">@csrf<button class="emp-docs-action" type="submit">{{ __('Generate salary sheet') }}</button></form>
                    <a href="{{ route('hr.payroll.cycles.salary-sheet', $cycle) }}" class="emp-docs-action">{{ __('View salary sheet') }}</a>
                    @if(! $cycle->isFinalized())
                        <form method="post" action="{{ route('hr.payroll.cycles.compute', $cycle) }}">@csrf<button class="emp-docs-action" type="submit">{{ __('Compute all') }}</button></form>
                        <form method="post" action="{{ route('hr.payroll.cycles.finalize', $cycle) }}" onsubmit="return confirm(@json(__('Finalize this cycle?')))">@csrf<button class="emp-docs-action emp-leave-act--ok" type="submit">{{ __('Finalize') }}</button></form>
                    @endif
                </div>
            </div>
        </section>

        <section class="payroll-cycle__card">
            <h3 style="margin:0 0 12px;font-size:1rem;">{{ __('Cycle summary') }}</h3>
            <div class="payroll-kpi-grid">
                <article class="payroll-kpi"><small>{{ __('Total gross') }}</small><strong>{{ number_format((float) $summary['total_gross'], 2) }}</strong></article>
                <article class="payroll-kpi"><small>{{ __('Total deductions') }}</small><strong>{{ number_format((float) $summary['total_deductions'], 2) }}</strong></article>
                <article class="payroll-kpi"><small>{{ __('Total net') }}</small><strong>{{ number_format((float) $summary['total_net'], 2) }}</strong></article>
                <article class="payroll-kpi"><small>{{ __('EPF total') }}</small><strong>{{ number_format((float) $summary['epf'], 2) }}</strong></article>
                <article class="payroll-kpi"><small>{{ __('ETF total') }}</small><strong>{{ number_format((float) $summary['etf'], 2) }}</strong></article>
                <article class="payroll-kpi"><small>{{ __('APIT total') }}</small><strong>{{ number_format((float) $summary['apit'], 2) }}</strong></article>
            </div>
        </section>

        <section class="payroll-cycle__card">
            <h3 style="margin:0 0 12px;font-size:1rem;">{{ __('Employee payroll items') }}</h3>
            <div class="payroll-table-wrap">
                <table class="emp-docs-table payroll-table">
                    <thead>
                        <tr>
                            <th>{{ __('Employee') }}</th>
                            <th>{{ __('Basic') }}</th>
                            <th>{{ __('Overtime') }}</th>
                            <th>{{ __('Gross') }}</th>
                            <th>{{ __('Deductions') }}</th>
                            <th>{{ __('Net') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Payslip') }}</th>
                            <th>{{ __('Recompute') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycle->items as $item)
                            <tr>
                                <td><span class="payroll-name">{{ $item->employee?->full_name ?? '—' }}</span><br><span class="emp-docs-table__meta">{{ $item->employee?->employee_id }}</span></td>
                                <td class="payroll-td--num">{{ number_format((float) $item->basic_salary, 2) }}</td>
                                <td class="payroll-td--num">{{ number_format((float) $item->overtime_amount, 2) }}</td>
                                <td class="payroll-td--num">{{ number_format((float) $item->gross_earnings, 2) }}</td>
                                <td class="payroll-td--num">{{ number_format((float) $item->total_deductions, 2) }}</td>
                                <td class="payroll-td--num"><strong>{{ number_format((float) $item->net_pay, 2) }}</strong></td>
                                <td class="payroll-td--center">{{ ucfirst((string) $item->status) }}</td>
                                <td>
                                    <div class="emp-docs-table__acts">
                                        <a href="{{ route('hr.payroll.cycles.items.payslip', [$cycle, $item]) }}" class="emp-docs-action">{{ __('View') }}</a>
                                        <a href="{{ route('hr.payroll.cycles.items.payslip.download', [$cycle, $item]) }}" class="emp-docs-action">{{ __('Download') }}</a>
                                    </div>
                                </td>
                                <td>
                                    @if(! $cycle->isFinalized())
                                        <form method="post" action="{{ route('hr.payroll.cycles.items.recompute', [$cycle, $item]) }}" class="payroll-mini-form">
                                            @csrf
                                            <input type="number" step="0.01" min="0" name="overtime_hours" class="payroll-input" placeholder="{{ __('OT hours') }}" value="{{ $item->inputs_json['overtime_hours'] ?? '' }}">
                                            <input type="number" step="0.01" min="0" name="overtime_rate" class="payroll-input" placeholder="{{ __('OT rate') }}" value="{{ $item->inputs_json['overtime_rate'] ?? '' }}">
                                            <button type="submit" class="emp-docs-action">{{ __('Recompute') }}</button>
                                        </form>
                                    @else
                                        <span class="emp-docs-table__meta">{{ __('Locked') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="muted">{{ __('No items computed yet. Click Compute all.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
