<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // A plain URL string on purpose: posts never reference media_files
            // by key so the blog stays decoupled from the media module and a
            // later CDN/S3 migration (P5-09) is a pure data rewrite.
            $table->string('featured_image')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('featured_image');
        });
    }
};
