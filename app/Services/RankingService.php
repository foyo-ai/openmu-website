<?php

namespace App\Services;

use App\Models\ConfigAttributeDefinition;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only leaderboards built from the OpenMU game DB.
 * Results are cached so the public site never hammers the live game database.
 */
class RankingService
{
    private const CACHE_TTL = 60; // seconds
    public const LIMIT = 100;

    /**
     * Top characters by reset count (secondary sort by level), with class name.
     * Returns rows: rank, name, class_name, resets, level.
     */
    public function topPlayers(int $limit = self::LIMIT): array
    {
        return Cache::remember('rank.players', self::CACHE_TTL, function () use ($limit) {
            $rows = DB::table('data.Character as c')
                ->join('data.StatAttribute as r', function ($j) {
                    $j->on('r.CharacterId', '=', 'c.Id');
                })
                ->where('r.DefinitionId', ConfigAttributeDefinition::RESET_ID)
                ->leftJoin('data.StatAttribute as l', function ($j) {
                    $j->on('l.CharacterId', '=', 'c.Id')
                        ->where('l.DefinitionId', '=', ConfigAttributeDefinition::LEVEL_ID);
                })
                ->leftJoin('config.CharacterClass as cc', 'cc.Id', '=', 'c.CharacterClassId')
                ->select([
                    'c.Name as name',
                    'cc.Name as class_name',
                    DB::raw('r."Value" as resets'),
                    DB::raw('l."Value" as level'),
                ])
                ->orderByDesc('r.Value')
                ->orderByDesc('l.Value')
                ->limit($limit)
                ->get();

            return $rows->map(fn ($row, $i) => [
                'rank'       => $i + 1,
                'name'       => $row->name,
                'class_name' => $row->class_name,
                'resets'     => (int) $row->resets,
                'level'      => (int) $row->level,
            ])->all();
        });
    }

    /**
     * Top characters by PK (player kill count).
     */
    public function topKillers(int $limit = self::LIMIT): array
    {
        return Cache::remember('rank.killers', self::CACHE_TTL, function () use ($limit) {
            $rows = DB::table('data.Character as c')
                ->leftJoin('config.CharacterClass as cc', 'cc.Id', '=', 'c.CharacterClassId')
                ->where('c.PlayerKillCount', '>', 0)
                ->select(['c.Name as name', 'cc.Name as class_name', 'c.PlayerKillCount as kills'])
                ->orderByDesc('c.PlayerKillCount')
                ->limit($limit)
                ->get();

            return $rows->map(fn ($row, $i) => [
                'rank'       => $i + 1,
                'name'       => $row->name,
                'class_name' => $row->class_name,
                'kills'      => (int) $row->kills,
            ])->all();
        });
    }

    /**
     * Top guilds by score, with member count.
     */
    public function topGuilds(int $limit = self::LIMIT): array
    {
        return Cache::remember('rank.guilds', self::CACHE_TTL, function () use ($limit) {
            $rows = DB::table('guild.Guild as g')
                ->leftJoin('guild.GuildMember as gm', 'gm.GuildId', '=', 'g.Id')
                ->select(['g.Name as name', 'g.Score as score', DB::raw('count(gm."Id") as members')])
                ->groupBy('g.Name', 'g.Score')
                ->orderByDesc('g.Score')
                ->limit($limit)
                ->get();

            return $rows->map(fn ($row, $i) => [
                'rank'    => $i + 1,
                'name'    => $row->name,
                'score'   => (int) $row->score,
                'members' => (int) $row->members,
            ])->all();
        });
    }
}
