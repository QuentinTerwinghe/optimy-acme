@extends('layouts.app')

@section('title', 'Donate to ' . $campaign->title)

@section('content')
<donation-form
    :campaign="{{ $campaign->toJson() }}"
    :quick-amounts="{{ json_encode($quickAmounts) }}"
    dashboard-url="{{ route('dashboard') }}"
    campaign-url="{{ route('campaigns.show', $campaign->id) }}"
></donation-form>
@endsection
