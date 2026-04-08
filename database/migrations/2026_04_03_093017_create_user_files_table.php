<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->string('operation_type');           
            $table->json('original_filenames');
            $table->unsignedBigInteger('input_size_bytes')->nullable();
            $table->unsignedBigInteger('output_size_bytes')->nullable();

            $table->string('output_path')->nullable();   
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');

            $table->timestamp('expires_at');            
            $table->timestamp('processed_at')->nullable();

            $table->json('metadata')->nullable();       

            $table->timestamps();

            $table->index(['user_id', 'expires_at']);
            $table->index('status');
            $table->index('operation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_files');
    }
};
