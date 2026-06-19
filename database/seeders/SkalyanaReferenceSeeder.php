<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SkalyanaReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('sql/skalyana_reference.sql');

        if (!file_exists($path)) {
            $this->command?->warn('skalyana_reference.sql not found. Run scratch/extract_skalyana.php first.');
            return;
        }

        $sql = file_get_contents($path);

        if (Schema::hasTable('castes')) {
            DB::table('cities')->delete();
            DB::table('states')->delete();
            DB::table('castes')->delete();
            DB::table('religions')->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        preg_match_all('/INSERT INTO[^;]+;/s', $sql, $matches);
        foreach ($matches[0] as $statement) {
            if (!str_starts_with(trim($statement), 'INSERT INTO')) {
                continue;
            }

            if (str_contains($statement, '`cities`') && str_contains($statement, 'deleted_at')) {
                $statement = str_replace(', `deleted_at`', '', $statement);
                $statement = preg_replace(
                    "/'(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})', NULL\)/",
                    "'$1')",
                    $statement
                );
            }

            // Skip malformed rows with id 0 (duplicate primary keys in source dump)
            if (str_contains($statement, 'INSERT INTO `cities`')) {
                $statement = preg_replace('/\(\s*0\s*,/', '(SKIP_ROW,', $statement);
                $statement = preg_replace('/\(\s*SKIP_ROW[^)]+\),?\s*/', '', $statement);
            }

            DB::unprepared($statement);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command?->info('Imported religions, castes, states, and cities from skalyana reference.');
    }
}
