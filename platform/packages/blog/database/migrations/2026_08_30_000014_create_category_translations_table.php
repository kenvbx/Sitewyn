<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('locale', 10);
            // Nullable on purpose: a translation only stores the fields it
            // overrides, the rest fall back to the default language category.
            $table->string('name')->nullable();
            $table->timestamps();
            // One translation row per category and locale.
            $table->unique(['category_id', 'locale']);
            // Removing a language removes its translations with it.
            $table->foreign('locale')->references('code')->on('languages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
