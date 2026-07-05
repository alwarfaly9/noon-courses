<?php

namespace App\Services;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data, string $ipAddress)
    {
        $validator = Validator::make($data, [
            'name'           => 'sometimes|string|max:255',
            'phone'          => 'sometimes|string|max:30',
            'bio'            => 'nullable|string|max:1000',
            'specialization' => 'nullable|string|max:255',
            'website'        => 'nullable|url|max:255',
            'location'       => 'nullable|string|max:255',
            'avatar'         => 'nullable|url|max:512',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user->update($validator->validated());

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'update_profile',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'User updated profile',
            'ip_address' => $ipAddress,
        ]);

        return $user;
    }
}
