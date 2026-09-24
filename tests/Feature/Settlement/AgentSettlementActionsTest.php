<?php

use App\Actions\Settlement\ApproveAgentSettlementAction;
use App\Actions\Settlement\CreateAgentSettlementAction;
use App\Actions\Settlement\MarkAgentSettlementPaidAction;
use App\Actions\Settlement\RejectAgentSettlementAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\Agent;
use App\Models\AgentWallet;
use App\Models\User;

function agentWithAvailableBalance(int $available): array
{
    $agent = Agent::factory()->create();
    $wallet = AgentWallet::factory()->create(['agent_id' => $agent->id, 'available_balance' => $available]);

    return compact('agent', 'wallet');
}

test('creating a settlement moves the amount from available to reserved', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();

    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);

    expect($settlement->status)->toBe(WithdrawalStatus::Pending)
        ->and($settlement->amount)->toBe(10000)
        ->and($settlement->created_by)->toBe($admin->id)
        ->and($settlement->reference)->toStartWith('AS-');

    expect($wallet->fresh())
        ->available_balance->toBe(10000)
        ->reserved_balance->toBe(10000);
});

test('a zero or negative settlement amount is rejected', function () {
    ['agent' => $agent] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();

    expect(fn () => app(CreateAgentSettlementAction::class)->handle($agent, 0, $admin))
        ->toThrow(InsufficientWalletBalanceException::class);
});

test('over-settlement is impossible: a second settlement exceeding the remaining available balance is rejected', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(10000);
    $admin = User::factory()->admin()->create();

    app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);

    expect($wallet->fresh()->available_balance)->toBe(0);

    expect(fn () => app(CreateAgentSettlementAction::class)->handle($agent, 5000, $admin))
        ->toThrow(InsufficientWalletBalanceException::class);

    expect($wallet->fresh())
        ->available_balance->toBe(0)
        ->reserved_balance->toBe(10000);
});

test('an admin can approve a pending settlement without moving any funds', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);

    $approved = app(ApproveAgentSettlementAction::class)->handle($settlement, $admin);

    expect($approved->status)->toBe(WithdrawalStatus::Approved)
        ->and($approved->reviewed_by)->toBe($admin->id);

    expect($wallet->fresh())
        ->reserved_balance->toBe(10000)
        ->withdrawn_balance->toBe(0);
});

test('an approved settlement cannot be approved again', function () {
    ['agent' => $agent] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);
    app(ApproveAgentSettlementAction::class)->handle($settlement, $admin);

    expect(fn () => app(ApproveAgentSettlementAction::class)->handle($settlement->fresh(), $admin))
        ->toThrow(InvalidWithdrawalTransitionException::class);
});

test('rejecting a pending settlement releases the hold back to available balance', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);

    $rejected = app(RejectAgentSettlementAction::class)->handle($settlement, 'Incorrect bank details', $admin);

    expect($rejected->status)->toBe(WithdrawalStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('Incorrect bank details');

    expect($wallet->fresh())
        ->available_balance->toBe(20000)
        ->reserved_balance->toBe(0);
});

test('an approved settlement can still be rejected', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);
    $approved = app(ApproveAgentSettlementAction::class)->handle($settlement, $admin);

    app(RejectAgentSettlementAction::class)->handle($approved, 'Compliance hold', $admin);

    expect($wallet->fresh())->available_balance->toBe(20000);
});

test('marking an approved settlement paid moves reserved funds to withdrawn', function () {
    ['agent' => $agent, 'wallet' => $wallet] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);
    $approved = app(ApproveAgentSettlementAction::class)->handle($settlement, $admin);

    $paid = app(MarkAgentSettlementPaidAction::class)->handle($approved, $admin);

    expect($paid->status)->toBe(WithdrawalStatus::Paid)
        ->and($paid->paid_at)->not->toBeNull();

    expect($wallet->fresh())
        ->reserved_balance->toBe(0)
        ->withdrawn_balance->toBe(10000);
});

test('a pending settlement cannot be marked paid without first being approved', function () {
    ['agent' => $agent] = agentWithAvailableBalance(20000);
    $admin = User::factory()->admin()->create();
    $settlement = app(CreateAgentSettlementAction::class)->handle($agent, 10000, $admin);

    expect(fn () => app(MarkAgentSettlementPaidAction::class)->handle($settlement, $admin))
        ->toThrow(InvalidWithdrawalTransitionException::class);
});
