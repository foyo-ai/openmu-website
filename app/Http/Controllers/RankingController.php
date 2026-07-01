<?php

namespace App\Http\Controllers;

use App\Models\RankingSnapshot;
use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RankingController extends Controller
{
    /** How many rows the leaderboard shows. */
    private const TOP = 10;

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['players', 'killers', 'guilds'], true)
            ? $request->query('tab')
            : 'players';

        // Read the precomputed snapshot (written every minute by rankings:refresh).
        // The page never calls the game server directly, so it stays fast under load.
        $snap        = RankingSnapshot::find($tab);
        $all         = $snap?->payload ?? [];
        $generatedAt = $snap?->generated_at;

        // Cold start (no snapshot row yet, e.g. right after a fresh deploy): fetch once
        // live so the board is never empty. The scheduler fills the table within a minute.
        if ($snap === null) {
            try {
                $all         = OpenMuApiClient::fromConfig()->rankings($tab, 100);
                $generatedAt = now();
            } catch (OpenMuApiException $e) {
                $all = [];
            }
        }

        $top = array_slice($all, 0, self::TOP);

        // The logged-in player's own rows for this board (players/killers only),
        // resolved within the snapshot's top rows (matched by character name).
        $mine = ($tab !== 'guilds' && auth()->check())
            ? $this->ownRows($all)
            : [];

        return view('ranking.index', [
            'tab'         => $tab,
            'players'     => $tab === 'players' ? $top : [],
            'killers'     => $tab === 'killers' ? $top : [],
            'guilds'      => $tab === 'guilds' ? $top : [],
            'mine'        => $mine,
            'top'         => self::TOP,
            'generatedAt' => $generatedAt,
        ]);
    }

    /**
     * Rows in the snapshot that belong to the authenticated account, matched by
     * (case-insensitive) character name. Character names are cached briefly per user.
     */
    private function ownRows(array $all): array
    {
        $login = auth()->user()->LoginName;

        $names = Cache::remember('rank.names.' . $login, 60, function () use ($login) {
            try {
                return collect(OpenMuApiClient::fromConfig()->characters($login))
                    ->reject(fn ($c) => (int) ($c['status'] ?? 0) === 1)   // 1 = banned/soft-deleted
                    ->pluck('name')
                    ->map(fn ($n) => mb_strtolower((string) $n))
                    ->all();
            } catch (OpenMuApiException $e) {
                return [];
            }
        });

        if (empty($names)) {
            return [];
        }

        return array_values(array_filter(
            $all,
            fn ($r) => in_array(mb_strtolower((string) ($r['name'] ?? '')), $names, true)
        ));
    }
}
