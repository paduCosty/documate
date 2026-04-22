<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("ai_models", function (Blueprint $table) {
            $table->id();
            $table->foreignId("provider_id")->constrained("ai_providers")->onDelete("cascade");
            $table->string("slug");
            $table->string("name");
            $table->boolean("enabled")->default(true);
            $table->boolean("is_default")->default(false);
            $table->json("metadata")->nullable();
            $table->timestamps();

            $table->unique(["provider_id", "slug"]);
            $table->index("provider_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("ai_models");
    }
};
