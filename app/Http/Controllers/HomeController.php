<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * The old Laravel-UI "/home" dashboard was a bare "You are logged in!" placeholder.
     * Send logged-in players to their character list instead (the real dashboard).
     */
    public function index()
    {
        return redirect()->route('character.index');
    }
}
