@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-sm text-gray-700">Welcome back, {{ Auth::user()->name }}!</p>
    </div>

    <!-- Dashboard Content (Vue Component) -->
    <dashboard-wrapper></dashboard-wrapper>
</div>
@endsection
