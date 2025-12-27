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
    $clientIp = request()->ip();
    \Illuminate\Support\Facades\Log::info('Migration attempt from IP: ' . $clientIp);

    try {
        // Check if .env exists
        if (!file_exists(base_path('.env'))) {
            return response()->json([
                'status' => 'error',
                'message' => '.env file not found',
                'path' => base_path('.')
            ], 500);
        }

        // Get database configuration
        $dbConfig = [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
        ];

        \Illuminate\Support\Facades\Log::info('Database config: ' . json_encode($dbConfig));

        // Test database connection
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        \Illuminate\Support\Facades\Log::info('Database connection successful');

        // Run migrations
        \Illuminate\Support\Facades\Artisan::call('migrate:status');
        $status = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $output = \Illuminate\Support\Facades\Artisan::output();

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
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('migrate');

Route::get('/', function () {
    return view('welcome');
});



