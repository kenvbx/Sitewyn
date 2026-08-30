<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // A plain URL string on purpose, mirroring featured_image: no
            // media_files FK, so a later CDN/S3 migration is a pure data
            // rewrite. P3-09.
            $table->string('og_image')->nullable()->after('featured_image');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('og_image');
        });
    }
};
