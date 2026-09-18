<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('court_reviews', function (Blueprint $table) {
            $table->unique(['court_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('court_reviews', function (Blueprint $table) {
            $table->dropUnique(['court_id', 'user_id']);
        });
    }
};