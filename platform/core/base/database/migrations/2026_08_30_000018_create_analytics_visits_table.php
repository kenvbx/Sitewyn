<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_visits', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500);
            $table->string('referer', 500)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('session_id', 64)->nullable();
            // One row per tracked pageview, written by the TrackVisits
            // middleware; immutable like audit entries.
            $table->timestamp('created_at')->nullable();
            $table->index('created_at');
            // path is varchar(500): a full-column index stays within MySQL's
            // 3072-byte key limit (500 x 4 bytes), and prefix lengths such as
            // path(191) are not portable to SQLite — hence a plain index.
            $table->index('path');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_visits');
    }
};
