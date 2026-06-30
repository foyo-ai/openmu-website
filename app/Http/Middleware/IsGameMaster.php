<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows only OpenMU GameMaster accounts (data.Account.State in {2,3}).
 * 2 = GameMaster, 3 = GameMasterInvisible.
 */
class IsGameMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && in_array((int) $user->State, [2, 3], true), 403);

        return $next($request);
    }
}
