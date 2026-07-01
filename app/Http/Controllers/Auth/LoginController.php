<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Authenticate via the game-server API (through the 'api' auth provider).
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (Auth::attempt(['LoginName' => $data['loginName'], 'password' => $data['password']])) {
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return redirect(route('login'))->withErrors(['credentials' => 'Invalid credentials'])->withInput();
    }
}
