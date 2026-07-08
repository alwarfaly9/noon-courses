<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    /** Fields safe for mass-assignment from user-controlled input. */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'bio',
        'specialization',
        'website',
        'location',
        'onboarding_completed',
        'referral_code',
    ];

    /**
     * Fields that must only be set via explicit, trusted code paths — never via
     * Request::all() / create($request->all()).
     * Kept out of $fillable to prevent mass-assignment attacks.
     *  is_verified, is_active, is_verified_instructor — admin-only toggles
     *  last_login_at, last_login_ip — set only inside AuthController
     */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_verified'          => 'boolean',
            'is_active'            => 'boolean',
            'onboarding_completed' => 'boolean',
            'last_login_at'        => 'datetime',
            'is_verified_instructor' => 'boolean',
        ];
    }

    // Relationships
    public function credits()
    {
        return $this->hasOne(Credit::class);
    }

    public function coursesAsTeacher()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function teachingCourses()
    {
        return $this->coursesAsTeacher();
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'student_id');
    }

    public function enrolledCourses()
    {
        return $this->belongsToMany(Course::class, 'course_enrollments', 'student_id', 'course_id')
                    ->withPivot('status', 'progress_percentage', 'enrolled_at', 'completed_at')
                    ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function reviews()
    {
        return $this->hasMany(CourseReview::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function withdrawRequests()
    {
        return $this->hasMany(WithdrawRequest::class);
    }

    // Helper Methods
    public function hasRoleName($role)
    {
        // Delegate to Spatie roles by name instead of slug
        return $this->getRoleNames()->contains($role);
    }

    public function hasPermissionName($permission)
    {
        return $this->hasPermissionTo($permission);
    }

    public function isTeacher()
    {
        return $this->hasRole('teacher');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isStudent()
    {
        return $this->hasRole('student');
    }

    public function getCreditBalance()
    {
        return $this->credits?->balance ?? 0;
    }
}
