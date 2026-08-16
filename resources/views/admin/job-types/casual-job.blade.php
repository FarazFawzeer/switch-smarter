@extends('layouts.vertical', ['subtitle' => 'Casual Jobs'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Job Types', 'subtitle' => 'Casual Jobs'])

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.job-types.casual-job.create') }}" class="btn text-white" style="background:#9B59B6;">
            <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> New Casual Job
        </a>
    </div>

    @include('admin.job-types._table')
@endsection