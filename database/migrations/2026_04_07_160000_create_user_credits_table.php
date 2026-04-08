<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedInteger('balance')->default(0);          // current spendable credits
            $table->unsignedInteger('total_purchased')->default(0);  // lifetime purchased (never decrements)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};
