<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('student');

        Credit::create(['user_id' => $this->user->id, 'balance' => 0]);
    }

    public function test_user_can_get_credit_balance(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payment/credit-balance');

        $response->assertOk()
            ->assertJsonPath('data.balance', 0);
    }

    public function test_user_can_redeem_valid_credit_card(): void
    {
        $card = CreditCard::create([
            'serial_number' => 'CARD-TEST-001',
            'value' => 50,
            'status' => 'active',
            'created_by' => $this->user->id,
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment/credit-cards/redeem', [
                'serial_number' => 'CARD-TEST-001',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(50, $this->user->credits->fresh()->balance);
        $this->assertEquals('used', $card->fresh()->status);
    }

    public function test_user_cannot_redeem_used_card(): void
    {
        CreditCard::create([
            'serial_number' => 'CARD-USED-001',
            'value' => 50,
            'status' => 'used',
            'created_by' => $this->user->id,
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment/credit-cards/redeem', [
                'serial_number' => 'CARD-USED-001',
            ]);

        $response->assertStatus(400);
        $this->assertEquals(0, $this->user->credits->fresh()->balance);
    }

    public function test_user_cannot_redeem_expired_card(): void
    {
        CreditCard::create([
            'serial_number' => 'CARD-EXP-001',
            'value' => 50,
            'status' => 'active',
            'created_by' => $this->user->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payment/credit-cards/redeem', [
                'serial_number' => 'CARD-EXP-001',
            ]);

        $response->assertStatus(400);
    }
}
