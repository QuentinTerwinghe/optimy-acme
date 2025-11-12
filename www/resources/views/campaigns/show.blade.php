@extends('layouts.app')

@section('title', $campaign->title)

@section('content')
<campaign-show
    :campaign="{{ $campaign->toJson() }}"
    dashboard-url="{{ route('dashboard') }}"
    donate-url="{{ route('donations.create', $campaign->id) }}"
></campaign-show>
@endsection
