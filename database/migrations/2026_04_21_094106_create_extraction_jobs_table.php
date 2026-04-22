<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("extraction_jobs", function (Blueprint $table) {
            $table->id();
            $table->uuid("uuid")->unique()->index();

            $table->foreignId("user_id")->nullable()->constrained("users")->onDelete("set null");
            $table->foreignId("template_id")->nullable()->constrained("extraction_templates")->onDelete("set null");
            $table->foreignId("provider_id")->nullable()->constrained("ai_providers")->onDelete("set null");
            $table->foreignId("model_id")->nullable()->constrained("ai_models")->onDelete("set null");

            $table->string("original_filename");
            $table->unsignedBigInteger("file_size_bytes")->nullable();
            $table->unsignedInteger("page_count")->nullable();

            $table->enum("status", ["pending", "processing", "completed", "failed"])->default("pending");
            $table->string("output_format")->default("excel");
            $table->string("output_path")->nullable();

            $table->json("extracted_data")->nullable();
            $table->text("error_message")->nullable();

            $table->unsignedInteger("tokens_used")->nullable();
            $table->unsignedInteger("processing_time_ms")->nullable();

            $table->timestamp("expires_at");
            $table->timestamp("processed_at")->nullable();
            $table->json("metadata")->nullable();
            $table->timestamps();

            $table->index(["user_id", "expires_at"]);
            $table->index("status");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("extraction_jobs");
    }
};
