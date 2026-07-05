<?php

namespace App\Services;

use App\Models\Setting;

class CommissionService
{
    /**
     * Get the global platform commission rate (percentage).
     * Defaults to 20% if not set.
     * @return float (e.g., 0.20 for 20%)
     */
    public static function getRate()
    {
        $setting = Setting::where('key', 'platform_commission_rate')->first();
        
        if (!$setting) {
            return 0.20; // Default 20%
        }

        return (float) $setting->value / 100;
    }

    /**
     * Calculate commission and earnings.
     * @param float $amount
     * @return array ['commission' => float, 'earnings' => float]
     */
    public static function calculateSplit($amount)
    {
        $rate = self::getRate();
        $commission = round($amount * $rate, 2);
        $earnings = $amount - $commission;

        return [
            'commission' => $commission,
            'earnings' => $earnings
        ];
    }
}
