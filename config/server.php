<?php

// Server presentation/config for the public site. Edit these to match your server.
// Kept separate from game data so non-developers can tweak the landing page safely.
return [
    'season'      => env('SERVER_SEASON', 'Season 6'),
    'version'     => env('SERVER_VERSION', 'Episode 3'),
    'exp_rate'    => env('SERVER_EXP_RATE', '50x'),
    'master_exp_rate' => env('SERVER_MASTER_EXP_RATE', '20x'),
    'drop_rate'   => env('SERVER_DROP_RATE', '30%'),
    'max_reset'   => env('SERVER_MAX_RESET', 'Unlimited'),

    // Where the game client connects first (shown on the landing page).
    'connect_host' => env('SERVER_CONNECT_HOST', 'connect.muss6.org'),
    'connect_port' => env('SERVER_CONNECT_PORT', '44405'),

    // Community + downloads
    'discord_url'  => env('SERVER_DISCORD_URL', 'https://discord.gg/'),
    'facebook_url' => env('SERVER_FACEBOOK_URL', ''),
    'downloads'    => [
        // label => url
        'Full Client (Google Drive)' => env('SERVER_DOWNLOAD_FULL', '#'),
        'Full Client (Mega)'         => env('SERVER_DOWNLOAD_MEGA', '#'),
    ],

    // OpenMU admin API base, reachable container-to-container for the live online count
    // and the is-online safety check. In production e.g. http://openmu-startup:8080
    'openmu_api'   => env('OPENMU_API_URL', ''),

    // Shared secret for the authenticated game-server api/v1 (must match the game server's
    // OpenMU:WebApiKey / WEB_API_KEY). The website is a trusted service that calls api/v1.
    'api_key'      => env('OPENMU_API_KEY', ''),

    // --- Character actions (Phase 4) -------------------------------------
    'reset' => [
        'min_level'     => (int) env('RESET_MIN_LEVEL', 400),   // level required to reset
        'reward_points' => (int) env('RESET_REWARD_POINTS', 0), // LevelUpPoints granted per reset
        'clear_stats'   => (bool) env('RESET_CLEAR_STATS', false), // keep added base stats by default
    ],
    // "Unstick" / move-to-safezone destination (Lorencia town by default).
    'safezone' => [
        'map_number' => (int) env('SAFEZONE_MAP_NUMBER', 0),
        'x'          => (int) env('SAFEZONE_X', 125),
        'y'          => (int) env('SAFEZONE_Y', 125),
    ],
];
