<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Credit;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_valid_coupon(): void
    {
        $user   = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'type'               => 'credits',
            'value'              => 50,
            'is_active'          => true,
            'max_uses'           => 10,
            'used_count'         => 0,
            'expires_at'         => now()->addDays(7),
        ]);
        Credit::factory()->create(['user_id' => $user->id, 'balance' => 0]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/wallet/redeem-coupon', ['code' => $coupon->code]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $user   = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/wallet/redeem-coupon', ['code' => $coupon->code]);

        $response->assertStatus(422);
    }

    public function test_user_can_request_withdrawal(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id, 'balance' => 200]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 100,
            'payment_method'  => 'bank_transfer',
            'payment_details' => ['account' => '123456789'],
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_withdrawal_rejected_when_balance_insufficient(): void
    {
        $user = User::factory()->create();
        Credit::factory()->create(['user_id' => $user->id, 'balance' => 10]);

        $response = $this->actingAs($user)->postJson('/api/v1/wallet/withdraw', [
            'amount'          => 100,
            'payment_method'  => 'bank_transfer',
            'payment_details' => ['account' => '123456789'],
        ]);

        $response->assertStatus(422);
    }
}
