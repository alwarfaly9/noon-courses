<?php

namespace Tests\Unit;

use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;
    public function test_calculate_split_default_rate(): void
    {
        $split = CommissionService::calculateSplit(100);

        // Default rate is 20%
        $this->assertEquals(20.0, $split['commission']);
        $this->assertEquals(80.0, $split['earnings']);
    }

    public function test_calculate_split_preserves_total(): void
    {
        $amount = 73.50;
        $split = CommissionService::calculateSplit($amount);

        $this->assertEquals($amount, $split['commission'] + $split['earnings']);
    }
}
