<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // A plain URL string on purpose (same decision as posts.featured_image):
            // pages never reference media_files by key so the module stays
            // decoupled from the media package. P3-09.
            $table->string('og_image')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('og_image');
        });
    }
};
