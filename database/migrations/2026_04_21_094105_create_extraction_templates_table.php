<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("extraction_templates", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained("users")->onDelete("cascade");
            $table->string("slug")->index();
            $table->string("name");
            $table->text("description")->nullable();
            $table->text("prompt_template");
            $table->json("output_schema");
            $table->boolean("is_system")->default(false);
            $table->boolean("active")->default(true);
            $table->json("metadata")->nullable();
            $table->timestamps();

            $table->index(["user_id", "active"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("extraction_templates");
    }
};
