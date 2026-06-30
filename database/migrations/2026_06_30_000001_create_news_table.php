<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App-owned `news` table. Lives in the default (public) schema — deliberately
 * separate from OpenMU's game data (data.* / config.*) so we never touch game tables.
 * Bilingual columns (vi/en) keep it simple for two locales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 180)->unique();
            $table->string('title_vi', 200);
            $table->string('title_en', 200)->nullable();
            $table->text('body_vi');
            $table->text('body_en')->nullable();
            $table->string('excerpt_vi', 500)->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->string('cover_image', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestampTz('published_at')->nullable();
            // References data.Account.Id, but NO FK constraint (cross-app/schema boundary).
            $table->uuid('author_account_id')->nullable();
            $table->timestampsTz();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
