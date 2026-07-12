<?php

namespace App\Filament\Vendor\Auth;

use Filament\Auth\Pages\Login;

/**
 * The vendor panel authenticates through the Phase 3 /vendor/login
 * controller (2FA + vendor-status gate — see routes/vendor.php), not
 * Filament's own Livewire login form. Filament's Authenticate middleware
 * still requires a registered "auth.login" route to compute a redirect
 * target for guests (its own hasLogin()/getLoginUrl() plumbing runs before
 * any app-level middleware gets a chance to intervene), so this page exists
 * purely to satisfy that internal requirement and immediately hands off to
 * the real login page — no one is meant to see it render.
 */
class PanelLoginRedirect extends Login
{
    public function mount(): void
    {
        $this->redirect(route('vendor.login'));
    }
}
