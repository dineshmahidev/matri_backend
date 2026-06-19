<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['member', 'staff', 'admin'])->default('member')->after('email');
            $table->string('phone', 20)->nullable()->after('role');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('phone');
            $table->date('dob')->nullable()->after('gender');
            $table->string('otp', 6)->nullable()->after('password');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'gender', 'dob', 'otp', 'otp_expires_at']);
        });
    }
};
