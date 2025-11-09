@extends('layouts.app')

@section('title', 'Edit Campaign')

@section('content')
<campaign-edit-form
    :campaign="{{ $campaign->toJson() }}"
    :categories="{{ $categories->toJson() }}"
    :tags="{{ $tags->toJson() }}"
    :currencies="{{ json_encode(array_map(fn($c) => ['value' => $c->value, 'symbol' => $c->symbol(), 'label' => $c->label()], $currencies)) }}"
    csrf-token="{{ csrf_token() }}"
    dashboard-url="{{ route('dashboard') }}"
    campaigns-url="{{ route('campaigns.index') }}"
></campaign-edit-form>
@endsection
