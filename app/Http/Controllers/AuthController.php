<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Credit;
use Spatie\Permission\Models\Role;
use App\Models\ActivityLog;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\OtpService;
use App\Services\ProfileService;
use App\Http\Resources\UserResource;
use App\Mail\PasswordResetMail;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $email = strtolower(trim($request->email));

        // ── Server-side OTP gate ──────────────────────────────────────
        // verifyOtp() stores a short-lived flag after successful verification.
        // Registration is rejected if the flag is absent.
        $verifiedKey = "email_otp_verified:{$email}";
        if (!Cache::get($verifiedKey)) {
            Log::warning('[Register] Attempt without prior OTP verification', [
                'email' => $email,
                'ip'    => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Email must be verified via OTP before registering',
            ], 403);
        }

        Log::info('[Register] Starting user creation', [
            'email' => $email,
            'ip'    => $request->ip(),
        ]);

        $user = (new User())->forceFill([
            'name'              => $request->name,
            'email'             => $email,
            'password'          => bcrypt($request->password),
            'phone'             => $request->phone,
            'email_verified_at' => now(),
            'is_verified'       => true,
            'is_active'         => true,
        ]);
        $user->save();

        // Consume the OTP-verified flag so it cannot be reused
        Cache::forget($verifiedKey);

        Log::info('[Register] User created', [
            'id'                => $user->id,
            'email'             => $user->email,
            'is_active'         => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
        ]);

        // Assign student role via Spatie
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $user->assignRole($studentRole);

        // Create credit account for student
        Credit::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        // Log activity
        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'register',
            'model_type'  => 'User',
            'model_id'    => $user->id,
            'description' => 'User registered successfully',
            'ip_address'  => $request->ip(),
        ]);

        // Track referral if a code was provided at registration
        if ($request->filled('referral_code')) {
            app(ReferralService::class)->trackRegistration($user, $request->referral_code);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = $user->load('roles')->toArray();
        $userData['wallet_balance'] = 0;

        Log::info('[Register] Completed — token issued', ['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user'         => $userData,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ]
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $email    = strtolower(trim($request->email));
        $password = $request->password; // do NOT trim — user chose their password

        Log::info('[Login] Attempt', [
            'email' => $email,
            'ip'    => $request->ip(),
        ]);

        // Look up user explicitly — avoids Auth::attempt() starting a web session
        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::warning('[Login] User not found', ['email' => $email]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        Log::info('[Login] User found', [
            'id'                => $user->id,
            'is_active'         => $user->is_active,
            'deleted_at'        => $user->deleted_at,
            'password_algo'     => substr($user->password, 0, 7),
            'email_verified_at' => $user->email_verified_at,
        ]);

        if (!$user->is_active) {
            Log::warning('[Login] Account disabled', ['user_id' => $user->id]);
            // Return 401 (not 403) to avoid confirming account existence to attackers
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!Hash::check($password, $user->password)) {
            Log::warning('[Login] Password mismatch', [
                'user_id'           => $user->id,
                'provided_length'   => strlen($password),
                'stored_algo'       => substr($user->password, 0, 7),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        Log::info('[Login] Credentials valid', ['user_id' => $user->id]);

        // Update last login — forceFill required as these fields are not in $fillable
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        Log::info('[Login] Token issued', ['user_id' => $user->id]);

        // Log activity
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => 'User',
            'model_id' => $user->id,
            'description' => 'User logged in',
            'ip_address' => $request->ip(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $userData = $user->load('roles', 'credits')->toArray();
        $userData['wallet_balance'] = $user->credits ? $user->credits->balance : 0;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $userData,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'logout',
            'model_type' => 'User',
            'model_id' => $request->user()->id,
            'description' => 'User logged out',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token and issue a fresh one
        $request->user()->currentAccessToken()->delete();
        $newToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $newToken,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles', 'credits');
        
        // Add wallet_balance attribute manually if not using API Resource
        $userData = $user->toArray();
        $userData['wallet_balance'] = $user->credits ? $user->credits->balance : 0;

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    public function updateProfile(Request $request, ProfileService $profileService)
    {
        try {
            $user = $profileService->updateProfile($request->user(), $request->all(), $request->ip());

            $userData = new UserResource($user->load('roles', 'credits'));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $userData,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني غير موجود',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;
        $code = (string) random_int(100000, 999999);

        // Store hashed code in password_reset_tokens (expires in 15 min)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        try {
            Mail::to($email)->send(new PasswordResetMail($code));
        } catch (\Exception $e) {
            Log::error('[PasswordReset] Failed to send email to ' . $email . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال البريد الإلكتروني، يرجى المحاولة لاحقاً',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز إعادة تعيين كلمة المرور إلى بريدك الإلكتروني',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email|exists:users,email',
            'code'                  => 'required|string|size:6',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير موجود أو منتهي الصلاحية',
            ], 404);
        }

        // Expire after 15 minutes
        if (now()->diffInMinutes($record->created_at) > 15) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الرمز، يرجى طلب رمز جديد',
            ], 410);
        }

        if (!Hash::check($request->code, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'الرمز غير صحيح',
            ], 400);
        }

        // Update password and revoke all tokens for security
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => $request->password]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن',
        ]);
    }

    // Send OTP to phone or email (stores code in cache)
    public function sendOtp(Request $request, OtpService $otpService)
    {
        try {
            $otpService->sendOtp($request->all());
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Verify OTP
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        try {
            $otpService->verifyOtp($request->all());
            return response()->json([
                'success' => true,
                'message' => 'OTP verified',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }
}
