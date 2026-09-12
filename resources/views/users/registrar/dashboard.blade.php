@extends('users.registrar.layout')

@section('title', 'Dashboard')

@section('content')
@include('users.partials.enrollment-dashboard', ['dashboardContext' => 'registrar'])
@endsection
