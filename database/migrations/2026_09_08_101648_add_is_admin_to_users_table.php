<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The users table migration already creates this column.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The column belongs to the users table migration and must remain on rollback.
    }
};
