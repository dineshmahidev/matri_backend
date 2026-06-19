<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('locations');
    }

    public function down(): void
    {
        Schema::create('locations', function ($table) {
            $table->id();
            $table->string('state', 100);
            $table->string('city', 100);
            $table->timestamps();
        });
    }
};
