<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("user_files", function (Blueprint $table) {
            // Make user_id nullable so guest files can exist without a user
            $table->unsignedBigInteger("user_id")->nullable()->change();
            // Add guest_id column
            $table->string("guest_id", 36)->nullable()->after("user_id");
            $table->index("guest_id");
        });
    }

    public function down(): void
    {
        Schema::table("user_files", function (Blueprint $table) {
            $table->dropIndex(["guest_id"]);
            $table->dropColumn("guest_id");
            $table->unsignedBigInteger("user_id")->nullable(false)->change();
        });
    }
};
