@extends('users.guidance.layout')

@section('title', 'Dashboard')

@section('content')
@include('users.partials.enrollment-dashboard', ['dashboardContext' => 'guidance'])
@endsection
