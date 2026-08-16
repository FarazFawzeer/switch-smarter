@extends('layouts.vertical', ['subtitle' => 'Repair'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Job Types', 'subtitle' => 'Repair'])
    @include('admin.job-types._table')
@endsection