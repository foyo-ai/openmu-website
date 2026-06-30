<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Http\Request;

class CharacterPointsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit(string $character)
    {
        return view('character-points.edit', ['character' => $this->ownCharacter($character)]);
    }

    public function update(Request $request, string $character)
    {
        $char = $this->ownCharacter($character);

        $data = $request->validate([
            'strength'   => ['nullable', 'integer', 'min:0'],
            'agility'    => ['nullable', 'integer', 'min:0'],
            'vitality'   => ['nullable', 'integer', 'min:0'],
            'energy'     => ['nullable', 'integer', 'min:0'],
            'leadership' => ['nullable', 'integer', 'min:0'],
        ]);

        $points = array_map(fn ($k) => (int) ($data[$k] ?? 0), array_flip(['strength', 'agility', 'vitality', 'energy', 'leadership']));

        if (array_sum($points) === 0) {
            return back()->with('alert-warning', __('character.nothing_to_add'))->withInput();
        }

        try {
            OpenMuApiClient::fromConfig()->addPoints(auth()->user()->LoginName, $char['name'], $points);
        } catch (OpenMuApiException $e) {
            $msg = $e->errorCode === 'not_enough_points' ? __('character.err_not_enough_points') : $e->getMessage();

            return back()->with('alert-danger', $msg)->withInput();
        }

        return redirect()->route('character.show', $char['id'])->with('alert-success', __('character.points_added'));
    }

    /** Fetch a character owned by the authenticated account (by id), via the API. */
    private function ownCharacter(string $id): array
    {
        try {
            $characters = collect(OpenMuApiClient::fromConfig()->characters(auth()->user()->LoginName));
        } catch (OpenMuApiException $e) {
            abort(503);
        }

        $char = $characters->firstWhere('id', $id);
        abort_if($char === null, 404);

        return $char;
    }
}
