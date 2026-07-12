<?php

namespace App\Listeners\Auth;

use App\Models\DeviceSession;
use Illuminate\Auth\Events\Logout;

class ClearDeviceSession
{
    public function handle(Logout $event): void
    {
        if (request()->hasSession()) {
            DeviceSession::where('session_id', request()->session()->getId())->delete();
        }
    }
}
