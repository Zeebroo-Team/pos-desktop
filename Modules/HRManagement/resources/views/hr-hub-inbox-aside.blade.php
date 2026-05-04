@php
    $pendingLeaveCount = (int) ($hrPendingLeaveCount ?? 0);
    $openComplaints = $hrInboxComplaints ?? collect();
    $inboxEmployees = $hrInboxEmployees ?? collect();
    $complaintErrKeys = ['complaint_employee_id', 'complaint_subject', 'complaint_body'];
    $complaintErrs = collect($complaintErrKeys)->flatMap(fn ($k) => $errors->get($k, []));
@endphp

<section class="hr-hub-aside__block" aria-labelledby="hr-aside-leave-title">
    <h3 id="hr-aside-leave-title" class="hr-hub-aside__h"><i class="fa fa-plane-departure" aria-hidden="true"></i>{{ __('Leave requests') }}</h3>
    <p class="hr-hub-aside__lead">{{ __('Open the queue to approve or reject pending time off. New entries are logged on each employee · Leave.') }}</p>
    <a href="{{ route('hr.leave-requests.index') }}" class="hr-hub-aside__leave-cta">
        {{ __('Review pending requests') }}
        @if($pendingLeaveCount > 0)
            <span class="hr-hub-aside__leave-cta-badge" aria-label="{{ __(':count pending', ['count' => $pendingLeaveCount]) }}">{{ $pendingLeaveCount }}</span>
        @endif
    </a>
</section>

<section class="hr-hub-aside__block" aria-labelledby="hr-aside-complaint-title">
    <h3 id="hr-aside-complaint-title" class="hr-hub-aside__h"><i class="fa fa-triangle-exclamation" aria-hidden="true"></i>{{ __('HR complaints') }}</h3>
    <p class="hr-hub-aside__lead">{{ __('Open issues — resolve or dismiss when handled.') }}</p>

    @if($complaintErrs->isNotEmpty())
        <ul class="hr-hub-aside__err" role="alert">
            @foreach($complaintErrs as $msg)
                <li>{{ $msg }}</li>
            @endforeach
        </ul>
    @endif

    @if($openComplaints->isEmpty())
        <p class="hr-hub-aside__empty">{{ __('No open complaints.') }}</p>
    @else
        <ul class="hr-hub-aside__list" role="list">
            @foreach($openComplaints as $c)
                <li class="hr-hub-aside__item">
                    <div class="hr-hub-aside__item-head">
                        <a href="{{ route('hr.employees.show', $c->employee) }}" class="hr-hub-aside__link">{{ $c->employee->full_name }}</a>
                        <span class="hr-hub-aside__pill hr-hub-aside__pill--open">{{ __('Open') }}</span>
                    </div>
                    <p class="hr-hub-aside__strong">{{ $c->subject }}</p>
                    <p class="hr-hub-aside__note">{{ \Illuminate\Support\Str::limit($c->body, 160) }}</p>
                    <div class="hr-hub-aside__actions">
                        <form method="post" action="{{ route('hr.complaints.update', $c) }}" class="hr-hub-aside__inline-form">
                            @csrf
                            @method('PATCH')
                            <button type="submit" name="complaint_status" value="{{ \Modules\HRManagement\Models\HrComplaint::STATUS_RESOLVED }}" class="hr-hub-aside__btn hr-hub-aside__btn--ok">{{ __('Resolve') }}</button>
                            <button type="submit" name="complaint_status" value="{{ \Modules\HRManagement\Models\HrComplaint::STATUS_DISMISSED }}" class="hr-hub-aside__btn hr-hub-aside__btn--no">{{ __('Dismiss') }}</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <hr class="hr-hub-aside__sep">

    <p class="hr-hub-aside__form-title">{{ __('Log a complaint') }}</p>
    @if($inboxEmployees->isEmpty())
        <p class="hr-hub-aside__empty">{{ __('Add employees before logging complaints.') }}</p>
    @else
        <form method="post" action="{{ route('hr.complaints.store') }}" class="hr-hub-aside__form">
            @csrf
            <label class="hr-hub-aside__lbl" for="hr-complaint-emp">{{ __('Employee involved') }}</label>
            <select name="complaint_employee_id" id="hr-complaint-emp" class="hr-hub-aside__input" required>
                <option value="">{{ __('Choose…') }}</option>
                @foreach($inboxEmployees as $emp)
                    <option value="{{ $emp->id }}" @selected((string) old('complaint_employee_id') === (string) $emp->id)>{{ $emp->full_name }} ({{ $emp->employee_id }})</option>
                @endforeach
            </select>
            <label class="hr-hub-aside__lbl" for="hr-complaint-subject">{{ __('Subject') }}</label>
            <input type="text" name="complaint_subject" id="hr-complaint-subject" class="hr-hub-aside__input" value="{{ old('complaint_subject') }}" maxlength="255" required placeholder="{{ __('Short summary') }}">
            <label class="hr-hub-aside__lbl" for="hr-complaint-body">{{ __('Details') }}</label>
            <textarea name="complaint_body" id="hr-complaint-body" class="hr-hub-aside__input hr-hub-aside__textarea" rows="3" maxlength="10000" required placeholder="{{ __('What happened?') }}">{{ old('complaint_body') }}</textarea>
            <button type="submit" class="hr-hub-aside__submit">{{ __('Submit complaint') }}</button>
        </form>
    @endif
</section>
