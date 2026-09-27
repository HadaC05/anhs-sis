@extends('users.principal.layout')

@section('title', 'Dashboard')

@section('content')
<div class="principal-dashboard">
    @include('users.partials.enrollment-dashboard', ['dashboardContext' => 'principal'])
</div>
@endsection
