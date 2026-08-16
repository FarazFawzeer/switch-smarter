@extends('layouts.vertical', ['subtitle' => 'Breakdown'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Job Types', 'subtitle' => 'Breakdown'])
    @include('admin.job-types._table')
@endsection