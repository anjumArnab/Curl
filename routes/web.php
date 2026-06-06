<?php

use App\Livewire\Projects\ProjectList;
use App\Livewire\Workspace\Workspace;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'projects.index' : 'login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', fn () => redirect()->route('projects.index'))->name('dashboard');

    Route::get('projects', ProjectList::class)->name('projects.index');
    Route::get('projects/{project}/workspace', Workspace::class)->name('projects.workspace');

    Route::view('profile', 'profile')->name('profile');
});

require __DIR__.'/auth.php';
