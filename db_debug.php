<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    echo "Creating migrations table...\n";
    Schema::create('migrations', function (Blueprint $table) {
        $table->id();
        $table->string('migration');
        $table->integer('batch');
    });
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
