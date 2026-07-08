<?php

namespace Tests\Feature\Permissions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed --class=PermissionSeeder');

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->teacher = User::factory()->create(['is_active' => true]);
        $this->teacher->assignRole('teacher');

        $this->student = User::factory()->create(['is_active' => true]);
        $this->student->assignRole('student');
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertOk();
    }

    public function test_teacher_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_student_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/users');

        $response->assertOk();
    }

    public function test_admin_can_view_analytics(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/analytics/summary');

        $response->assertOk();
    }

    public function test_admin_can_manage_coupons(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/coupons', [
                'code'           => 'TEST20',
                'name'           => 'Test Coupon',
                'discount_type'  => 'percentage',
                'discount_value' => 20,
            ]);

        $response->assertStatus(201);
    }
}
