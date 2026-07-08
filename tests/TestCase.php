<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureRolesExist();
    }

    protected function ensureRolesExist(): void
    {
        try {
            $roles = ['student', 'teacher', 'admin'];
            foreach ($roles as $role) {
                Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            }

            // Ensure admin has access_admin_panel permission
            $perm = Permission::firstOrCreate(['name' => 'access_admin_panel', 'guard_name' => 'web']);
            Role::where('name', 'admin')->first()?->givePermissionTo($perm);
        } catch (\Exception $e) {
            // Skip if roles table doesn't exist (Unit tests without RefreshDatabase)
        }
    }

    protected function createUserWithRole(string $role, array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }
}
