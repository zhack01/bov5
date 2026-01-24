<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/view-report/{filename}', function ($filename) {
    $path = 'public/reports/' . $filename;
    
    if (!Storage::exists($path)) abort(404);

    return response(Storage::get($path), 200)
            ->header('Content-Type', 'text/html');
})->name('report.view');