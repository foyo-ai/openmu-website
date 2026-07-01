<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A precomputed leaderboard snapshot (public.ranking_snapshots), keyed by board
 * type. Written by the rankings:refresh command; read by RankingController so
 * page views never hit the game server directly.
 */
class RankingSnapshot extends Model
{
    protected $table = 'ranking_snapshots';

    protected $primaryKey = 'type';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['type', 'generated_at', 'payload'];

    protected $casts = [
        'generated_at' => 'datetime',
        'payload'      => 'array',
    ];
}
