@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="mt-2 text-sm text-gray-700">Welcome back, {{ Auth::user()->name }}!</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <stats-card
            title="Total Users"
            value="1,234"
            change="+12%"
            icon="users"
            color="indigo"
        ></stats-card>

        <stats-card
            title="Active Projects"
            value="56"
            change="+8%"
            icon="document"
            color="green"
        ></stats-card>

        <stats-card
            title="Completed Tasks"
            value="892"
            change="+23%"
            icon="check"
            color="blue"
        ></stats-card>

        <stats-card
            title="Revenue"
            value="$45.2k"
            change="-2%"
            icon="chart"
            color="yellow"
        ></stats-card>
    </div>

    <!-- Additional Content Area -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Left Column - Active Campaigns -->
        <active-campaigns-list></active-campaigns-list>

        <!-- Right Column -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <p class="text-gray-600">Quick action buttons will go here...</p>
            </div>
        </div>
    </div>
</div>
@endsection
