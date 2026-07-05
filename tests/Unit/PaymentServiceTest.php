<?php

namespace Tests\Unit;

use App\Models\Credit;
use App\Models\CreditCard;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentService();
    }

    public function test_redeem_credit_card_adds_balance(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id, 'balance' => 100]);

        CreditCard::create([
            'serial_number' => 'CARD-UNIT-001',
            'value' => 50,
            'status' => 'active',
            'created_by' => $user->id,
            'expires_at' => now()->addYear(),
        ]);

        $result = $this->service->redeemCreditCard($user, 'CARD-UNIT-001');

        $this->assertEquals(50, $result['credit_added']);
        $this->assertEquals(150, $result['new_balance']);
    }

    public function test_redeem_invalid_card_throws_exception(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id, 'balance' => 0]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid or expired credit card');

        $this->service->redeemCreditCard($user, 'NONEXISTENT-CARD');
    }

    public function test_generate_cards_creates_correct_count(): void
    {
        $admin = User::factory()->create();

        $cards = $this->service->generateCards($admin, 5, 25.0);

        $this->assertCount(5, $cards);
        foreach ($cards as $card) {
            $this->assertEquals(25, $card->value);
            $this->assertEquals('active', $card->status);
        }
    }
}
