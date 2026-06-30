<?php

namespace App\Services;

use App\Models\Character;
use App\Models\ConfigAttributeDefinition;
use App\Models\DataStatAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Safe, transactional writes to a single OpenMU character.
 * Callers MUST ensure the character is owned by the user and the account is OFFLINE
 * before calling these (see CharacterActionController).
 *
 * OpenMU enum values used here:
 *   HeroState.Normal      = 3
 *   CharacterStatus.Normal = 0, .Banned = 1
 */
class CharacterActionService
{
    private const HERO_STATE_NORMAL = 3;
    private const CHAR_STATUS_BANNED = 1;

    /** Rename a character (unique, 1–10 chars). */
    public function rename(Character $character, string $newName): void
    {
        $newName = trim($newName);
        if ($newName === '' || mb_strlen($newName) > 10) {
            throw new RuntimeException(__('character.err_name_length'));
        }
        if (! preg_match('/^[A-Za-z0-9]+$/', $newName)) {
            throw new RuntimeException(__('character.err_name_chars'));
        }
        $taken = Character::where('Name', $newName)->where('Id', '!=', $character->Id)->exists();
        if ($taken) {
            throw new RuntimeException(__('character.err_name_taken'));
        }

        DB::transaction(function () use ($character, $newName) {
            $character->Name = $newName;
            $character->save();
        });
    }

    /** Reset a character: must meet min level; bumps reset count, level->1, exp->0, grants reward. */
    public function reset(Character $character): void
    {
        $level = (int) $character->getLevel();
        $minLevel = (int) config('server.reset.min_level');
        if ($level < $minLevel) {
            throw new RuntimeException(__('character.err_reset_level', ['level' => $minLevel]));
        }

        DB::transaction(function () use ($character) {
            // Increment reset count (upsert — the row may not exist yet).
            $resetRow = DataStatAttribute::where('CharacterId', $character->Id)
                ->where('DefinitionId', ConfigAttributeDefinition::RESET_ID)->first();
            if ($resetRow) {
                $resetRow->Value = (float) $resetRow->Value + 1;
                $resetRow->save();
            } else {
                DataStatAttribute::create([
                    'Id' => Str::uuid(),
                    'CharacterId' => $character->Id,
                    'DefinitionId' => ConfigAttributeDefinition::RESET_ID,
                    'Value' => 1,
                ]);
            }

            // Level back to 1.
            DataStatAttribute::where('CharacterId', $character->Id)
                ->where('DefinitionId', ConfigAttributeDefinition::LEVEL_ID)
                ->update(['Value' => 1]);

            $character->Experience = 0;
            $character->LevelUpPoints += (int) config('server.reset.reward_points');
            $character->save();
        });
    }

    /** Clear PK status: zero kill count, back to Normal hero state. */
    public function clearPk(Character $character): void
    {
        DB::transaction(function () use ($character) {
            $character->PlayerKillCount = 0;
            $character->State = self::HERO_STATE_NORMAL;
            $character->StateRemainingSeconds = 0;
            $character->save();
        });
    }

    /** Move a stuck character to the configured safezone (Lorencia by default). */
    public function unstick(Character $character): void
    {
        $mapNumber = (int) config('server.safezone.map_number');
        $mapId = DB::table('config.GameMapDefinition')->where('Number', $mapNumber)->value('Id');
        if (! $mapId) {
            throw new RuntimeException(__('character.err_safezone'));
        }

        DB::transaction(function () use ($character, $mapId) {
            $character->CurrentMapId = $mapId;
            $character->PositionX = (int) config('server.safezone.x');
            $character->PositionY = (int) config('server.safezone.y');
            $character->save();
        });
    }

    /**
     * Soft-delete: ban the character + rename to free the unique name so the player
     * can recreate it in-game. Reversible; no FK/orphan risk (unlike a hard delete).
     */
    public function softDelete(Character $character): void
    {
        DB::transaction(function () use ($character) {
            // Free the unique name with a short token derived from the id (<=10 chars).
            $token = 'd' . substr(str_replace('-', '', (string) $character->Id), 0, 9);
            $character->Name = $token;
            $character->CharacterStatus = self::CHAR_STATUS_BANNED;
            $character->save();
        });
    }
}
