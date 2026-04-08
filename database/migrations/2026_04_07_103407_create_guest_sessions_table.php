<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("guest_sessions", function (Blueprint $table) {
            $table->id();
            $table->string("guest_id", 36)->unique(); // UUID
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->timestamp("last_activity_at")->nullable();
            $table->timestamps();

            $table->index("guest_id");
            $table->index("last_activity_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("guest_sessions");
    }
};
