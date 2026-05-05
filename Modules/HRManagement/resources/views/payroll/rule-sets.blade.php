@extends('theme::layouts.app', ['title' => __('Payroll rule sets'), 'heading' => __('Payroll rule sets')])

@section('content')
    <style>
        .payroll-wrap{max-width:1120px;display:grid;gap:12px}
        .payroll-card{border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:var(--card);padding:12px 14px;box-shadow:0 1px 0 color-mix(in srgb,var(--border)55%,transparent) inset,0 6px 18px rgba(0,0,0,.04)}
        .payroll-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px}
        .payroll-title{margin:0;font-size:.98rem;font-weight:800}
        .payroll-sub{margin:3px 0 0;font-size:12px;line-height:1.4;color:var(--muted)}
        .payroll-grid{display:grid;gap:8px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end}
        .payroll-field label{display:block;font-size:10px;font-weight:700;color:var(--muted);margin-bottom:4px}
        .payroll-input{width:100%;box-sizing:border-box;border:1px solid color-mix(in srgb,var(--border)90%,transparent);background:color-mix(in srgb,var(--card)96%,transparent);color:var(--text);border-radius:8px;padding:8px 10px;font-size:12px;line-height:1.35;outline:none}
        .payroll-input:focus{border-color:color-mix(in srgb,var(--primary)48%,var(--border));box-shadow:0 0 0 3px color-mix(in srgb,var(--primary)14%,transparent)}
        .payroll-table-wrap{overflow:auto;border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:10px;background:color-mix(in srgb,var(--card)98%,transparent)}
        .payroll-table{width:100%;min-width:760px;border-collapse:separate;border-spacing:0}
        .payroll-table th,.payroll-table td{vertical-align:top}
        .payroll-table thead th{background:color-mix(in srgb,var(--card)94%,transparent);color:var(--muted);font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:800;padding:8px;white-space:nowrap;border-bottom:1px solid color-mix(in srgb,var(--border)82%,transparent)}
        .payroll-table tbody td{padding:8px;border-bottom:1px solid color-mix(in srgb,var(--border)74%,transparent)}
        .payroll-table tbody tr:nth-child(even) td{background:color-mix(in srgb,var(--card)97%,transparent)}
        .payroll-table tbody tr:hover td{background:color-mix(in srgb,var(--primary)6%,transparent)}
        .payroll-table tbody tr:last-child td{border-bottom:none}
        .payroll-table th + th,.payroll-table td + td{border-left:1px solid color-mix(in srgb,var(--border)68%,transparent)}
        .payroll-name{font-size:11px;font-weight:700;line-height:1.25}
        .payroll-rules-col{font-size:10px;line-height:1.25}
        .payroll-col-name-head,.payroll-col-effective-head{font-size:9px!important}
        .payroll-col-effective-val{font-size:9.5px;line-height:1.25}
        .payroll-col-currency-val{font-size:9px;line-height:1.2}
        .payroll-td--num{text-align:right;font-variant-numeric:tabular-nums}
        .payroll-td--center{text-align:center}
        .payroll-rule-cell{background:color-mix(in srgb,var(--card)95%,transparent)!important}
        .payroll-chip{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid var(--border);background:color-mix(in srgb,var(--card)96%,transparent)}
        .payroll-chip--ok{border-color:color-mix(in srgb,#22c55e 42%,var(--border));color:#15803d;background:color-mix(in srgb,#22c55e 10%,transparent)}
        .payroll-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:8px;border:1px solid color-mix(in srgb,var(--primary)42%,var(--border));background:color-mix(in srgb,var(--primary)12%,transparent);color:var(--text);font-size:12px;font-weight:700;cursor:pointer}
        .payroll-btn:hover{background:color-mix(in srgb,var(--primary)18%,transparent)}
        .payroll-rule-form{display:grid;gap:5px;grid-template-columns:repeat(3,minmax(100px,1fr));padding:5px;border:1px solid color-mix(in srgb,var(--border)84%,transparent);border-radius:8px;background:color-mix(in srgb,var(--card)98%,transparent)}
    </style>

    @if(session('status'))
        <p class="emp-show__flash" role="status" style="max-width:1080px;">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <div class="emp-show__err" role="alert" style="max-width:1080px;">
            <ul style="margin:0;padding-left:18px;">@foreach($errors->all() as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="payroll-wrap">
        <section class="payroll-card">
            <div class="payroll-head">
                <div>
                    <h2 class="payroll-title">{{ __('Rule sets') }}</h2>
                    <p class="payroll-sub">{{ __('Create and maintain payroll rules with effective dates. Use this page for EPF, ETF, APIT, and custom formulas.') }}</p>
                </div>
                <a href="{{ route('hr.payroll.index') }}" class="payroll-btn"><i class="fa fa-arrow-left"></i>{{ __('Back to payroll') }}</a>
            </div>
            <form method="post" action="{{ route('hr.payroll.rule-sets.store') }}" class="payroll-grid">
                @csrf
                <div class="payroll-field"><label>{{ __('Rule set name') }}</label><input type="text" name="name" class="payroll-input" required></div>
                <div class="payroll-field"><label>{{ __('Currency') }}</label><input type="text" name="currency" class="payroll-input" value="{{ $business->currency ?? 'LKR' }}"></div>
                <div class="payroll-field"><label>{{ __('Effective from') }}</label><input type="date" name="effective_from" class="payroll-input" value="{{ now()->toDateString() }}" required></div>
                <div class="payroll-field"><label>{{ __('Effective to') }}</label><input type="date" name="effective_to" class="payroll-input"></div>
                <div class="payroll-field"><label>{{ __('Default') }}</label><select name="is_default" class="payroll-input"><option value="0">{{ __('No') }}</option><option value="1">{{ __('Yes') }}</option></select></div>
                <div style="grid-column:1/-1;"><button type="submit" class="payroll-btn"><i class="fa fa-plus"></i>{{ __('Create rule set') }}</button></div>
            </form>

            <div class="payroll-table-wrap" style="margin-top:14px;">
                <table class="emp-docs-table payroll-table">
                    <thead>
                        <tr>
                            <th class="payroll-col-name-head" style="width:28%;">{{ __('Name') }}</th>
                            <th class="payroll-col-effective-head" style="width:16%;">{{ __('Effective') }}</th>
                            <th style="width:10%;">{{ __('Rules') }}</th>
                            <th style="width:12%;">{{ __('Default') }}</th>
                            <th>{{ __('Add rule') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ruleSets as $set)
                            <tr>
                                <td><span class="payroll-name">{{ $set->name }}</span><br><span class="emp-docs-table__meta payroll-col-currency-val">{{ $set->currency }}</span></td>
                                <td><span class="emp-docs-table__meta payroll-col-effective-val">{{ $set->effective_from?->format('Y-m-d') ?? '—' }} → {{ $set->effective_to?->format('Y-m-d') ?? __('open') }}</span></td>
                                <td class="payroll-td--num"><span class="payroll-rules-col">{{ $set->rules_count }}</span></td>
                                <td class="payroll-td--center">@if($set->is_default)<span class="payroll-chip payroll-chip--ok">{{ __('Default') }}</span>@else<span class="payroll-chip">{{ __('No') }}</span>@endif</td>
                                <td class="payroll-rule-cell">
                                    <form method="post" action="{{ route('hr.payroll.rules.store', $set) }}" class="payroll-rule-form">
                                        @csrf
                                        <input type="text" name="code" class="payroll-input" placeholder="CODE" required>
                                        <input type="text" name="name" class="payroll-input" placeholder="{{ __('Name') }}" required>
                                        <select name="component_type" class="payroll-input" required>
                                            <option value="earning">{{ __('Earning') }}</option>
                                            <option value="deduction">{{ __('Deduction') }}</option>
                                            <option value="statutory">{{ __('Statutory') }}</option>
                                            <option value="overtime">{{ __('Overtime') }}</option>
                                        </select>
                                        <select name="calculation_mode" class="payroll-input" required>
                                            <option value="fixed">{{ __('Fixed') }}</option>
                                            <option value="percentage">{{ __('Percentage') }}</option>
                                            <option value="slab">{{ __('Slab') }}</option>
                                            <option value="formula">{{ __('Formula') }}</option>
                                        </select>
                                        <input type="number" name="sort_order" class="payroll-input" placeholder="{{ __('Order') }}" value="0" min="0">
                                        <input type="text" name="config_json" class="payroll-input" placeholder='{"amount":1000}'>
                                        <button type="submit" class="payroll-btn" style="grid-column:1/-1;justify-content:center;padding:7px 12px;font-size:12px;"><i class="fa fa-plus"></i>{{ __('Add rule') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted">{{ __('No rule sets yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
