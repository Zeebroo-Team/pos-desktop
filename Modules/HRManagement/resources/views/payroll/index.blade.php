@extends('theme::layouts.app', ['title' => __('Payroll'), 'heading' => __('Payroll')])

@section('content')
    <style>
        .payroll-wrap{max-width:1120px;display:grid;gap:12px}
        .payroll-kpis{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .payroll-kpi{padding:9px 11px;border-radius:10px;border:1px solid color-mix(in srgb,var(--border)90%,transparent);background:var(--card)}
        .payroll-kpi small{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700}
        .payroll-kpi strong{display:block;margin-top:3px;font-size:16px;letter-spacing:-.01em}
        .payroll-card{border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:var(--card);padding:12px 14px;box-shadow:0 1px 0 color-mix(in srgb,var(--border)55%,transparent) inset,0 6px 18px rgba(0,0,0,.04)}
        .payroll-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px}
        .payroll-title{margin:0;font-size:.98rem;font-weight:800}
        .payroll-sub{margin:3px 0 0;font-size:12px;line-height:1.4;color:var(--muted)}
        .payroll-grid{display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end}
        .payroll-field label{display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px}
        .payroll-input{
            width:100%;box-sizing:border-box;
            border:1px solid color-mix(in srgb,var(--border)90%,transparent);
            background:color-mix(in srgb,var(--card)96%,transparent);
            color:var(--text);
            border-radius:8px;
            padding:8px 10px;
            font-size:12px;
            line-height:1.35;
            outline:none;
        }
        .payroll-input:focus{
            border-color:color-mix(in srgb,var(--primary)48%,var(--border));
            box-shadow:0 0 0 3px color-mix(in srgb,var(--primary)14%,transparent);
        }
        .payroll-table-wrap{overflow:auto;border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)98%,transparent)}
        .payroll-table{width:100%;min-width:760px;border-collapse:separate;border-spacing:0}
        .payroll-table th,.payroll-table td{vertical-align:top}
        .payroll-table thead th{
            background:color-mix(in srgb,var(--card)94%,transparent);
            color:var(--muted);
            font-size:10px;
            text-transform:uppercase;
            letter-spacing:.06em;
            font-weight:800;
            padding:8px 8px;
            white-space:nowrap;
            border-bottom:1px solid color-mix(in srgb,var(--border)82%,transparent);
        }
        .payroll-table tbody td{
            padding:8px 8px;
            border-bottom:1px solid color-mix(in srgb,var(--border)74%,transparent);
        }
        .payroll-table tbody tr:nth-child(even) td{background:color-mix(in srgb,var(--card)97%,transparent)}
        .payroll-table tbody tr:hover td{background:color-mix(in srgb,var(--primary)6%,transparent)}
        .payroll-table tbody tr:last-child td{border-bottom:none}
        .payroll-table th + th,.payroll-table td + td{border-left:1px solid color-mix(in srgb,var(--border)68%,transparent)}
        .payroll-table .emp-docs-table__meta{display:inline-block;font-size:11px}
        .payroll-name{font-size:13px;font-weight:700;line-height:1.3}
        .payroll-rule-set-col{font-size:11px;line-height:1.3}
        .payroll-rules-col{font-size:11px;line-height:1.3}
        .payroll-td--num{text-align:right;font-variant-numeric:tabular-nums}
        .payroll-td--center{text-align:center}
        .payroll-rule-cell{background:color-mix(in srgb,var(--card)95%,transparent)!important}
        .payroll-chip{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid var(--border);background:color-mix(in srgb,var(--card)96%,transparent)}
        .payroll-chip--ok{border-color:color-mix(in srgb,#22c55e 42%,var(--border));color:#15803d;background:color-mix(in srgb,#22c55e 10%,transparent)}
        .payroll-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:8px;border:1px solid color-mix(in srgb,var(--primary)42%,var(--border));background:color-mix(in srgb,var(--primary)12%,transparent);color:var(--text);font-size:12px;font-weight:700;cursor:pointer}
        .payroll-btn:hover{background:color-mix(in srgb,var(--primary)18%,transparent)}
        .payroll-actions{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
        .payroll-actions .emp-docs-action{padding:4px 8px;font-size:10px}
        .payroll-template{display:flex;flex-wrap:wrap;gap:8px;align-items:end;padding:10px 12px;border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:var(--card)}
        .payroll-template__field{min-width:260px;flex:1}
        .payroll-template__hint{margin:0;font-size:11px;color:var(--muted)}
        @media (max-width:900px){.payroll-kpis{grid-template-columns:1fr}}
    </style>
    @php
        $draftCount = $cycles->where('status', 'draft')->count();
        $finalizedCount = $cycles->where('status', 'finalized')->count();
    @endphp
    @if(session('status'))
        <p class="emp-show__flash" role="status" style="max-width:1080px;">{{ session('status') }}</p>
    @endif
    @if(session('warning'))
        <p class="emp-show__err" role="alert" style="max-width:1080px;">{{ session('warning') }}</p>
    @endif
    @if($errors->any())
        <div class="emp-show__err" role="alert" style="max-width:1080px;">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="payroll-wrap">
        <form method="post" action="{{ route('hr.payroll.templates.apply') }}" class="payroll-template">
            @csrf
            <div class="payroll-template__field payroll-field">
                <label>{{ __('Payroll template') }}</label>
                <select name="template" class="payroll-input" required>
                    @foreach($payrollTemplates as $templateKey => $templateLabel)
                        <option value="{{ $templateKey }}" @selected($selectedPayrollTemplate === $templateKey)>{{ $templateLabel }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="payroll-btn"><i class="fa fa-gear"></i>{{ __('Apply template') }}</button>
            <p class="payroll-template__hint">{{ __('Selecting template auto-configures statutory rules and payroll cycle defaults (EPF, ETF, APIT).') }}</p>
        </form>

        <div class="payroll-kpis">
            <article class="payroll-kpi"><small>{{ __('Rule sets') }}</small><strong>{{ $ruleSets->count() }}</strong></article>
            <article class="payroll-kpi"><small>{{ __('Draft cycles') }}</small><strong>{{ $draftCount }}</strong></article>
            <article class="payroll-kpi"><small>{{ __('Finalized cycles') }}</small><strong>{{ $finalizedCount }}</strong></article>
        </div>

        <section class="payroll-card">
            <div class="payroll-head">
                <div>
                    <h2 class="payroll-title">{{ __('Rule sets') }}</h2>
                    <p class="payroll-sub">{{ __('Manage payroll rule sets and add EPF/ETF/APIT/custom formulas from a dedicated page.') }}</p>
                </div>
                <a href="{{ route('hr.payroll.rule-sets.index') }}" class="payroll-btn"><i class="fa fa-sliders"></i>{{ __('Open rule sets') }}</a>
            </div>
        </section>

        <section class="payroll-card">
            <div class="payroll-head">
                <div>
                    <h2 class="payroll-title">{{ __('Payroll cycles') }}</h2>
                    <p class="payroll-sub">{{ __('Create monthly runs, compute payroll, review outputs, and finalize for payout.') }}</p>
                </div>
            </div>
            <form method="post" action="{{ route('hr.payroll.cycles.store') }}" class="payroll-grid" style="margin-bottom:14px;">
                @csrf
                <div class="payroll-field"><label>{{ __('Rule set') }}</label>
                    <select name="rule_set_id" class="payroll-input" required>
                        @foreach($ruleSets as $set)
                            <option value="{{ $set->id }}" @selected((int) $defaultRuleSetId === (int) $set->id)>{{ $set->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="payroll-field"><label>{{ __('Cycle name') }}</label><input type="text" name="name" class="payroll-input" value="{{ get_settings('hr.payroll.cycle.default_name', __('Monthly Payroll'), $business) }}" required></div>
                <div class="payroll-field"><label>{{ __('Year') }}</label><input type="number" name="year" class="payroll-input" value="{{ now()->year }}" min="2020" max="2100" required></div>
                <div class="payroll-field"><label>{{ __('Month') }}</label><input type="number" name="month" class="payroll-input" value="{{ now()->month }}" min="1" max="12" required></div>
                <div class="payroll-field"><label>{{ __('Start') }}</label><input type="date" name="period_start" class="payroll-input" value="{{ now()->startOfMonth()->toDateString() }}" required></div>
                <div class="payroll-field"><label>{{ __('End') }}</label><input type="date" name="period_end" class="payroll-input" value="{{ now()->endOfMonth()->toDateString() }}" required></div>
                <div style="grid-column:1/-1;"><button type="submit" class="payroll-btn"><i class="fa fa-calendar-plus"></i>{{ __('Create payroll cycle') }}</button></div>
            </form>

            <div class="payroll-table-wrap">
                <table class="emp-docs-table payroll-table">
                    <thead>
                        <tr>
                            <th style="width:24%;">{{ __('Cycle') }}</th>
                            <th style="width:20%;">{{ __('Period') }}</th>
                            <th style="width:18%;">{{ __('Rule set') }}</th>
                            <th style="width:14%;">{{ __('Status') }}</th>
                            <th style="width:10%;">{{ __('Employees') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $cycle)
                            <tr>
                                <td><span class="payroll-name">{{ $cycle->name }}</span><br><span class="emp-docs-table__meta">{{ $cycle->year }}-{{ str_pad((string) $cycle->month, 2, '0', STR_PAD_LEFT) }}</span></td>
                                <td><span class="emp-docs-table__meta">{{ $cycle->period_start?->format('Y-m-d') ?? '—' }} → {{ $cycle->period_end?->format('Y-m-d') ?? '—' }}</span></td>
                                <td><span class="payroll-rule-set-col">{{ $cycle->ruleSet?->name ?? '—' }}</span></td>
                                <td class="payroll-td--center">
                                    @if($cycle->isFinalized())
                                        <span class="payroll-chip payroll-chip--ok">{{ __('Finalized') }}</span>
                                    @else
                                        <span class="payroll-chip">{{ ucfirst($cycle->status) }}</span>
                                    @endif
                                </td>
                                <td class="payroll-td--num">{{ $cycle->items_count }}</td>
                                <td>
                                    <div class="payroll-actions">
                                        <a href="{{ route('hr.payroll.cycles.show', $cycle) }}" class="emp-docs-action">{{ __('Open') }}</a>
                                        <form method="post" action="{{ route('hr.payroll.cycles.salary-sheet.generate', $cycle) }}">@csrf<button class="emp-docs-action" type="submit">{{ __('Generate salary sheet') }}</button></form>
                                        <a href="{{ route('hr.payroll.cycles.salary-sheet', $cycle) }}" class="emp-docs-action">{{ __('View salary sheet') }}</a>
                                        @if(! $cycle->isFinalized())
                                            <form method="post" action="{{ route('hr.payroll.cycles.compute', $cycle) }}">@csrf<button class="emp-docs-action" type="submit">{{ __('Compute') }}</button></form>
                                            <form method="post" action="{{ route('hr.payroll.cycles.finalize', $cycle) }}" onsubmit="return confirm(@json(__('Finalize this cycle? This will lock updates.')))">@csrf<button class="emp-docs-action emp-leave-act--ok" type="submit">{{ __('Finalize') }}</button></form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">{{ __('No cycles yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
