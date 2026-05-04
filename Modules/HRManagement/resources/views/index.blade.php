@extends('theme::layouts.app', ['title' => 'HR · '.$business->name, 'heading' => 'Human resources'])

@section('content')
@php
    $s = $hrSummary ?? [];
    $cur = ($s['currency'] ?? '') !== '' ? $s['currency'].' ' : '';
    $fmtMoney = function (float $n) use ($cur): string {
        return $cur.number_format($n, abs($n - round($n)) < 0.0001 ? 0 : 2);
    };
    $chart = $headcountChart ?? ['hasData' => false, 'labels' => [], 'datasets' => [], 'note' => ''];
    $bdays = $upcomingBirthdays ?? [];
    $bdaysWindow = (int) ($upcomingBirthdaysWindowDays ?? 30);
@endphp
<style>
.hr-hub-sum{
    display:grid;gap:6px 8px;grid-template-columns:1fr;align-items:stretch;
}
@media (min-width:560px){
    .hr-hub-sum{grid-template-columns:repeat(3,minmax(0,1fr));}
}

.hr-hub-card{
    --hr-accent:var(--primary);
    box-sizing:border-box;border-radius:6px;min-height:0;
    border:1px solid color-mix(in srgb,var(--border)92%,transparent);
    border-left:2px solid color-mix(in srgb,var(--hr-accent)65%,var(--border));
    background:var(--card);
}
.hr-hub-card__inner{padding:7px 10px 8px;display:flex;flex-direction:column;min-height:100%;}
.hr-hub-card__row{display:block;}
.hr-hub-card__body{min-width:0;}
.hr-hub-card__k{margin:0 0 3px;font-size:10px;font-weight:600;letter-spacing:.02em;color:var(--muted);line-height:1.25;}
.hr-hub-card__v{margin:0;font-size:clamp(0.85rem,.35vw + .78rem,0.98rem);font-weight:700;letter-spacing:-.015em;color:var(--text);line-height:1.15;}
.hr-hub-card__v--money{font-variant-numeric:tabular-nums;}
.hr-hub-card__sub{margin:5px 0 0;font-size:10px;line-height:1.4;color:var(--muted);}
.hr-hub-card__sub a{
    display:inline;font-size:10px;font-weight:600;color:var(--primary);text-decoration:none;
}
.hr-hub-card__sub a:hover{text-decoration:underline;}
.hr-hub-card__link-extra{display:inline-block;margin-top:4px;}
.hr-hub-card__policy-line{margin:2px 0 0;font-size:10px;line-height:1.45;color:var(--text);font-weight:500;word-break:break-word;}
.hr-hub-card__policy-line .sep{color:color-mix(in srgb,var(--muted)80%,var(--border));margin:0 .25em;}
.hr-hub-chart-wrap{border:1px solid var(--border);border-radius:14px;background:color-mix(in srgb,var(--card)97%,transparent);padding:14px 14px 16px;margin-bottom:16px;}
.hr-hub-chart-wrap h3{margin:0 0 10px;font-size:15px;font-weight:800;}

.hr-hub-shortcuts-wrap{
    margin-top:4px;border:1px solid var(--border);border-radius:14px;
    background:color-mix(in srgb,var(--card)97%,transparent);
    padding:14px 14px 16px;
}
.hr-hub-shortcuts-wrap__title{margin:0 0 12px;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.hr-hub-shortcuts-wrap__title i{opacity:.85;color:var(--primary);}
.hr-hub-shortcuts{
    display:grid;gap:8px 10px;
    grid-template-columns:1fr;
}
@media (min-width:560px){
    .hr-hub-shortcuts{grid-template-columns:repeat(3,minmax(0,1fr));}
}

.hr-hub-shortcut{
    --sc-accent:var(--primary);
    display:flex;align-items:flex-start;gap:10px;box-sizing:border-box;min-height:0;
    padding:11px 11px 10px;text-decoration:none;color:inherit;border-radius:11px;
    border:1px solid color-mix(in srgb,var(--border)88%,transparent);
    background:
        radial-gradient(ellipse 100% 80% at 100% -20%,color-mix(in srgb,var(--sc-accent) 10%,transparent) 0%,transparent 48%),
        linear-gradient(168deg,color-mix(in srgb,var(--card) 100%,transparent),color-mix(in srgb,var(--card) 96%,var(--border)));
    box-shadow:0 1px 2px rgba(0,0,0,.04);
    transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease;
}
.hr-hub-shortcut:hover{
    transform:translateY(-2px);
    border-color:color-mix(in srgb,var(--sc-accent) 32%,var(--border));
    box-shadow:0 4px 14px rgba(0,0,0,.07),0 2px 8px color-mix(in srgb,var(--sc-accent) 8%,transparent);
}
:is(html[data-theme="dark"],html[data-theme*="dark"]) .hr-hub-shortcut:hover{
    box-shadow:0 8px 24px rgba(0,0,0,.35);
}
.hr-hub-shortcut__icon{
    flex-shrink:0;width:32px;height:32px;border-radius:9px;display:grid;place-items:center;font-size:13px;line-height:1;
    color:var(--sc-accent);
    background:linear-gradient(145deg,color-mix(in srgb,var(--sc-accent) 16%,transparent),color-mix(in srgb,var(--sc-accent) 6%,transparent));
    border:1px solid color-mix(in srgb,var(--sc-accent) 26%,var(--border));
}
.hr-hub-shortcut__body{flex:1;min-width:0;}
.hr-hub-shortcut__h{margin:0 0 3px;font-size:13px;font-weight:700;line-height:1.25;color:var(--text);letter-spacing:-.01em;}
.hr-hub-shortcut__p{margin:0;font-size:11px;line-height:1.42;color:color-mix(in srgb,var(--muted) 94%,var(--text));}

.hr-hub-bdays-wrap{
    margin-bottom:16px;border:1px solid var(--border);border-radius:14px;
    background:color-mix(in srgb,var(--card)97%,transparent);padding:14px 14px 16px;
}
.hr-hub-bdays-wrap h3{margin:0 0 10px;font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.hr-hub-bdays-wrap h3 i{opacity:.85;color:#db2777;}
.hr-hub-bdays__lead{margin:0 0 12px;font-size:12px;line-height:1.45;color:var(--muted);max-width:80ch;}
.hr-hub-bdays__list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:0;}
.hr-hub-bdays__row{
    display:flex;align-items:center;gap:12px;padding:10px 4px;border-bottom:1px solid color-mix(in srgb,var(--border)75%,transparent);
    text-decoration:none;color:inherit;border-radius:8px;margin:0 -4px;transition:background .14s ease;
}
.hr-hub-bdays__row:last-child{border-bottom:none;}
.hr-hub-bdays__row:hover{background:color-mix(in srgb,var(--primary)6%,transparent);}
.hr-hub-bdays__thumb{
    flex-shrink:0;width:40px;height:40px;border-radius:50%;overflow:hidden;border:1px solid color-mix(in srgb,var(--border)88%,transparent);
    background:color-mix(in srgb,#db2777 12%,var(--card));font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;
    color:color-mix(in srgb,#db2777 55%,var(--text));
}
.hr-hub-bdays__thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.hr-hub-bdays__body{flex:1;min-width:0;}
.hr-hub-bdays__name{margin:0 0 2px;font-size:14px;font-weight:700;color:var(--text);letter-spacing:-.01em;}
.hr-hub-bdays__sub{margin:0;font-size:11px;color:var(--muted);line-height:1.35;}
.hr-hub-bdays__when{text-align:right;flex-shrink:0;}
.hr-hub-bdays__date{margin:0 0 2px;font-size:13px;font-weight:700;color:var(--text);white-space:nowrap;}
.hr-hub-bdays__badge{margin:0;font-size:11px;font-weight:600;color:#db2777;}

.hr-hub-layout{
    display:grid;gap:clamp(14px,2vw,20px);grid-template-columns:1fr;
}
@media (min-width:1000px){
    .hr-hub-layout{
        grid-template-columns:minmax(0,1fr) minmax(280px,340px);
        align-items:start;
    }
    .hr-hub-layout__aside{
        position:sticky;top:calc(env(safe-area-inset-top,0px) + 72px);align-self:start;
        max-height:calc(100vh - env(safe-area-inset-top,0px) - 88px);
        overflow-y:auto;overscroll-behavior:contain;padding-right:4px;
    }
}
.hr-hub-layout__main{min-width:0;}

.hr-hub-aside__block{
    border:1px solid color-mix(in srgb,var(--border)88%,transparent);
    border-radius:12px;background:color-mix(in srgb,var(--card)98%,transparent);
    padding:12px 12px 14px;margin-bottom:12px;
}
.hr-hub-aside__block:last-child{margin-bottom:0;}
.hr-hub-aside__h{margin:0 0 8px;font-size:14px;font-weight:800;display:flex;align-items:center;gap:8px;letter-spacing:-.02em;}
.hr-hub-aside__h i{opacity:.88;color:#0d9488;}
.hr-hub-aside__block[aria-labelledby="hr-aside-complaint-title"] .hr-hub-aside__h i{color:#b45309;}
.hr-hub-aside__lead{margin:0 0 10px;font-size:11px;line-height:1.45;color:var(--muted);}
.hr-hub-aside__empty{margin:0 0 8px;font-size:12px;color:var(--muted);line-height:1.45;}
.hr-hub-aside__err{
    margin:0 0 10px;padding:8px 10px 8px 22px;border-radius:8px;font-size:12px;line-height:1.4;
    border:1px solid color-mix(in srgb,#f87171 38%,var(--border));
    background:color-mix(in srgb,#f87171 8%,transparent);color:var(--text);
}
.hr-hub-aside__list{list-style:none;margin:0;padding:0;}
.hr-hub-aside__item{padding:10px 0;border-bottom:1px solid color-mix(in srgb,var(--border)72%,transparent);}
.hr-hub-aside__item:last-child{border-bottom:none;}
.hr-hub-aside__item-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:4px;}
.hr-hub-aside__link{font-size:13px;font-weight:700;color:var(--primary);text-decoration:none;}
.hr-hub-aside__link:hover{text-decoration:underline;}
.hr-hub-aside__pill{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding:2px 7px;border-radius:999px;flex-shrink:0;}
.hr-hub-aside__pill--pending{color:#b45309;background:color-mix(in srgb,#b45309 12%,transparent);border:1px solid color-mix(in srgb,#b45309 28%,var(--border));}
.hr-hub-aside__pill--open{color:#b91c1c;background:color-mix(in srgb,#ef4444 10%,transparent);border:1px solid color-mix(in srgb,#ef4444 26%,var(--border));}
.hr-hub-aside__meta{margin:0;font-size:11px;color:var(--muted);line-height:1.35;}
.hr-hub-aside__strong{margin:4px 0 2px;font-size:12px;font-weight:700;color:var(--text);line-height:1.3;}
.hr-hub-aside__note{margin:0;font-size:11px;color:color-mix(in srgb,var(--muted)92%,var(--text));line-height:1.4;}
.hr-hub-aside__actions{margin-top:8px;}
.hr-hub-aside__inline-form{display:flex;flex-wrap:wrap;gap:6px;}
.hr-hub-aside__btn{
    padding:5px 10px;font-size:11px;font-weight:600;border-radius:8px;cursor:pointer;font-family:inherit;border:1px solid var(--border);
    background:color-mix(in srgb,var(--card)94%,transparent);color:var(--text);
}
.hr-hub-aside__btn--ok{border-color:color-mix(in srgb,#22c55e 35%,var(--border));color:color-mix(in srgb,#15803d 92%,var(--text));}
.hr-hub-aside__btn--no{border-color:color-mix(in srgb,var(--border)90%,transparent);color:var(--muted);}
.hr-hub-aside__btn:hover{filter:brightness(1.03);}
.hr-hub-aside__sep{border:none;border-top:1px dashed color-mix(in srgb,var(--border)85%,transparent);margin:12px 0;}
.hr-hub-aside__form-title{margin:0 0 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);}
.hr-hub-aside__form{display:flex;flex-direction:column;gap:8px;}
.hr-hub-aside__lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);}
.hr-hub-aside__input,.hr-hub-aside__textarea{
    width:100%;box-sizing:border-box;padding:8px 9px;font-size:12px;border-radius:8px;border:1px solid var(--border);
    background:var(--card);color:var(--text);font-family:inherit;
}
.hr-hub-aside__textarea{resize:vertical;min-height:52px;line-height:1.4;}
.hr-hub-aside__submit{
    margin-top:4px;padding:8px 12px;font-size:12px;font-weight:700;border-radius:9px;border:1px solid color-mix(in srgb,var(--primary)40%,var(--border));
    background:color-mix(in srgb,var(--primary)14%,transparent);color:var(--text);cursor:pointer;font-family:inherit;
}
.hr-hub-aside__submit:hover{background:color-mix(in srgb,var(--primary)20%,transparent);}

.hr-hub-aside__leave-cta{
    display:inline-flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;row-gap:6px;
    width:100%;box-sizing:border-box;margin:0;padding:10px 12px;
    font-size:13px;font-weight:800;font-family:inherit;letter-spacing:-.015em;text-align:center;text-decoration:none;
    border-radius:10px;border:1px solid color-mix(in srgb,var(--primary)40%,var(--border));
    background:color-mix(in srgb,var(--primary)14%,transparent);color:var(--text);cursor:pointer;
    transition:background .15s ease,border-color .15s ease;
}
.hr-hub-aside__leave-cta:hover{
    background:color-mix(in srgb,var(--primary)22%,transparent);
    border-color:color-mix(in srgb,var(--primary)52%,var(--border));
    color:inherit;
}
.hr-hub-aside__leave-cta-badge{
    flex-shrink:0;display:inline-grid;place-items:center;min-width:22px;height:22px;padding:0 8px;
    font-size:11px;font-weight:800;color:#fff;border-radius:999px;background:#0d9488;box-sizing:border-box;
}
</style>

    @if(session('status'))
        <div class="card" style="margin-bottom:14px;background:linear-gradient(135deg,color-mix(in srgb,#22c55e 14%,transparent),transparent);border-color:color-mix(in srgb,#22c55e 45%,var(--border));max-width:none;">
            <strong style="color:color-mix(in srgb,#22c55e 85%,var(--text));">{{ session('status') }}</strong>
        </div>
    @endif

    <div class="card" style="max-width:none;">
        <h2 style="margin:0 0 10px;font-size:clamp(1.08rem,2vw,1.25rem);">HR hub — {{ $business->name }}</h2>
        @if($employeeBandLabel ?? null)
            <p class="muted" style="margin:0 0 14px;line-height:1.45;font-size:13px;">{{ __('Headcount tier:') }} <strong>{{ $employeeBandLabel }}</strong></p>
        @endif
        <p class="muted" style="margin:-6px 0 16px;line-height:1.45;font-size:13px;">
            <a href="{{ route('settings.business', ['tab' => 'hr']) }}" style="color:var(--primary);font-weight:600;">{{ __('Business settings → HR') }}</a>
            <span class="muted"> · </span>{{ __('Work days, leave policy, holidays, deductions notes, head of HR, and more.') }}
        </p>

        <div class="hr-hub-layout">
        <div class="hr-hub-layout__main">

        <div class="hr-hub-sum" style="margin-bottom:14px;">
            <article class="hr-hub-card" style="--hr-accent:#6366f1;">
                <div class="hr-hub-card__inner">
                    <div class="hr-hub-card__body">
                        <p class="hr-hub-card__k">{{ __('Departments') }}</p>
                        <p class="hr-hub-card__v">{{ number_format((int) ($s['department_count'] ?? 0)) }}</p>
                        <p class="hr-hub-card__sub"><a href="{{ route('hr.departments.index') }}">{{ __('Catalogue') }}</a></p>
                    </div>
                </div>
            </article>
            <article class="hr-hub-card" style="--hr-accent:#0d9488;">
                <div class="hr-hub-card__inner">
                    <div class="hr-hub-card__body">
                        <p class="hr-hub-card__k">{{ __('Employees') }}</p>
                        <p class="hr-hub-card__v">{{ number_format((int) ($s['employee_count'] ?? 0)) }}</p>
                        <p class="hr-hub-card__sub"><a href="{{ route('hr.employees.index') }}">{{ __('Directory') }}</a></p>
                    </div>
                </div>
            </article>
            <article class="hr-hub-card" style="--hr-accent:#2563eb;">
                <div class="hr-hub-card__inner">
                    <div class="hr-hub-card__body">
                        <p class="hr-hub-card__k">{{ __('Designations') }}</p>
                        <p class="hr-hub-card__v">{{ number_format((int) ($s['designation_count'] ?? 0)) }}</p>
                        <p class="hr-hub-card__sub"><a href="{{ route('hr.job-titles.index') }}">{{ __('Job titles') }}</a></p>
                    </div>
                </div>
            </article>
            <article class="hr-hub-card" style="--hr-accent:#0891b2;">
                <div class="hr-hub-card__inner">
                    <div class="hr-hub-card__body">
                        <p class="hr-hub-card__k">{{ __('Holidays') }}</p>
                        <p class="hr-hub-card__v">{{ number_format((int) ($s['holiday_count'] ?? 0)) }}</p>
                        <p class="hr-hub-card__sub"><a href="{{ route('settings.business', ['tab' => 'hr']) }}">{{ __('HR settings') }}</a></p>
                    </div>
                </div>
            </article>
            <article class="hr-hub-card" style="--hr-accent:#d97706;">
                <div class="hr-hub-card__inner">
                    <div class="hr-hub-card__body">
                        <p class="hr-hub-card__k">{{ __('Monthly gross (on file)') }}</p>
                        <p class="hr-hub-card__v hr-hub-card__v--money">{{ $fmtMoney((float) ($s['monthly_salary_total'] ?? 0)) }}</p>
                        <p class="hr-hub-card__sub">
                            @php
                                $withSal = (int) ($s['employees_with_salary'] ?? 0);
                                $missSal = (int) ($s['employees_missing_salary'] ?? 0);
                            @endphp
                            @if($withSal === 0)
                                {{ __('No gross recorded yet.') }}
                            @else
                                {{ __(':count with gross', ['count' => number_format($withSal)]) }}@if($missSal > 0) · {{ __(':n missing', ['n' => number_format($missSal)]) }}@endif.
                            @endif
                            <a href="{{ route('hr.employees.index') }}" class="hr-hub-card__link-extra">{{ __('Review employees') }}</a>
                        </p>
                    </div>
                </div>
            </article>
            @if(($s['annual_leave_days'] ?? null) !== null || ($s['casual_leave_days'] ?? null) !== null || ($s['workdays_per_month'] ?? null) !== null)
                @php
                    $policyBits = [];
                    if (($s['workdays_per_month'] ?? null) !== null) {
                        $policyBits[] = __('Work :n/mo', ['n' => $s['workdays_per_month']]);
                    }
                    if (($s['annual_leave_days'] ?? null) !== null) {
                        $policyBits[] = __('Annual :n d/yr', ['n' => $s['annual_leave_days']]);
                    }
                    if (($s['casual_leave_days'] ?? null) !== null) {
                        $policyBits[] = __('Casual :n d/yr', ['n' => $s['casual_leave_days']]);
                    }
                @endphp
                <article class="hr-hub-card" style="--hr-accent:#64748b;">
                    <div class="hr-hub-card__inner">
                        <div class="hr-hub-card__body">
                            <p class="hr-hub-card__k">{{ __('Policy reference') }}</p>
                            <p class="hr-hub-card__policy-line">{{ implode(' · ', $policyBits) }}</p>
                            <p class="hr-hub-card__sub"><a href="{{ route('settings.business', ['tab' => 'hr']) }}">{{ __('Edit in settings') }}</a></p>
                        </div>
                    </div>
                </article>
            @endif
        </div>

        <div class="hr-hub-chart-wrap">
            <h3><i class="fa fa-chart-line" style="margin-right:8px;opacity:.85;"></i>{{ __('Employee growth (cumulative by hire date)') }}</h3>
            @if(! ($chart['hasData'] ?? false))
                <p class="muted" style="margin:0;line-height:1.55;font-size:14px;">{{ __('Once people are registered with hire dates, this chart shows total headcount over time.') }}</p>
                <div style="margin-top:14px;">
                    <a href="{{ route('hr.employees.index') }}" class="linkbtn" style="padding:9px 18px;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fa fa-user-plus"></i>{{ __('Go to Employees') }}
                    </a>
                </div>
            @else
                <p class="muted" style="margin:0 0 12px;font-size:12px;line-height:1.45;max-width:80ch;">{{ $chart['note'] ?? '' }}</p>
                @include('hrmanagement::departments.partials.hr-line-chart', [
                    'canvasId' => 'hr-hub-headcount-chart',
                    'chartAriaLabel' => __('Company headcount over time'),
                    'chartLabels' => $chart['labels'],
                    'chartDatasets' => $chart['datasets'],
                    'chartWrapStyle' => 'position:relative;height:min(240px,38vh);width:100%;',
                ])
                @if(count($chart['labels'] ?? []) > 0)
                    <p class="muted" style="margin:14px 0 0;text-align:center;font-size:11px;">{{ __(':count months on the timeline.', ['count' => count($chart['labels'])]) }}</p>
                @endif
            @endif
            <p class="muted" style="margin:12px 0 0;font-size:12px;">
                <a href="{{ route('hr.departments.growth') }}" style="color:var(--primary);font-weight:600;">{{ __('Per-department growth chart') }}</a>
            </p>
        </div>

        <div class="hr-hub-bdays-wrap">
            <h3><i class="fa fa-cake-candles" aria-hidden="true"></i>{{ __('Upcoming birthdays') }}</h3>
            <p class="hr-hub-bdays__lead">{{ __('Next :days days, based on date of birth on file.', ['days' => $bdaysWindow]) }}</p>
            @if(count($bdays) === 0)
                <p class="muted" style="margin:0;line-height:1.55;font-size:14px;">{{ __('No birthdays in this window. Add or complete employee dates of birth to see reminders here.') }}</p>
            @else
                <ul class="hr-hub-bdays__list" role="list">
                    @foreach($bdays as $row)
                        @php
                            $emp = $row['employee'];
                            $next = $row['next_on'];
                            $d = (int) ($row['days_until'] ?? 0);
                            if ($d === 0) {
                                $when = __('Today');
                            } elseif ($d === 1) {
                                $when = __('Tomorrow');
                            } else {
                                $when = __('In :days days', ['days' => $d]);
                            }
                        @endphp
                        <li>
                            <a class="hr-hub-bdays__row" href="{{ route('hr.employees.show', $emp) }}">
                                <span class="hr-hub-bdays__thumb" aria-hidden="true">
                                    @if($emp->profilePhotoUrl())
                                        <img src="{{ $emp->profilePhotoUrl() }}" alt="" width="40" height="40">
                                    @else
                                        {{ $emp->avatarInitials() }}
                                    @endif
                                </span>
                                <div class="hr-hub-bdays__body">
                                    <p class="hr-hub-bdays__name">{{ $emp->full_name }}</p>
                                    <p class="hr-hub-bdays__sub">{{ $emp->employee_id }}@if($emp->department)<span> · </span>{{ $emp->department->name }}@endif</p>
                                </div>
                                <div class="hr-hub-bdays__when">
                                    <p class="hr-hub-bdays__date">{{ $next->translatedFormat('M j') }}</p>
                                    <p class="hr-hub-bdays__badge">{{ $when }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="hr-hub-shortcuts-wrap">
            <h3 class="hr-hub-shortcuts-wrap__title"><i class="fa fa-bolt" aria-hidden="true"></i>{{ __('Shortcuts') }}</h3>
            <div class="hr-hub-shortcuts">
                <a class="hr-hub-shortcut" style="--sc-accent:#0d9488;" href="{{ route('hr.employees.index') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-user-group"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Employees') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Register and list staff for payroll.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#b45309;" href="{{ route('hr.payroll.index') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-money-check-dollar"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Payroll') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Runs, payslips, approvals — coming soon.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#6366f1;" href="{{ route('hr.departments.index') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-folder-tree"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Departments') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Teams, cost centers, and department settings.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#2563eb;" href="{{ route('hr.departments.growth') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-chart-line"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Department growth') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Headcount trend by team over time.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#7c3aed;" href="{{ route('hr.job-titles.index') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-id-badge"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Designations') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Job titles catalogue for this business.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#0891b2;" href="{{ route('hr.allowance-types.index') }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-list-check"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('Allowance types') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Transport, meal, and other payroll add-ons.') }}</span>
                    </span>
                </a>
                <a class="hr-hub-shortcut" style="--sc-accent:#64748b;" href="{{ route('settings.business', ['tab' => 'hr']) }}">
                    <span class="hr-hub-shortcut__icon" aria-hidden="true"><i class="fa fa-sliders"></i></span>
                    <span class="hr-hub-shortcut__body">
                        <span class="hr-hub-shortcut__h">{{ __('HR settings') }}</span>
                        <span class="hr-hub-shortcut__p">{{ __('Leave, holidays, deductions, head of HR.') }}</span>
                    </span>
                </a>
            </div>
        </div>

        </div>{{-- .hr-hub-layout__main --}}

        <aside class="hr-hub-layout__aside" aria-label="{{ __('Leave and complaints inbox') }}">
            @include('hrmanagement::hr-hub-inbox-aside')
        </aside>
        </div>{{-- .hr-hub-layout --}}

    </div>
@endsection
