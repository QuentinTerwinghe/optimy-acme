<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Payment Successful - {{ config('app.name', 'ACME Corp') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app">
        <!-- Payment Success Component -->
        <payment-success
            :payment="{{ Js::from([
                'id' => $payment->id,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'transaction_id' => $payment->transaction_id,
                'completed_at' => $payment->completed_at,
            ]) }}"
            :donation="{{ Js::from([
                'id' => $donation->id,
                'amount' => $donation->amount,
            ]) }}"
            :campaign="{{ Js::from([
                'id' => $campaign->id,
                'title' => $campaign->title,
                'description' => $campaign->description,
            ]) }}"
            :user="{{ Js::from([
                'name' => $user->name,
            ]) }}"
        ></payment-success>
    </div>

    <!-- Pass Laravel data to Vue -->
    <script>
        window.Laravel = {
            user: @json(auth()->user()),
            csrfToken: '{{ csrf_token() }}'
        };
    </script>
</body>
</html>
