<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App-owned `guides` table (public schema) — the player cẩm nang / guide section.
 * Deliberately separate from OpenMU game data (data.* / config.*): we never touch
 * game tables. Bilingual columns (vi/en); body stored as HTML, authored by GMs only.
 *
 * category groups guides on the index: 'class' (per-class builds/gear), 'wings',
 * 'stats', 'general'. class_key ties a 'class' guide to one of the 7 S6 classes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('slug', 180)->unique();
            $table->string('category', 40)->default('general');
            $table->string('class_key', 40)->nullable();
            $table->string('icon', 60)->nullable();          // fontawesome icon name or image url
            $table->string('title_vi', 200);
            $table->string('title_en', 200)->nullable();
            $table->text('body_vi');
            $table->text('body_en')->nullable();
            $table->string('excerpt_vi', 500)->nullable();
            $table->string('excerpt_en', 500)->nullable();
            $table->string('cover_image', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            // References data.Account.Id, but NO FK constraint (cross-app/schema boundary).
            $table->uuid('author_account_id')->nullable();
            $table->timestampsTz();

            $table->index(['category', 'sort_order']);
            $table->index(['is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};
