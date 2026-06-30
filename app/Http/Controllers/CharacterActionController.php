<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Services\CharacterActionService;
use App\Services\OnlineCheckService;
use Illuminate\Http\Request;
use RuntimeException;

class CharacterActionController extends Controller
{
    public function __construct(
        private readonly CharacterActionService $actions,
        private readonly OnlineCheckService $online,
    ) {
        $this->middleware('auth');
        $this->middleware('verifyCharacterAccountOwner');
    }

    public function rename(Request $request, Character $character)
    {
        $request->validate(['name' => ['required', 'string', 'max:10']]);
        return $this->guarded($character, fn () => $this->actions->rename($character, $request->input('name')), 'character.renamed');
    }

    public function reset(Character $character)
    {
        return $this->guarded($character, fn () => $this->actions->reset($character), 'character.reset_done');
    }

    public function clearPk(Character $character)
    {
        return $this->guarded($character, fn () => $this->actions->clearPk($character), 'character.pk_cleared');
    }

    public function unstick(Character $character)
    {
        return $this->guarded($character, fn () => $this->actions->unstick($character), 'character.unstuck');
    }

    public function destroy(Request $request, Character $character)
    {
        // Destructive: require the account security code as confirmation.
        $request->validate(['security_code' => ['required', 'string']]);
        if ($request->input('security_code') !== $request->user()->SecurityCode) {
            return back()->with('alert-danger', __('character.err_security_code'));
        }

        $result = $this->guarded($character, fn () => $this->actions->softDelete($character), 'character.deleted');
        // After deletion send the user back to the character list, not the (gone) character page.
        return $result instanceof \Illuminate\Http\RedirectResponse && session('alert-success')
            ? redirect()->route('character.index')->with('alert-success', __('character.deleted'))
            : $result;
    }

    /**
     * Shared guard: block if the account is online, run the action, translate
     * any domain error into a flashed message.
     */
    private function guarded(Character $character, callable $action, string $successKey)
    {
        if ($this->online->isOnline(auth()->user()->LoginName)) {
            return back()->with('alert-danger', __('character.err_online'));
        }

        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('alert-danger', $e->getMessage());
        }

        return back()->with('alert-success', __($successKey));
    }
}
