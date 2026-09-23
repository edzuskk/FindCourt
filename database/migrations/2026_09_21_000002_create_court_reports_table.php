<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reportReason', 50);
            $table->text('reportComment')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            $table->unique(['court_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_reports');
    }
};
