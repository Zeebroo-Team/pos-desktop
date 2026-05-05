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
        .payroll-name{font-size:12px;font-weight:700;line-height:1.25}
        .payroll-rules-col{font-size:11px;line-height:1.25}
        .payroll-col-name-head,.payroll-col-effective-head{}
        .payroll-col-effective-val{font-size:10px;line-height:1.25}
        .payroll-col-currency-val{font-size:10px;line-height:1.2}
        .payroll-td--num{text-align:right;font-variant-numeric:tabular-nums}
        .payroll-td--center{text-align:center}
        .payroll-rule-cell{background:color-mix(in srgb,var(--card)95%,transparent)!important}
        .payroll-chip{display:inline-flex;padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700;border:1px solid var(--border);background:color-mix(in srgb,var(--card)96%,transparent)}
        .payroll-chip--ok{border-color:color-mix(in srgb,#22c55e 42%,var(--border));color:#15803d;background:color-mix(in srgb,#22c55e 10%,transparent)}
        .payroll-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:8px;border:1px solid color-mix(in srgb,var(--primary)42%,var(--border));background:color-mix(in srgb,var(--primary)12%,transparent);color:var(--text);font-size:12px;font-weight:700;cursor:pointer}
        .payroll-btn:hover{background:color-mix(in srgb,var(--primary)18%,transparent)}
        .payroll-rule-form{display:grid;gap:5px;grid-template-columns:repeat(3,minmax(100px,1fr));padding:5px;border:1px solid color-mix(in srgb,var(--border)84%,transparent);border-radius:8px;background:color-mix(in srgb,var(--card)98%,transparent)}
        .payroll-rule-list{
            margin-top:8px;
            border:1px solid color-mix(in srgb,var(--border)84%,transparent);
            border-radius:10px;
            padding:8px;
            background:color-mix(in srgb,var(--card)99%,transparent);
        }
        .payroll-rule-list summary{
            cursor:pointer;
            font-size:11px;
            font-weight:800;
            color:var(--muted);
            letter-spacing:.04em;
            text-transform:uppercase;
        }
        .payroll-rule-list[open] summary{margin-bottom:8px}
        .payroll-rule-item{
            display:grid;
            gap:6px;
            border:1px solid color-mix(in srgb,var(--border)76%,transparent);
            border-radius:10px;
            padding:8px;
            margin-top:6px;
            background:color-mix(in srgb,var(--card)96%,transparent);
        }
        .payroll-rule-item:first-of-type{margin-top:0}
        .payroll-rule-item-head{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:8px;
        }
        .payroll-rule-item-title{font-size:12px;line-height:1.3}
        .payroll-rule-code{
            display:inline-flex;
            font-size:11px;
            font-weight:800;
            border-radius:6px;
            padding:2px 6px;
            border:1px solid color-mix(in srgb,var(--primary)38%,var(--border));
            background:color-mix(in srgb,var(--primary)10%,transparent);
        }
        .payroll-rule-tags{display:flex;gap:6px;flex-wrap:wrap}
        .payroll-rule-tag{
            font-size:10px;
            font-weight:700;
            border-radius:999px;
            padding:2px 7px;
            border:1px solid color-mix(in srgb,var(--border)80%,transparent);
            background:color-mix(in srgb,var(--card)95%,transparent);
            color:var(--muted);
        }
        .payroll-rule-config{
            font-size:10px;
            line-height:1.35;
            color:var(--muted);
            background:color-mix(in srgb,var(--card)94%,transparent);
            border:1px dashed color-mix(in srgb,var(--border)76%,transparent);
            border-radius:8px;
            padding:8px;
            display:grid;
            gap:6px;
        }
        .payroll-rule-config-row{
            display:flex;
            justify-content:space-between;
            gap:8px;
            align-items:flex-start;
            border-bottom:1px solid color-mix(in srgb,var(--border)62%,transparent);
            padding-bottom:4px;
        }
        .payroll-rule-config-row:last-child{border-bottom:none;padding-bottom:0}
        .payroll-rule-config-key{font-weight:700;color:var(--text);font-size:10px}
        .payroll-rule-config-value{text-align:right;font-size:10px}
        .payroll-rule-config-slabs{
            display:flex;
            flex-wrap:wrap;
            gap:6px;
            justify-content:flex-end;
        }
        .payroll-rule-config-slab{
            font-size:9px;
            border:1px solid color-mix(in srgb,var(--border)72%,transparent);
            border-radius:999px;
            padding:2px 7px;
            background:color-mix(in srgb,var(--card)97%,transparent);
        }
        .payroll-rule-empty{
            font-size:11px;
            color:var(--muted);
            padding:6px 2px;
        }

        .payroll-add-rule-summary{cursor:pointer}
        .payroll-add-rule-details summary{list-style:none}
        .payroll-add-rule-details summary::-webkit-details-marker{display:none}
        .payroll-add-rule-details summary::marker{display:none}

        .payroll-rule-set-cards{display:flex;flex-direction:column;gap:12px}
        .payroll-rule-set-card{border:1px solid color-mix(in srgb,var(--border)90%,transparent);border-radius:12px;background:color-mix(in srgb,var(--card)100%,transparent);padding:12px 14px;width:100%}
        .payroll-rule-set-head{display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
        .payroll-rule-set-right{display:flex;flex-direction:column;align-items:flex-end;gap:6px;min-width:140px}
        .payroll-rule-set-effective{font-size:9.5px;line-height:1.25;color:var(--muted)}
        .payroll-rule-set-line{display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:11px;color:var(--muted)}
        .payroll-rule-set-line .payroll-name{font-size:12px}
        .payroll-rule-set-sep{opacity:.65}
        @media (max-width:720px){.payroll-rule-set-cards{gap:10px}}

        /* Modal */
        .payroll-modal-overlay{
            position:fixed;inset:0;
            background:rgba(2,6,23,.55);
            backdrop-filter:blur(3px);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:1100;
            padding:18px;
        }
        .payroll-modal-overlay.is-open{display:flex}
        .payroll-modal{
            width:100%;
            max-width:760px;
            border:1px solid color-mix(in srgb,var(--border)86%,transparent);
            border-radius:16px;
            background:linear-gradient(180deg,color-mix(in srgb,var(--card)96%,#fff 4%),var(--card));
            box-shadow:0 22px 55px rgba(2,6,23,.35);
            padding:14px;
        }
        .payroll-modal__head{
            display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
            margin-bottom:12px;
            padding:2px 2px 8px;
            border-bottom:1px solid color-mix(in srgb,var(--border)80%,transparent);
        }
        .payroll-modal__title{margin:0;font-size:1.05rem;font-weight:900;letter-spacing:-.01em}
        .payroll-modal__sub{margin:4px 0 0;font-size:11px;line-height:1.35;color:var(--muted)}
        .payroll-modal__close{
            border:1px solid color-mix(in srgb,var(--border)84%,transparent);background:color-mix(in srgb,var(--card)97%,transparent);color:var(--muted);
            font-size:20px;line-height:1;cursor:pointer;padding:4px 8px;border-radius:10px;
        }
        .payroll-modal__close:hover{background:color-mix(in srgb,var(--primary)12%,transparent);color:var(--text)}
        .payroll-modal__form-wrap{
            border:1px solid color-mix(in srgb,var(--border)82%,transparent);
            border-radius:12px;
            background:color-mix(in srgb,var(--card)97%,transparent);
            padding:10px;
        }
        .payroll-modal__actions{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap;margin-top:12px}
        .payroll-modal__actions .payroll-btn{padding:7px 12px}
        .payroll-modal__btn-primary{
            border-color:color-mix(in srgb,var(--primary)60%,var(--border));
            background:linear-gradient(180deg,color-mix(in srgb,var(--primary)28%,transparent),color-mix(in srgb,var(--primary)18%,transparent));
        }
        .payroll-modal__btn-secondary{
            border-color:color-mix(in srgb,var(--border)88%,transparent);
            background:color-mix(in srgb,var(--card)96%,transparent);
        }
        .payroll-rule-help{display:block;margin-top:4px;font-size:10px;line-height:1.35;color:var(--muted)}
        .payroll-field-help{display:block;margin-top:4px;font-size:10px;line-height:1.35;color:var(--muted)}
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
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" id="payrollRuleSetOpenBtn" class="payroll-btn"><i class="fa fa-plus"></i>{{ __('Add rule set') }}</button>
                    <a href="{{ route('hr.payroll.index') }}" class="payroll-btn"><i class="fa fa-arrow-left"></i>{{ __('Back to payroll') }}</a>
                </div>
            </div>

            <div class="payroll-rule-set-cards" style="margin-top:14px;">
                @forelse($ruleSets as $set)
                    <section class="payroll-rule-set-card">
                        <div class="payroll-rule-set-head">
                            <div>
                                <div class="payroll-rule-set-line">
                                    <span class="payroll-name">{{ $set->name }}</span>
                                    <span class="payroll-rule-set-sep">•</span>
                                    <span class="payroll-rule-set-effective">{{ $set->effective_from?->format('Y-m-d') ?? '—' }} → {{ $set->effective_to?->format('Y-m-d') ?? __('open') }}</span>
                                    <span class="payroll-rule-set-sep">•</span>
                                    <span class="emp-docs-table__meta payroll-col-currency-val">{{ $set->currency }}</span>
                                </div>
                            </div>
                            <div class="payroll-rule-set-right">
                                <div class="payroll-rules-col">{{ $set->rules_count }} {{ __('rules') }}</div>
                                @if($set->is_default)
                                    <span class="payroll-chip payroll-chip--ok">{{ __('Default') }}</span>
                                @else
                                    <span class="payroll-chip">{{ __('Not default') }}</span>
                                @endif
                            </div>
                        </div>

                        <button
                            type="button"
                            class="payroll-btn payroll-add-rule-btn"
                            data-rule-set-id="{{ $set->id }}"
                            data-action-url="{{ route('hr.payroll.rules.store', $set) }}"
                        >
                            <i class="fa fa-plus"></i>{{ __('Add rule') }}
                        </button>

                        <details class="payroll-rule-list">
                            <summary>{{ __('View added rules') }} ({{ $set->rules->count() }})</summary>
                            @forelse($set->rules as $rule)
                                <div class="payroll-rule-item">
                                    <div class="payroll-rule-item-head">
                                        <div class="payroll-rule-item-title">
                                            <span class="payroll-rule-code">{{ $rule->code }}</span>
                                            {{ $rule->name }}
                                        </div>
                                        <div class="payroll-rule-tags">
                                            <span class="payroll-rule-tag">{{ ucfirst((string) $rule->component_type) }}</span>
                                            <span class="payroll-rule-tag">{{ ucfirst((string) $rule->calculation_mode) }}</span>
                                        </div>
                                    </div>
                                    @if(!empty($rule->config_json))
                                        @php
                                            $config = is_array($rule->config_json) ? $rule->config_json : [];
                                        @endphp
                                        <div class="payroll-rule-config">
                                            @foreach($config as $configKey => $configValue)
                                                @if($configKey === 'slabs' && is_array($configValue))
                                                    <div class="payroll-rule-config-row">
                                                        <span class="payroll-rule-config-key">{{ __('Slabs') }}</span>
                                                        <span class="payroll-rule-config-slabs">
                                                            @foreach($configValue as $slab)
                                                                @php
                                                                    $from = (float) ($slab['from'] ?? 0);
                                                                    $to = $slab['to'] ?? null;
                                                                    $percent = (float) ($slab['percent'] ?? 0);
                                                                @endphp
                                                                <span class="payroll-rule-config-slab">
                                                                    {{ number_format($from, 0) }} - {{ $to === null ? __('above') : number_format((float) $to, 0) }} : {{ number_format($percent, 2) }}%
                                                                </span>
                                                            @endforeach
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="payroll-rule-config-row">
                                                        <span class="payroll-rule-config-key">{{ ucwords(str_replace('_', ' ', (string) $configKey)) }}</span>
                                                        <span class="payroll-rule-config-value">
                                                            @if(is_array($configValue))
                                                                {{ implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE), $configValue)) }}
                                                            @else
                                                                {{ is_bool($configValue) ? ($configValue ? __('Yes') : __('No')) : (string) $configValue }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="payroll-rule-empty">{{ __('No rules added yet.') }}</div>
                            @endforelse
                        </details>
                    </section>
                @empty
                    <p class="muted" style="margin:0;">{{ __('No rule sets yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    {{-- Rule set create modal --}}
    <div id="payrollRuleSetModalOverlay" class="payroll-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="payrollRuleSetModalTitle">
        <div class="payroll-modal">
            <div class="payroll-modal__head">
                <div>
                    <h3 id="payrollRuleSetModalTitle" class="payroll-modal__title">{{ __('Add rule set') }}</h3>
                    <p class="payroll-modal__sub">{{ __('Create a payroll rule set with effective dates and description, then add EPF/ETF/APIT/custom rules.') }}</p>
                </div>
                <button type="button" id="payrollRuleSetModalClose" class="payroll-modal__close" aria-label="{{ __('Close') }}">×</button>
            </div>

            <form method="post" action="{{ route('hr.payroll.rule-sets.store') }}">
                @csrf
                <div class="payroll-modal__form-wrap">
                    <div class="payroll-grid">
                        <div class="payroll-field">
                            <label>{{ __('Rule set name') }}</label>
                            <input type="text" name="name" class="payroll-input" value="{{ old('name') }}" required>
                            <small class="payroll-field-help">{{ __('A clear template name, e.g. Sri Lanka Standard 2026.') }}</small>
                        </div>
                        <div class="payroll-field">
                            <label>{{ __('Currency') }}</label>
                            <input type="text" name="currency" class="payroll-input" value="{{ old('currency', $business->currency ?? 'LKR') }}">
                            <small class="payroll-field-help">{{ __('Payroll currency for this rule set (LKR, USD, etc.).') }}</small>
                        </div>
                        <div class="payroll-field">
                            <label>{{ __('Effective from') }}</label>
                            <input type="date" name="effective_from" class="payroll-input" value="{{ old('effective_from', now()->toDateString()) }}" required>
                            <small class="payroll-field-help">{{ __('Start date from which this rule set is valid.') }}</small>
                        </div>
                        <div class="payroll-field">
                            <label>{{ __('Effective to') }}</label>
                            <input type="date" name="effective_to" class="payroll-input" value="{{ old('effective_to') }}">
                            <small class="payroll-field-help">{{ __('Optional end date; leave empty for open-ended usage.') }}</small>
                        </div>
                        <div class="payroll-field">
                            <label>{{ __('Default') }}</label>
                            <select name="is_default" class="payroll-input"><option value="0" @selected(old('is_default', '0') === '0')>{{ __('No') }}</option><option value="1" @selected(old('is_default') === '1')>{{ __('Yes') }}</option></select>
                            <small class="payroll-field-help">{{ __('Set as default to auto-select this rule set for new cycles.') }}</small>
                        </div>
                        <div class="payroll-field" style="grid-column:1/-1;">
                            <label>{{ __('Description') }}</label>
                            <textarea name="notes" class="payroll-input" rows="3" placeholder="{{ __('Add notes about statutory scope, assumptions, or period applicability...') }}">{{ old('notes') }}</textarea>
                            <small class="payroll-field-help">{{ __('Optional internal notes for this rule set (saved as description).') }}</small>
                        </div>
                    </div>
                </div>

                <div class="payroll-modal__actions">
                    <button type="button" id="payrollRuleSetModalCancel" class="payroll-btn payroll-modal__btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="payroll-btn payroll-modal__btn-primary"><i class="fa fa-plus"></i>{{ __('Create rule set') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Salary/payroll rule modal (single instance) --}}
    <div id="payrollRuleModalOverlay" class="payroll-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="payrollRuleModalTitle">
        <div class="payroll-modal">
            <div class="payroll-modal__head">
                <div>
                    <h3 id="payrollRuleModalTitle" class="payroll-modal__title">{{ __('Add payroll rule') }}</h3>
                    <p class="payroll-modal__sub">{{ __('Define a professional payroll component with clear type, calculation mode, and JSON configuration.') }}</p>
                </div>
                <button type="button" id="payrollRuleModalClose" class="payroll-modal__close" aria-label="{{ __('Close') }}">×</button>
            </div>

            <form id="payrollRuleModalForm" method="post" action="">
                @csrf
                <input type="hidden" name="rule_set_id" id="payrollRuleModalRuleSetId" value="{{ old('rule_set_id') }}">

                <div class="payroll-modal__form-wrap">
                <div class="payroll-rule-form">
                    <div class="payroll-field" style="grid-column:1/-1;">
                        <label>{{ __('Rule code') }}</label>
                        <input type="text" name="code" class="payroll-input" placeholder="EPF_EMPLOYEE" value="{{ old('code') }}" required>
                        <small class="payroll-rule-help">{{ __('Short unique code, uppercase, used in reports and formulas.') }}</small>
                    </div>
                    <div class="payroll-field" style="grid-column:1/-1;">
                        <label>{{ __('Rule name') }}</label>
                        <input type="text" name="name" class="payroll-input" placeholder="{{ __('EPF employee contribution') }}" value="{{ old('name') }}" required>
                        <small class="payroll-rule-help">{{ __('Human friendly label visible on payslips and salary sheet.') }}</small>
                    </div>
                    <div class="payroll-field">
                        <label>{{ __('Component type') }}</label>
                        <select name="component_type" class="payroll-input" required>
                            <option value="earning" @selected(old('component_type') === 'earning')>{{ __('Earning') }}</option>
                            <option value="deduction" @selected(old('component_type') === 'deduction')>{{ __('Deduction') }}</option>
                            <option value="statutory" @selected(old('component_type') === 'statutory')>{{ __('Statutory') }}</option>
                            <option value="overtime" @selected(old('component_type') === 'overtime')>{{ __('Overtime') }}</option>
                        </select>
                        <small class="payroll-rule-help">{{ __('Choose whether this is income, deduction, statutory, or overtime.') }}</small>
                    </div>
                    <div class="payroll-field">
                        <label>{{ __('Calculation mode') }}</label>
                        <select name="calculation_mode" class="payroll-input" required>
                            <option value="fixed" @selected(old('calculation_mode') === 'fixed')>{{ __('Fixed') }}</option>
                            <option value="percentage" @selected(old('calculation_mode') === 'percentage')>{{ __('Percentage') }}</option>
                            <option value="slab" @selected(old('calculation_mode') === 'slab')>{{ __('Slab') }}</option>
                            <option value="formula" @selected(old('calculation_mode') === 'formula')>{{ __('Formula') }}</option>
                        </select>
                        <small class="payroll-rule-help">{{ __('How the amount is calculated (flat value, % of base, tax slabs, or formula).') }}</small>
                    </div>
                    <div class="payroll-field">
                        <label>{{ __('Sort order') }}</label>
                        <input type="number" name="sort_order" class="payroll-input" placeholder="{{ __('Order') }}" value="{{ old('sort_order', 0) }}" min="0">
                        <small class="payroll-rule-help">{{ __('Controls evaluation/display order. Lower numbers run first.') }}</small>
                    </div>
                    <div class="payroll-field" style="grid-column:1/-1;">
                        <label>{{ __('Config JSON') }}</label>
                        <input type="text" name="config_json" class="payroll-input" placeholder='{"amount":1000} or {"base_field":"basic_salary","percent":8}' value="{{ old('config_json') }}">
                        <small class="payroll-rule-help">{{ __('Advanced settings as JSON. For example fixed amount, percentage base field, or slab definitions.') }}</small>
                    </div>
                </div>
                </div>

                <div class="payroll-modal__actions">
                    <button type="button" id="payrollRuleModalCancel" class="payroll-btn payroll-modal__btn-secondary">{{ __('Cancel') }}</button>
                    <button type="submit" class="payroll-btn payroll-modal__btn-primary">
                        <i class="fa fa-plus"></i>{{ __('Save rule') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const overlay = document.getElementById('payrollRuleModalOverlay');
            const closeBtn = document.getElementById('payrollRuleModalClose');
            const cancelBtn = document.getElementById('payrollRuleModalCancel');
            const form = document.getElementById('payrollRuleModalForm');
            const ruleSetIdInput = document.getElementById('payrollRuleModalRuleSetId');
            const ruleSetOverlay = document.getElementById('payrollRuleSetModalOverlay');
            const ruleSetOpenBtn = document.getElementById('payrollRuleSetOpenBtn');
            const ruleSetCloseBtn = document.getElementById('payrollRuleSetModalClose');
            const ruleSetCancelBtn = document.getElementById('payrollRuleSetModalCancel');

            function openModalFromButton(btn) {
                if (!overlay || !form || !ruleSetIdInput || !btn) return;
                const actionUrl = btn.getAttribute('data-action-url') || '';
                const ruleSetId = btn.getAttribute('data-rule-set-id') || '';
                form.action = actionUrl;
                ruleSetIdInput.value = ruleSetId;
                overlay.classList.add('is-open');
            }

            function closeModal() {
                if (!overlay) return;
                overlay.classList.remove('is-open');
            }

            function openRuleSetModal() {
                if (!ruleSetOverlay) return;
                ruleSetOverlay.classList.add('is-open');
            }

            function closeRuleSetModal() {
                if (!ruleSetOverlay) return;
                ruleSetOverlay.classList.remove('is-open');
            }

            document.querySelectorAll('.payroll-add-rule-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModalFromButton(btn);
                });
            });

            closeBtn && closeBtn.addEventListener('click', closeModal);
            cancelBtn && cancelBtn.addEventListener('click', closeModal);
            overlay && overlay.addEventListener('click', function (e) {
                if (e.target === overlay) closeModal();
            });
            ruleSetOpenBtn && ruleSetOpenBtn.addEventListener('click', openRuleSetModal);
            ruleSetCloseBtn && ruleSetCloseBtn.addEventListener('click', closeRuleSetModal);
            ruleSetCancelBtn && ruleSetCancelBtn.addEventListener('click', closeRuleSetModal);
            ruleSetOverlay && ruleSetOverlay.addEventListener('click', function (e) {
                if (e.target === ruleSetOverlay) closeRuleSetModal();
            });

            const oldRuleSetId = @json(old('rule_set_id'));
            if (oldRuleSetId) {
                document.querySelectorAll('.payroll-add-rule-btn').forEach(function (btn) {
                    if ((btn.getAttribute('data-rule-set-id') || '') === String(oldRuleSetId)) {
                        openModalFromButton(btn);
                    }
                });
            }

            const hasRuleSetOldInput = @json(
                old('name') !== null
                || old('currency') !== null
                || old('effective_from') !== null
                || old('effective_to') !== null
                || old('is_default') !== null
                || old('notes') !== null
            );
            if (hasRuleSetOldInput) {
                openRuleSetModal();
            }
        })();
    </script>
@endsection
