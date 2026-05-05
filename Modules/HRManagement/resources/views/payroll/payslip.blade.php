@extends('theme::layouts.app', ['title' => __('Payslip'), 'heading' => __('Payslip')])

@section('content')
    @php
        $isDownload = (bool) ($isDownload ?? false);
        $period = $cycle->year.'-'.str_pad((string) $cycle->month, 2, '0', STR_PAD_LEFT);
    @endphp
    <style>
        .payslip-sheet{max-width:920px;border:1px solid #dbe2ea;border-radius:14px;background:#fff;color:#0f172a;padding:18px 20px;box-shadow:0 12px 24px rgba(15,23,42,.06)}
        .payslip-top{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;border-bottom:1px dashed #dbe2ea;padding-bottom:12px;margin-bottom:14px}
        .payslip-title{margin:0;font-size:1.26rem;letter-spacing:-.01em}
        .payslip-sub{margin:4px 0 0;font-size:13px;color:#475569}
        .payslip-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:14px}
        .payslip-box{border:1px solid #dbe2ea;border-radius:10px;padding:10px 12px;background:#f8fafc}
        .payslip-box small{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#64748b;font-weight:700}
        .payslip-box strong{display:block;margin-top:5px;font-size:16px}
        .payslip-table{width:100%;border-collapse:collapse;min-width:620px;font-size:13px}
        .payslip-table thead th{background:#f8fafc;border-bottom:1px solid #dbe2ea;text-align:left;padding:9px 10px}
        .payslip-table td{padding:9px 10px;border-bottom:1px solid #eef2f7}
        .payslip-table td:last-child,.payslip-table th:last-child{text-align:right}
        .payslip-foot{display:grid;gap:8px;justify-content:end;margin-top:12px}
        .payslip-foot__row{display:flex;justify-content:space-between;gap:20px;min-width:280px;font-size:13px}
        .payslip-foot__row strong{font-size:15px}
    </style>
    <div class="payslip-sheet">
        @unless($isDownload)
            <p style="margin:0 0 12px;">
                <a href="{{ route('hr.payroll.cycles.show', $cycle) }}" class="emp-docs-action">{{ __('Back to cycle') }}</a>
            </p>
        @endunless

        <div class="payslip-top">
            <div>
                <h2 class="payslip-title">{{ __('Payslip') }}</h2>
                <p class="payslip-sub">{{ $business->name }} · {{ $cycle->name }} · {{ $period }}</p>
            </div>
            <div class="payslip-sub">{{ __('Generated at') }}: {{ now()->format('Y-m-d H:i') }}</div>
        </div>

        <div class="payslip-meta">
            <article class="payslip-box">
                <small>{{ __('Employee') }}</small>
                <strong>{{ $item->employee?->full_name ?? '—' }}</strong>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $item->employee?->employee_id }}</div>
            </article>
            <article class="payslip-box">
                <small>{{ __('Status') }}</small>
                <strong>{{ ucfirst((string) $item->status) }}</strong>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ __('Cycle') }}: {{ $period }}</div>
            </article>
            <article class="payslip-box">
                <small>{{ __('Net pay') }}</small>
                <strong>{{ number_format((float) $item->net_pay, 2) }}</strong>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ __('Amount payable') }}</div>
            </article>
        </div>

        <div style="overflow:auto;border:1px solid #dbe2ea;border-radius:10px;">
            <table class="payslip-table">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Component') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->components as $c)
                        <tr>
                            <td>{{ $c->code }}</td>
                            <td>{{ $c->name }}</td>
                            <td>{{ ucfirst((string) $c->component_type) }}</td>
                            <td>{{ number_format((float) $c->amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="payslip-foot">
            <div class="payslip-foot__row"><span>{{ __('Gross') }}</span><span>{{ number_format((float) $item->gross_earnings, 2) }}</span></div>
            <div class="payslip-foot__row"><span>{{ __('Deductions') }}</span><span>{{ number_format((float) $item->total_deductions, 2) }}</span></div>
            <div class="payslip-foot__row"><strong>{{ __('Net pay') }}</strong><strong>{{ number_format((float) $item->net_pay, 2) }}</strong></div>
        </div>
    </div>
@endsection
