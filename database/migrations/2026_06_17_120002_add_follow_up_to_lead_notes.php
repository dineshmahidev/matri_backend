<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_notes', function (Blueprint $table) {
            $table->timestamp('follow_up_at')->nullable()->after('note');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->after('follow_up_at');
            $table->index('follow_up_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('lead_notes', function (Blueprint $table) {
            $table->dropIndex(['follow_up_at']);
            $table->dropIndex(['status']);
            $table->dropColumn(['follow_up_at', 'status']);
        });
    }
};
