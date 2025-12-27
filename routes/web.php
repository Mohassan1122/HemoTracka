<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Temporary route to run migrations (REMOVE AFTER USE)
Route::get('/migrate', function() {
    // Simple IP restriction for security (replace with your IP if needed)
    if (!in_array(request()->ip(), ['127.0.0.1', '::1'])) {
        return 'Unauthorized';
    }
    
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return 'Migrations completed successfully!';
    } catch (\Exception $e) {
        return 'Migration failed: ' . $e->getMessage();
    }
})->name('migrate');

Route::get('/', function () {
    return view('welcome');
});
