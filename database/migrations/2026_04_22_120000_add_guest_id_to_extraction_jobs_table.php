<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('extraction_jobs', function (Blueprint $table) {
            $table->string('guest_id')->nullable()->after('user_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('extraction_jobs', function (Blueprint $table) {
            $table->dropColumn('guest_id');
        });
    }
};
