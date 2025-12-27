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
    // Simple IP restriction for security (you can add your IP if needed)
    $allowedIps = ['127.0.0.1', '::1'];
    $clientIp = request()->ip();
    
    \Illuminate\Support\Facades\Log::info('Migration attempt from IP: ' . $clientIp);
    
    if (!in_array($clientIp, $allowedIps)) {
        \Illuminate\Support\Facades\Log::warning('Unauthorized migration attempt from IP: ' . $clientIp);
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized',
            'your_ip' => $clientIp
        ], 403);
    }
    
    try {
        // Test database connection
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        
        // Run migrations with output buffer
        ob_start();
        \Illuminate\Support\Facades\Artisan::call('migrate:status');
        $status = ob_get_clean();
        
        ob_start();
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = ob_get_clean();
        
        \Illuminate\Support\Facades\Log::info("Migration status: " . $status);
        \Illuminate\Support\Facades\Log::info("Migration output: " . $output);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Migrations completed successfully!',
            'status_output' => $status,
            'migration_output' => $output
        ]);
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Migration failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('migrate');

Route::get('/', function () {
    return view('welcome');
});



