@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
    <role-edit-form :role-id="{{ $roleId }}"></role-edit-form>
</div>
@endsection
