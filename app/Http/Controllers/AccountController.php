<?php

namespace App\Http\Controllers;

use App\Services\OpenMuApiClient;
use App\Services\OpenMuApiException;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        return view('account.edit');
    }

    public function updatePassword(Request $request)
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return $this->call('password', fn ($api, $login) => $api->updatePassword(
            $login,
            $request->input('current_password'),
            $request->input('password'),
        ), 'account.password_updated');
    }

    public function updateEmail(Request $request)
    {
        $request->validateWithBag('email', [
            'current_password' => ['required', 'string'],
            'EMail'            => ['required', 'string', 'email', 'max:255'],
        ]);

        return $this->call('email', fn ($api, $login) => $api->updateEmail(
            $login,
            $request->input('current_password'),
            $request->input('EMail'),
        ), 'account.email_updated');
    }

    public function updateSecurityCode(Request $request)
    {
        $request->validateWithBag('security', [
            'current_password' => ['required', 'string'],
            'SecurityCode'     => ['required', 'string', 'size:6'],
        ]);

        return $this->call('security', fn ($api, $login) => $api->updateSecurityCode(
            $login,
            $request->input('current_password'),
            $request->input('SecurityCode'),
        ), 'account.security_updated');
    }

    private function call(string $bag, callable $apiCall, string $successKey)
    {
        try {
            $apiCall(OpenMuApiClient::fromConfig(), auth()->user()->LoginName);
        } catch (OpenMuApiException $e) {
            $message = $e->errorCode === 'wrong_password' ? __('account.wrong_password') : $e->getMessage();

            return back()->withErrors(['current_password' => $message], $bag);
        }

        return back()->with('alert-success', __($successKey));
    }
}
