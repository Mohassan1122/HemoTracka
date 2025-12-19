<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

$migrationsPath = database_path('migrations');
$files = scandir($migrationsPath);

// Filter out directories and non-php files
$files = array_filter($files, function ($file) use ($migrationsPath) {
    return is_file($migrationsPath . DIRECTORY_SEPARATOR . $file) && str_ends_with($file, '.php');
});

sort($files);

echo "Wiping database...\n";
Artisan::call('db:wipe');
echo Artisan::output();

echo "Starting migrations one by one...\n";

foreach ($files as $file) {
    echo "Migrating: $file ... ";
    try {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/' . $file,
            '--force' => true,
        ]);
        echo "Success!\n";
    } catch (\Throwable $e) {
        echo "FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        // Optionally print stack trace if needed
        // echo $e->getTraceAsString() . "\n";
        break;
    }
}
