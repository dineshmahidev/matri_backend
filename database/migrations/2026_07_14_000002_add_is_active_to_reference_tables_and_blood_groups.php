<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('religions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
        Schema::table('castes', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
        Schema::table('states', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
        });

        Schema::create('blood_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20);
            $table->string('name_ta', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        foreach ($groups as $g) {
            \App\Models\BloodGroup::create(['name' => $g]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_groups');
        Schema::table('cities', fn(Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('states', fn(Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('castes', fn(Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('religions', fn(Blueprint $t) => $t->dropColumn('is_active'));
    }
};
