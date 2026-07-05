<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'EdLibya',
                'type' => 'text',
                'group' => 'general',
                'description' => 'The name of the platform',
            ],
            [
                'key' => 'platform_commission_rate',
                'value' => '20', // %
                'type' => 'number',
                'group' => 'payment',
                'description' => 'The percentage of the course price that the platform takes as commission',
            ],
            [
                'key' => 'support_email',
                'value' => 'support@edlibya.com',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Email address for support inquiries',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
