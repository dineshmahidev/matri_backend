<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('interest_express_limit')->default(-1)->after('message_quota');
            $table->integer('profile_show_limit')->default(-1)->after('interest_express_limit');
            $table->integer('image_upload_limit')->default(-1)->after('profile_show_limit');
            $table->boolean('is_active')->default(true)->after('image_upload_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['interest_express_limit', 'profile_show_limit', 'image_upload_limit', 'is_active']);
        });
    }
};
