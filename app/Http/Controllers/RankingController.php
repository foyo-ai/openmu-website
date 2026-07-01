<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RankingController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['players', 'killers', 'guilds'], true)
            ? $request->query('tab')
            : 'players';

        $rows = Cache::remember('rank.' . $tab, 60, function () use ($tab) {
            try {
                return OpenMuApiClient::fromConfig()->rankings($tab);
            } catch (OpenMuApiException $e) {
                return [];
            }
        });

        return view('ranking.index', [
            'tab'     => $tab,
            'players' => $tab === 'players' ? $rows : [],
            'killers' => $tab === 'killers' ? $rows : [],
            'guilds'  => $tab === 'guilds' ? $rows : [],
        ]);
    }
}
