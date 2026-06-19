<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->string('father', 100)->nullable();
            $table->string('mother', 100)->nullable();
            $table->string('siblings')->nullable();
            $table->string('family_type', 50)->nullable();
            $table->string('family_values', 50)->nullable();
            $table->string('family_status', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_details');
    }
};
