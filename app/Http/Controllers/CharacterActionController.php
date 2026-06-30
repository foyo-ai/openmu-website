<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Http\Request;

/**
 * Character actions — all executed by the game server via api/v1 (no direct DB writes).
 * The server enforces ownership, the offline requirement (409 if online), validation,
 * and performs a real cascade delete.
 */
class CharacterActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function reset(string $character)
    {
        return $this->call($character, fn ($api, $login, $name) => $api->reset($login, $name), 'character.reset_done');
    }

    public function clearPk(string $character)
    {
        return $this->call($character, fn ($api, $login, $name) => $api->clearPk($login, $name), 'character.pk_cleared');
    }

    public function unstick(string $character)
    {
        return $this->call($character, fn ($api, $login, $name) => $api->unstick($login, $name), 'character.unstuck');
    }

    public function rename(Request $request, string $character)
    {
        $request->validate(['name' => ['required', 'string', 'max:10']]);

        return $this->call($character, fn ($api, $login, $name) => $api->rename($login, $name, $request->input('name')), 'character.renamed');
    }

    public function destroy(Request $request, string $character)
    {
        $request->validate(['security_code' => ['required', 'string']]);

        return $this->call(
            $character,
            fn ($api, $login, $name) => $api->deleteCharacter($login, $name, $request->input('security_code')),
            'character.deleted',
            toIndex: true,
        );
    }

    /**
     * Resolve the character (by id, from the owner's API list) then run the API call,
     * mapping API errors to localized flash messages.
     */
    private function call(string $id, callable $apiCall, string $successKey, bool $toIndex = false)
    {
        $api = OpenMuApiClient::fromConfig();
        $login = auth()->user()->LoginName;

        try {
            $characters = collect($api->characters($login));
        } catch (OpenMuApiException $e) {
            return back()->with('alert-danger', __('character.err_unreachable'));
        }

        $char = $characters->firstWhere('id', $id);
        abort_if($char === null, 404);

        try {
            $apiCall($api, $login, $char['name']);
        } catch (OpenMuApiException $e) {
            return back()->with('alert-danger', $this->mapError($e));
        }

        $redirect = $toIndex ? redirect()->route('character.index') : back();

        return $redirect->with('alert-success', __($successKey));
    }

    private function mapError(OpenMuApiException $e): string
    {
        return match ($e->errorCode) {
            'character_online'    => __('character.err_online'),
            'name_taken'          => __('character.err_name_taken'),
            'invalid_name'        => __('character.err_name_chars'),
            'wrong_security_code' => __('character.err_security_code'),
            'not_enough_points'   => __('character.err_not_enough_points'),
            'unreachable'         => __('character.err_unreachable'),
            default               => $e->getMessage(),
        };
    }
}
