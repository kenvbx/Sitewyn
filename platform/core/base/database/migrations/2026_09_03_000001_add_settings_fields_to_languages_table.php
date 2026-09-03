<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('languages', function (Blueprint $table): void {
            $table->string('locale', 20)->default('en_US')->after('name');
            $table->string('flag', 10)->default('us')->after('locale');
            $table->string('text_direction', 3)->default('ltr')->after('flag');
            $table->unsignedInteger('order')->default(0)->after('is_active');
        });

        DB::table('languages')
            ->where('code', 'en')
            ->update([
                'locale' => 'en_US',
                'flag' => 'us',
                'text_direction' => 'ltr',
                'order' => 0,
            ]);
    }

    public function down(): void
    {
        Schema::table('languages', function (Blueprint $table): void {
            $table->dropColumn(['locale', 'flag', 'text_direction', 'order']);
        });
    }
};
