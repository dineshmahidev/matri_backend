<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo_visibility', 10)->default('all')->after('photo');
            $table->string('profile_visibility', 10)->default('all')->after('photo_visibility');
            $table->boolean('show_phone')->default(false)->after('profile_visibility');
            $table->boolean('notify_interest')->default(true)->after('show_phone');
            $table->boolean('notify_message')->default(true)->after('notify_interest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['photo_visibility', 'profile_visibility', 'show_phone', 'notify_interest', 'notify_message']);
        });
    }
};