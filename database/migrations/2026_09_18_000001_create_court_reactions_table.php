<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('reaction', ['like', 'dislike']);
            $table->timestamps();
            $table->unique(['court_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_reactions');
    }
};