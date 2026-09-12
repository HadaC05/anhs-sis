@extends('users.principal.layout')

@section('title', 'Dashboard')

@section('content')
@include('users.partials.enrollment-dashboard', ['dashboardContext' => 'principal'])
@endsection
