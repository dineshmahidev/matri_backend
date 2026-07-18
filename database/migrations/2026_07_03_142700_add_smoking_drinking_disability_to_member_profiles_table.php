<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->enum('smoking_status', ['yes', 'no'])->default('no')->after('featured');
            $table->enum('drinking_status', ['yes', 'no'])->default('no')->after('smoking_status');
            $table->enum('disability', ['yes', 'no'])->default('no')->after('drinking_status');
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropColumn(['smoking_status', 'drinking_status', 'disability']);
        });
    }
};
