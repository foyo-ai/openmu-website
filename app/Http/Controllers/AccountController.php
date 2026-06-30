<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Account settings: change password / email / security code.
     */
    public function edit()
    {
        return view('account.edit');
    }

    public function updatePassword(Request $request)
    {
        $this->ensureCurrentPassword($request, 'password');

        $request->validateWithBag('password', [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->PasswordHash = Hash::make($request->input('password'));
        $user->save();

        return back()->with('alert-success', __('account.password_updated'));
    }

    public function updateEmail(Request $request)
    {
        $this->ensureCurrentPassword($request, 'email');

        $request->validateWithBag('email', [
            // Ignore the current account's own row when checking uniqueness.
            'EMail' => [
                'required', 'string', 'email', 'max:255',
                'unique:pgsql.data.Account,EMail,' . $request->user()->Id . ',Id',
            ],
        ]);

        $user = $request->user();
        $user->EMail = $request->input('EMail');
        $user->save();

        return back()->with('alert-success', __('account.email_updated'));
    }

    public function updateSecurityCode(Request $request)
    {
        $this->ensureCurrentPassword($request, 'security');

        $request->validateWithBag('security', [
            'SecurityCode' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $user = $request->user();
        $user->SecurityCode = $request->input('SecurityCode');
        $user->save();

        return back()->with('alert-success', __('account.security_updated'));
    }

    /**
     * Require the account's current password before any change (re-auth).
     * Throws a validation error into the given bag if it doesn't match.
     */
    private function ensureCurrentPassword(Request $request, string $bag): void
    {
        $request->validateWithBag($bag, [
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('current_password'), $request->user()->PasswordHash)) {
            throw ValidationException::withMessages([
                'current_password' => __('account.wrong_password'),
            ])->errorBag($bag);
        }
    }
}
