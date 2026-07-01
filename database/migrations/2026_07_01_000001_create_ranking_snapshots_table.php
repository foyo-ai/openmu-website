<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App-owned `ranking_snapshots` table (public schema). A background command
 * (rankings:refresh, scheduled every minute) writes the leaderboard here from
 * api/v1, so page views read a precomputed snapshot instead of triggering the
 * expensive game-server scan. One row per board type, with the capture time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ranking_snapshots', function (Blueprint $table) {
            $table->string('type', 20)->primary();   // players | killers | guilds
            $table->timestampTz('generated_at');      // when this snapshot was captured
            $table->json('payload');                  // ordered array of ranking rows
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranking_snapshots');
    }
};
