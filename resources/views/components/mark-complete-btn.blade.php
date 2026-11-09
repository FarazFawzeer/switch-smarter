@props(['job', 'small' => true])
{{--
    Office "Mark as Completed" button for a started / saved job. Shown only to the technician's
    engineer or supervisor, or an admin / manager. Needs the timesheet-adjust-script partial on the page.
--}}
@php
    $showBtn = $job->check_in_time
        && in_array($job->status, \App\Http\Controllers\Admin\JobCompletionController::OPEN_STATUSES, true)
        && auth()->check()
        && \App\Http\Controllers\Admin\JobCompletionController::canComplete(auth()->user(), $job->technician);
    $needsUnit = in_array($job->job_type, ['breakdown', 'repair'], true) && ! $job->parent_job_id && ! $job->unit_status_after;
@endphp
@if ($showBtn)
    <button type="button" {{ $attributes->merge(['class' => 'btn ' . ($small ? 'btn-sm ' : '') . 'btn-outline-success mark-complete-btn']) }}
        data-url="{{ route('admin.jobs.mark-completed', $job->id) }}"
        data-who="{{ $job->job_code ? $job->job_code . ' · ' : '' }}{{ optional($job->technician)->name }} — {{ optional($job->unit)->identification_no }}"
        data-start="{{ $job->check_in_time->format('d M Y, h:i A') }}"
        data-needs-unit="{{ $needsUnit ? 1 : 0 }}"
        title="The work is done — close this job">
        <x-ui-icon name="check" size="14" stroke="2.4" /> Complete
    </button>
@endif
