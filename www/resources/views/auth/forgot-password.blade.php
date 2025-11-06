@extends('layouts.app')

@section('title', 'Forgot Password')

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
                Reset your password
            </p>
            <p class="mt-1 text-center text-xs text-gray-500">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

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

        <!-- Forgot Password Form -->
        <form class="mt-8 space-y-6" action="{{ route('password.email') }}" method="POST">
            @csrf

            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="appearance-none rounded-md relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm @error('email') border-red-300 @enderror"
                        placeholder="Email address"
                    >
                </div>
            </div>

            @error('email')
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">
                                {{ $message }}
                            </p>
                        </div>
                    </div>
                </div>
            @enderror

            <div>
                <button
                    type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Send Reset Link
                </button>
            </div>

            <div class="flex items-center justify-center">
                <div class="text-sm">
                    <a href="{{ route('login.form') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Back to Login
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection
