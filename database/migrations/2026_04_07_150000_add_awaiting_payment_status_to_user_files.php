<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE user_files MODIFY COLUMN status ENUM('pending','processing','completed','failed','awaiting_payment') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE user_files MODIFY COLUMN status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending'");
    }
};
