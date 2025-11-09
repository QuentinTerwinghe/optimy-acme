@extends('layouts.app')

@section('title', 'Create Campaign')

@section('content')
<campaign-create-form
    :categories="{{ $categories->toJson() }}"
    :tags="{{ $tags->toJson() }}"
    :currencies="{{ json_encode(array_map(fn($c) => ['value' => $c->value, 'symbol' => $c->symbol(), 'label' => $c->label()], $currencies)) }}"
    csrf-token="{{ csrf_token() }}"
    dashboard-url="{{ route('dashboard') }}"
></campaign-create-form>
@endsection
