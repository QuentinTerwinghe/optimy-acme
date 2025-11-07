<?php

use Illuminate\Support\Facades\Route;

// All routes now serve the SPA
// Vue Router will handle client-side routing
Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*');
