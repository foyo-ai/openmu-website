<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;

class CharacterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * The player's character list — read live from the game server via the API.
     */
    public function index()
    {
        $characters = $this->fetchOwnCharacters();

        return view('character.index', ['characters' => $characters]);
    }

    /**
     * A single character. Ownership is enforced by only resolving characters that
     * belong to the authenticated account (returned by the API).
     */
    public function show(string $character)
    {
        $characters = $this->fetchOwnCharacters();
        $char = $characters->firstWhere('id', $character);
        abort_if($char === null, 404);

        return view('character.show', ['character' => $char]);
    }

    /**
     * Fetch the authenticated account's characters (excluding soft-deleted/banned),
     * degrading gracefully if the game server is unreachable.
     */
    private function fetchOwnCharacters()
    {
        try {
            $characters = OpenMuApiClient::fromConfig()->characters(auth()->user()->LoginName);
        } catch (OpenMuApiException $e) {
            return collect();
        }

        // status 1 = CharacterStatus.Banned (soft-deleted) — hide from the list.
        return collect($characters)->reject(fn ($c) => (int) ($c['status'] ?? 0) === 1)->values();
    }
}
