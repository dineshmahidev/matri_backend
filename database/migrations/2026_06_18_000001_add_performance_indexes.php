<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('gender');
            $table->index('phone');
            $table->index('created_at');
        });

        // Member profiles
        Schema::table('member_profiles', function (Blueprint $table) {
            $table->index('age');
            $table->index('religion');
            $table->index('community');
            $table->index('city');
            $table->index('education');
            $table->index('mother_tongue');
            $table->index('premium');
            $table->index('photo');
        });

        // Leads
        Schema::table('leads', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });

        // Notifications
        Schema::table('notifications', function (Blueprint $table) {
            $table->index('type');
            $table->index('read');
        });

        // Subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('status');
        });

        // Payments
        Schema::table('payments', function (Blueprint $table) {
            $table->index('status');
        });

        // Messages
        Schema::table('messages', function (Blueprint $table) {
            $table->index('sender_id');
            $table->index('conversation_id');
            $table->index('read_at');
        });

        // Conversations
        Schema::table('conversations', function (Blueprint $table) {
            $table->index('updated_at');
        });

        // Interests
        Schema::table('interests', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('member_profiles', function (Blueprint $table) {
            $table->dropIndex(['age']);
            $table->dropIndex(['religion']);
            $table->dropIndex(['community']);
            $table->dropIndex(['city']);
            $table->dropIndex(['education']);
            $table->dropIndex(['mother_tongue']);
            $table->dropIndex(['premium']);
            $table->dropIndex(['photo']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['read']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['conversation_id']);
            $table->dropIndex(['read_at']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('interests', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
