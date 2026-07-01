<?php

// Operator-curated presentation for in-game events. The SCHEDULE (start times + duration)
// comes live from the game server (api/v1 /events); this file only adds display extras that
// the game has no clean source for: an icon, a highlight image, the best reward and its rate.
// Keyed by the event name returned by the API. Leave 'reward'/'rate'/'image' null to hide them.
return [
    'Blood Castle' => [
        'icon'   => 'fort-awesome',
        'image'  => null,   // e.g. '/images/events/blood-castle.jpg'
        'reward' => null,   // e.g. 'Ring of Warrior + Jewel of Bless'
        'rate'   => null,   // e.g. 'Bless x2'
    ],
    'Devil Square' => [
        'icon'   => 'khanda',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
    'Chaos Castle' => [
        'icon'   => 'skull',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
    'Golden Invasion' => [
        'icon'   => 'crown',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
    'Red Dragon Invasion' => [
        'icon'   => 'dragon',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
    'Happy Hour' => [
        'icon'   => 'champagne-glasses',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
    'Wandering Merchants' => [
        'icon'   => 'store',
        'image'  => null,
        'reward' => null,
        'rate'   => null,
    ],
];
