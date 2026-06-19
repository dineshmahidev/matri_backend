<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('display_id', 10)->unique(); // e.g. M1000
            $table->integer('age')->nullable();
            $table->string('height', 20)->nullable();
            $table->string('religion', 50)->nullable();
            $table->string('community', 50)->nullable();
            $table->string('mother_tongue', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('profession', 100)->nullable();
            $table->string('education', 100)->nullable();
            $table->string('income', 50)->nullable();
            $table->string('marital_status', 30)->default('Never Married');
            $table->string('photo')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('premium')->default(false);
            $table->boolean('verified')->default(false);
            $table->boolean('online')->default(false);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
