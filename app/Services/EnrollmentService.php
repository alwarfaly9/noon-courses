<?php

namespace App\Services;

use App\Events\EnrollmentCreated;
use App\Events\PaymentCompleted;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Credit;
use App\Models\Coupon;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CacheService;
use App\Services\ReferralService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EnrollmentService
{
    /**
     * Enroll a student in a course, with optional coupon code.
     * Uses DB transactions with lockForUpdate() for financial safety.
     *
     * @throws \Exception
     */
    public function enroll(User $student, Course $course, ?string $couponCode = null): array
    {
        // Check if already enrolled
        if ($student->enrolledCourses()->where('course_id', $course->id)->exists()) {
            throw new \Exception('Already enrolled in this course');
        }

        // Only published courses can be enrolled
        if ($course->status !== 'published') {
            throw new \Exception('Course is not published');
        }

        $price = $course->discount_price ?? $course->price;
        $appliedCoupon = null;

        if ($price > 0 && $couponCode) {
            $appliedCoupon = $this->resolveCoupon($couponCode, $price);
            $price = $appliedCoupon['final_price'];
        }

        if ($price > 0) {
            return $this->enrollPaid($student, $course, $price, $appliedCoupon);
        }

        return $this->enrollFree($student, $course);
    }

    /**
     * After enrollment, convert pending referral on first enrollment.
     */
    private function convertReferralIfFirstEnrollment(User $student): void
    {
        $enrollmentCount = CourseEnrollment::where('student_id', $student->id)->count();
        if ($enrollmentCount === 1) {
            app(ReferralService::class)->convertReferral($student);
        }
    }

    /**
     * Validate and resolve a coupon, returning pricing details.
     * @throws \Exception if coupon is invalid
     */
    private function resolveCoupon(string $code, float $originalPrice): array
    {
        $coupon = Coupon::where('code', $code)->where('is_active', true)->first();

        if (!$coupon) throw new \Exception('كود الخصم غير صالح');
        if ($coupon->expires_at?->isPast()) throw new \Exception('انتهت صلاحية كود الخصم');
        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new \Exception('تم استنفاد الحد الأقصى لاستخدام هذا الكود');
        }

        if ($coupon->discount_type === 'percentage') {
            $discount = ($originalPrice * $coupon->discount_value) / 100;
            if ($coupon->maximum_discount) $discount = min($discount, $coupon->maximum_discount);
        } else {
            $discount = min($coupon->discount_value, $originalPrice);
        }

        return [
            'coupon'      => $coupon,
            'discount'    => round($discount, 2),
            'final_price' => round(max(0, $originalPrice - $discount), 2),
        ];
    }

    /**
     * Handle paid course enrollment with proper locking.
     */
    private function enrollPaid(User $student, Course $course, float $price, ?array $couponData = null): array
    {
        return DB::transaction(function () use ($student, $course, $price, $couponData) {
            // Lock the credit row to prevent race conditions
            $credit = Credit::where('user_id', $student->id)->lockForUpdate()->first();

            if (!$credit || $credit->balance < $price) {
                throw new \Exception('Insufficient balance');
            }

            // Deduct balance
            $credit->decrement('balance', $price);

            // Calculate commission split
            $split = CommissionService::calculateSplit($price);

            // Student purchase transaction
            $transaction = Transaction::create([
                'transaction_number' => 'TRX-' . Str::upper(Str::random(12)),
                'user_id' => $student->id,
                'course_id' => $course->id,
                'coupon_id' => $couponData ? $couponData['coupon']->id : null,
                'amount' => -$price,
                'type' => 'purchase',
                'notes' => "Purchased course: {$course->title}" . ($couponData ? " (coupon: {$couponData['coupon']->code})" : ''),
                'platform_commission' => $split['commission'],
                'instructor_earnings' => $split['earnings'],
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Increment coupon used_count
            if ($couponData) {
                $couponData['coupon']->increment('used_count');
            }

            // Teacher earnings transaction
            Transaction::create([
                'transaction_number' => 'EARN-' . Str::upper(Str::random(12)),
                'user_id' => $course->teacher_id,
                'course_id' => $course->id,
                'amount' => $split['earnings'],
                'type' => 'enrollment',
                'notes' => "Earnings from student {$student->name} for course {$course->title}",
                'platform_commission' => $split['commission'],
                'instructor_earnings' => $split['earnings'],
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Create enrollment
            CourseEnrollment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
                'progress_percentage' => 0,
            ]);

            // Convert referral on first enrollment
            $this->convertReferralIfFirstEnrollment($student);

            // Dispatch events for notification pipeline
            EnrollmentCreated::dispatch($student, $course);
            PaymentCompleted::dispatch($student, $course, $transaction);

            return [
                'new_balance' => $credit->fresh()->balance,
            ];
        });
    }

    /**
     * Handle free course enrollment.
     */
    private function enrollFree(User $student, Course $course): array
    {
        CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'progress_percentage' => 0,
        ]);

        // Convert referral on first enrollment
        $this->convertReferralIfFirstEnrollment($student);

        // Dispatch event for notification pipeline
        EnrollmentCreated::dispatch($student, $course);

        CacheService::invalidateUser($student->id);

        return [
            'new_balance' => $student->credits ? $student->credits->balance : 0,
        ];
    }
}
