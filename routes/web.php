<?php

use App\Livewire\BookAppointment;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book', BookAppointment::class)->name('book');
