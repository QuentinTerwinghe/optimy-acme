@extends('layouts.app')

@section('title', 'Login')

@section('app-container-class')
class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
@endsection

@section('content')
    <div class="max-w-md w-full space-y-8">
        <!-- Logo/Header -->
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                {{ config('app.name', 'ACME Corp') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>

        <!-- Vue Login Form Component -->
        <login-form
            login-url="{{ route('login') }}"
            csrf-token="{{ csrf_token() }}"
            old-username="{{ old('username') }}"
            :server-errors='@json($errors->messages())'
        ></login-form>

        <!-- Session Status Messages -->
        @if (session('status'))
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('status') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
