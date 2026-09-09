<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('/login', Login::class)->name('login')->middleware('guest');

Route::post('/logout', LogoutController::class)->name('logout')->middleware('auth');
