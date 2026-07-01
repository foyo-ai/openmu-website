<?php

namespace App\Http\Controllers\Auth;

use App\Auth\AccountIdentity;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Register a new account via the game-server API (creates a real game account).
     */
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'LoginName'    => ['required', 'string', 'min:3', 'max:10'],
            'EMail'        => ['required', 'string', 'email', 'max:255'],
            'SecurityCode' => ['required', 'string', 'size:6'],
            'PasswordHash' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $account = OpenMuApiClient::fromConfig()->register([
                'login'        => $data['LoginName'],
                'password'     => $data['PasswordHash'],
                'email'        => $data['EMail'],
                'securityCode' => $data['SecurityCode'],
            ]);
        } catch (OpenMuApiException $e) {
            $field = $e->errorCode === 'login_taken' ? 'LoginName' : 'PasswordHash';

            return back()->withErrors([$field => $e->getMessage()])->withInput();
        }

        Auth::login(AccountIdentity::fromApi($account));

        return redirect(RouteServiceProvider::HOME);
    }
}
