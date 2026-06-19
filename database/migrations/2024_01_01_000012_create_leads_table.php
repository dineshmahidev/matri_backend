<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('display_id', 10)->unique();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('source', 50);
            $table->enum('status', ['New', 'Contacted', 'Qualified', 'Converted', 'Lost'])->default('New');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
