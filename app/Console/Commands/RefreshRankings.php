<?php

namespace App\Console\Commands;

use App\Models\RankingSnapshot;
use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Console\Command;

/**
 * Snapshots the leaderboards from api/v1 into public.ranking_snapshots.
 * Scheduled every minute (see Console\Kernel) so the website reads a fresh
 * precomputed table instead of running the heavy game-server scan per request.
 * On an API error the previous snapshot is kept (never overwritten with empty).
 */
class RefreshRankings extends Command
{
    protected $signature = 'rankings:refresh';

    protected $description = 'Snapshot the leaderboards from api/v1 into ranking_snapshots.';

    /** How many rows to keep per board (top N; "your rank" resolves within this). */
    private const KEEP = 100;

    public function handle(): int
    {
        foreach (['players', 'killers', 'guilds'] as $type) {
            try {
                $rows = OpenMuApiClient::fromConfig()->rankings($type, self::KEEP);
            } catch (OpenMuApiException $e) {
                $this->warn("rankings:refresh skipped '{$type}' — {$e->getMessage()}");
                continue; // keep the last good snapshot
            }

            RankingSnapshot::updateOrCreate(
                ['type' => $type],
                ['generated_at' => now(), 'payload' => array_values($rows)],
            );
            $this->info("rankings:refresh '{$type}' — " . count($rows) . ' rows');
        }

        return self::SUCCESS;
    }
}
