<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Course;
use App\Models\Credit;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_valid_coupon(): void
    {
        $user   = User::factory()->create();
        $course = Course::factory()->create(['price' => 100]);
        $coupon = Coupon::factory()->create([
            'is_active'          => true,
            'usage_limit'        => 10,
            'used_count'         => 0,
            'expires_at'         => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/student/coupons/validate', [
                'code'      => $coupon->code,
                'course_id' => $course->id,
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $course = Course::factory()->create(['price' => 100]);
        $user   = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/student/coupons/validate', [
                'code'      => $coupon->code,
                'course_id' => $course->id,
            ]);

        $response->assertStatus(410);
    }

    public function test_user_can_request_withdrawal(): void
    {
        Permission::firstOrCreate(['name' => 'manage_own_courses', 'guard_name' => 'web']);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $teacher->givePermissionTo('manage_own_courses');
        Credit::factory()->create(['user_id' => $teacher->id, 'balance' => 200]);

        $response = $this->actingAs($teacher)->postJson('/api/v1/teacher/withdraw-requests', [
            'amount'         => 100,
            'bank_name'      => 'Test Bank',
            'account_name'   => 'Test User',
            'account_number' => '123456789',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_withdrawal_rejected_when_balance_insufficient(): void
    {
        Permission::firstOrCreate(['name' => 'manage_own_courses', 'guard_name' => 'web']);
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $teacher->givePermissionTo('manage_own_courses');
        Credit::factory()->create(['user_id' => $teacher->id, 'balance' => 10]);

        $response = $this->actingAs($teacher)->postJson('/api/v1/teacher/withdraw-requests', [
            'amount'         => 100,
            'bank_name'      => 'Test Bank',
            'account_name'   => 'Test User',
            'account_number' => '123456789',
        ]);

        $response->assertStatus(422);
    }
}
