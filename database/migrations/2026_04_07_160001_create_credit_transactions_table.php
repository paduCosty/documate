<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Positive = credit added, negative = credit spent
            $table->integer('amount');

            $table->enum('type', ['purchase', 'usage', 'refund', 'bonus']);
            $table->string('description');

            // Null for usage/bonus; set for purchases to prevent double-crediting
            $table->string('stripe_session_id')->nullable()->unique();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
