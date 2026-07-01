<?php

namespace App\Http\Controllers;

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

        // Fetch the full ordered leaderboard once (the game server ranks all characters
        // regardless of the limit), cache it, then slice locally. Cached 5 min because
        // ranks change slowly and the server-side scan is the expensive part.
        $all = Cache::remember('rank.' . $tab, 300, function () use ($tab) {
            try {
                return OpenMuApiClient::fromConfig()->rankings($tab, 200);
            } catch (OpenMuApiException $e) {
                return [];
            }
        });

        $top = array_slice($all, 0, self::TOP);

        // The logged-in player's own rows for this leaderboard (players/killers only,
        // matched by character name), so they can see their standing even outside the top.
        $mine = ($tab !== 'guilds' && auth()->check())
            ? $this->ownRows($all)
            : [];

        return view('ranking.index', [
            'tab'     => $tab,
            'players' => $tab === 'players' ? $top : [],
            'killers' => $tab === 'killers' ? $top : [],
            'guilds'  => $tab === 'guilds' ? $top : [],
            'mine'    => $mine,
            'top'     => self::TOP,
        ]);
    }

    /**
     * Rows in the full leaderboard that belong to the authenticated account,
     * matched by (case-insensitive) character name. Cached briefly per user.
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
