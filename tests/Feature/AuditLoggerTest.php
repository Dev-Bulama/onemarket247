<?php

use App\Actions\Vendor\RejectVendorApplicationAction;
use App\Actions\Withdrawal\ApproveWithdrawalAction;
use App\Filament\Pages\MailSettings;
use App\Models\AuditLog;
use App\Models\MailSetting;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Models\VendorWallet;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use App\Support\AuditLogger;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('AuditLogger::record writes a row with the actor, action, and diff', function () {
    $admin = User::factory()->admin()->create();
    $application = VendorApplication::factory()->create();

    AuditLogger::record('test.action', $application, ['status' => 'pending'], ['status' => 'approved'], $admin);

    $log = AuditLog::first();
    expect($log->action)->toBe('test.action')
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->auditable_id)->toBe($application->id)
        ->and($log->auditable_type)->toBe($application->getMorphClass())
        ->and($log->before)->toBe(['status' => 'pending'])
        ->and($log->after)->toBe(['status' => 'approved']);
});

test('rejecting a vendor application writes an audit log entry', function () {
    $admin = User::factory()->admin()->create();
    $application = VendorApplication::factory()->create();

    app(RejectVendorApplicationAction::class)->handle($application, 'Incomplete documents', $admin);

    expect(AuditLog::where('action', 'vendor_application.rejected')->count())->toBe(1);
    $log = AuditLog::where('action', 'vendor_application.rejected')->first();
    expect($log->user_id)->toBe($admin->id)
        ->and($log->after['rejection_reason'])->toBe('Incomplete documents');
});

test('approving a withdrawal writes an audit log entry', function () {
    $admin = User::factory()->admin()->create();
    $vendor = Vendor::factory()->create();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);
    $withdrawal = Withdrawal::factory()->create(['vendor_id' => $vendor->id, 'withdrawal_method_id' => $method->id]);

    app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    expect(AuditLog::where('action', 'withdrawal.approved')->count())->toBe(1);
});

test('saving mail settings writes an audit log entry with the password redacted', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());
    MailSetting::current();

    Livewire::actingAs($admin, 'admin')
        ->test(MailSettings::class)
        ->fillForm(['password' => 'super-secret-smtp-password'])
        ->call('save');

    $log = AuditLog::where('action', 'mail_settings.updated')->first();
    expect($log)->not->toBeNull();

    $payload = json_encode($log->after);
    expect($payload)->not->toContain('super-secret-smtp-password')
        ->and($log->after['password'] ?? null)->toBe('[redacted]');
});
