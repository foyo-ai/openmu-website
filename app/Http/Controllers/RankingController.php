<?php

namespace App\Http\Controllers;

use App\Services\RankingService;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    public function index(Request $request, RankingService $rankings)
    {
        $tab = in_array($request->query('tab'), ['players', 'killers', 'guilds'], true)
            ? $request->query('tab')
            : 'players';

        return view('ranking.index', [
            'tab'      => $tab,
            'players'  => $tab === 'players' ? $rankings->topPlayers() : [],
            'killers'  => $tab === 'killers' ? $rankings->topKillers() : [],
            'guilds'   => $tab === 'guilds' ? $rankings->topGuilds() : [],
        ]);
    }
}
