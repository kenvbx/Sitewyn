<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            // Self-referencing parent for one nesting level; deleting a
            // parent promotes its children to top level instead of removing
            // them with it.
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('label', 191);
            // Link source: "page" and "post" point at target_id in the
            // pages/posts tables, "custom" carries its own url.
            $table->string('type', 20);
            // Deliberately no FK to pages/posts: cross-package references
            // (core/base -> package tables) would couple migrations, so the
            // builder validates the target by hand instead.
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('url', 500)->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['menu_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
