<?php

use Illuminate\Support\Facades\Route;

// ==================== AJAX SEARCH (Select2 dropdowns) ====================
Route::get('/api/search/people', [\App\Http\Controllers\SearchController::class, 'people'])->name('api.search.people');
Route::get('/api/search/partners', [\App\Http\Controllers\SearchController::class, 'partners'])->name('api.search.partners');
