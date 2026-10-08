<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasDuplicateLocations = DB::table('courts')
            ->select(['latitude', 'longitude'])
            ->groupBy('latitude', 'longitude')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateLocations) {
            throw new RuntimeException('Cannot add the unique court location index while duplicate coordinates exist.');
        }

        Schema::table('courts', function (Blueprint $table): void {
            $table->unique(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courts', function (Blueprint $table): void {
            $table->dropUnique(['latitude', 'longitude']);
        });
    }
};
