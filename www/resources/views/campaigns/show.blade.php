@extends('layouts.app')

@section('title', $campaign->title)

@section('content')
<campaign-show
    :campaign="{{ $campaign->toJson() }}"
    dashboard-url="{{ route('dashboard') }}"
></campaign-show>
@endsection
