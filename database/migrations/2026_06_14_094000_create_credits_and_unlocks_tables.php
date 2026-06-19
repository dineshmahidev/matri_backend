<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add credits to users table
        Schema::table('users', function (Blueprint $table) {
            $table->integer('credits')->default(50)->after('role');
        });

        // Create unlocked_profiles pivot table
        Schema::create('unlocked_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('unlocked_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'unlocked_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unlocked_profiles');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('credits');
        });
    }
};
