<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            // The area lives in the active theme's theme.json, not the DB —
            // no FK is possible, so the controller validates the slug by
            // hand against ThemeManager::widgetAreas() instead.
            $table->string('area_slug', 50);
            $table->string('type', 32);
            $table->json('data')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('area_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
