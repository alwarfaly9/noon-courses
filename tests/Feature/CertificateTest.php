<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_verify_certificate_without_auth(): void
    {
        $certificate = Certificate::factory()->create();

        $response = $this->getJson("/api/v1/certificates/verify/{$certificate->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_download_requires_authentication(): void
    {
        $certificate = Certificate::factory()->create();

        $response = $this->getJson("/api/v1/certificates/{$certificate->id}/download");

        $response->assertStatus(401);
    }

    public function test_student_can_download_own_certificate(): void
    {
        $user        = User::factory()->create();
        $certificate = Certificate::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/certificates/{$certificate->id}/download");

        // 200 (file) or 302 (redirect to file) both acceptable
        $this->assertContains($response->status(), [200, 302]);
    }

    public function test_student_cannot_download_another_students_certificate(): void
    {
        $owner       = User::factory()->create();
        $attacker    = User::factory()->create();
        $certificate = Certificate::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker)
            ->getJson("/api/v1/certificates/{$certificate->id}/download");

        $response->assertStatus(403);
    }
}
