<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Set free interest credits in site_settings if not exists
        DB::table('site_settings')->upsert([
            ['key' => 'free_interest_credits', 'value' => '2'],
            ['key' => 'free_contact_quota', 'value' => '30'],
            ['key' => 'free_message_quota', 'value' => '50'],
            ['key' => 'credit_cost_interest', 'value' => '1'],
            ['key' => 'credit_cost_unlock', 'value' => '1'],
        ], 'key', ['value']);

        // Backfill existing member users: give credits=2, contact_quota=30, message_quota=50
        // Only if they currently have 0 in those columns
        DB::table('users')
            ->where('role', 'member')
            ->where('credits', 0)
            ->update(['credits' => 2]);

        DB::table('users')
            ->where('role', 'member')
            ->where('contact_quota', 0)
            ->update(['contact_quota' => 30]);

        DB::table('users')
            ->where('role', 'member')
            ->where('message_quota', 0)
            ->update(['message_quota' => 50]);
    }

    public function down(): void
    {
        // No rollback needed
    }
};
