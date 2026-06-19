<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_galleries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained()->onDelete('cascade');
            $table->string('image_url');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_galleries');
    }
};
