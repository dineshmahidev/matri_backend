<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_a_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('user_b_id')->constrained('users')->onDelete('cascade');
            $table->text('last_message')->nullable();
            $table->string('last_message_time')->nullable();
            $table->integer('unread_count_a')->default(0);
            $table->integer('unread_count_b')->default(0);
            $table->timestamps();
            $table->unique(['user_a_id', 'user_b_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
