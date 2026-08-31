<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->string('locale', 10);
            // Every field is nullable on purpose: a translation only stores
            // the fields it overrides, the rest fall back to the default
            // language post. Slugs are deliberately NOT translated (P5-01) —
            // translations are addressed by the default slug.
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();
            // One translation row per post and locale.
            $table->unique(['post_id', 'locale']);
            // Removing a language removes its translations with it.
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_translations');
    }
};
