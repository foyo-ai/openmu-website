<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use App\Services\OpenMuStatusService;
use Illuminate\Support\Facades\Cache;

class PageController extends Controller
{
    /**
     * Public landing / server-info page.
     */
    public function home(OpenMuStatusService $status)
    {
        $serverStatus = $status->status();

        $latestNews = News::query()
            ->published()
            ->latestFirst()
            ->limit(3)
            ->get();

        return view('welcome', [
            'serverStatus' => $serverStatus,
            'latestNews'   => $latestNews,
            'rates'        => $this->rates(),
        ]);
    }

    /**
     * Server rates for the landing page. Experience/master rates and the level cap are read
     * LIVE from the game server (api/v1 /server-info) so they stay accurate; drop rate and the
     * "max reset" policy have no single game-config value, so they stay operator-set in config.
     * Falls back to config across the board if the game server is unreachable.
     */
    private function rates(): array
    {
        $info = Cache::remember('server.info', 300, function () {
            try {
                return OpenMuApiClient::fromConfig()->serverInfo();
            } catch (OpenMuApiException $e) {
                return [];
            }
        });

        return [
            'exp'        => isset($info['experienceRate']) ? $this->asMultiplier($info['experienceRate']) : config('server.exp_rate'),
            'master_exp' => isset($info['masterExperienceRate']) ? $this->asMultiplier($info['masterExperienceRate']) : config('server.master_exp_rate'),
            'drop'       => config('server.drop_rate'),                 // no single game-config value
            'max_reset'  => config('server.max_reset'),                 // operator policy
            'max_level'  => $info['maxLevel'] ?? null,
        ];
    }

    /** Formats a rate float as e.g. "x50" / "x1.5". */
    private function asMultiplier($value): string
    {
        $n = rtrim(rtrim(number_format((float) $value, 1, '.', ''), '0'), '.');
        return 'x' . $n;
    }
}
