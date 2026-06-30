<?php

namespace App\Http\Controllers;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Account settings (change password / email / security code).
     * Implemented in Phase 3 once a safe test DB is in place — placeholder for now.
     */
    public function edit()
    {
        return view('account.edit');
    }
}
