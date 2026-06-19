<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->string('age_range', 20)->nullable();
            $table->string('height_range', 30)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('community', 100)->nullable();
            $table->string('education', 100)->nullable();
            $table->string('profession', 100)->nullable();
            $table->string('location', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};
