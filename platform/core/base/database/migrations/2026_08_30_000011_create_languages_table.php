<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // The site ships with exactly one language: English as the default
        // (P5-01). Seeding here — not in a seeder — keeps every install
        // consistent the moment the table exists, and the translations tables
        // created right after this migration FK its code column.
        if (DB::table('languages')->count() === 0) {
            DB::table('languages')->insert([
                'code' => 'en',
                'name' => 'English',
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
