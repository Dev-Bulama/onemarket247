<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\VendorPanelProvider;
use App\Providers\MailConfigServiceProvider;

return [
    AppServiceProvider::class,
    MailConfigServiceProvider::class,
    AdminPanelProvider::class,
    VendorPanelProvider::class,
];
