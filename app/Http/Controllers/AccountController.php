<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(): View
    {
        return view('account.dashboard');
    }

    public function security(Request $request): View
    {
        return view('account.security', [
            'sessions' => $request->user()->deviceSessions()->orderByDesc('last_used_at')->get(),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }
}
