<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    /**
     * In-game event schedule. Start times + duration come LIVE from the game server
     * (api/v1 /events); presentation extras (icon, image, reward, rate) are merged from the
     * operator-curated config/events.php. Countdowns are computed client-side against the
     * server clock (UTC), so players see how long until each event starts.
     */
    public function index()
    {
        $schedule = Cache::remember('server.events', 60, function () {
            try {
                return OpenMuApiClient::fromConfig()->events();
            } catch (OpenMuApiException $e) {
                return [];
            }
        });

        $curated = config('events', []);

        $events = collect($schedule)
            ->map(function ($e) use ($curated) {
                $name  = (string) ($e['name'] ?? '');
                $extra = $curated[$name] ?? [];

                return [
                    'name'     => $name,
                    'times'    => array_values($e['times'] ?? []),
                    'duration' => (int) ($e['durationMinutes'] ?? 0),
                    'icon'     => $extra['icon'] ?? 'calendar-day',
                    'image'    => $extra['image'] ?? null,
                    'reward'   => $extra['reward'] ?? null,
                    'rate'     => $extra['rate'] ?? null,
                ];
            })
            ->filter(fn ($e) => !empty($e['times']))
            ->values()
            ->all();

        return view('events.index', [
            'events'      => $events,
            'serverNowMs' => now()->getTimestampMs(),
        ]);
    }
}
