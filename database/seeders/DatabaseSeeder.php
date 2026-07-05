<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Credit;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ✅ firstOrCreate بدل create لتجنب التكرار
        $roleAdmin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $roleTeacher = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        $roleStudent = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $roleSupport = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@edlibya.com'],
            [
                'name'        => 'Admin',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $admin->assignRole($roleAdmin);
        Credit::firstOrCreate(['user_id' => $admin->id], ['balance' => 0]);

        // Create Teacher User
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@edlibya.com'],
            [
                'name'        => 'Teacher',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $teacher->assignRole($roleTeacher);
        Credit::firstOrCreate(['user_id' => $teacher->id], ['balance' => 0]);

        // Create Student User
        $student = User::firstOrCreate(
            ['email' => 'student@edlibya.com'],
            [
                'name'        => 'Student',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $student->assignRole($roleStudent);
        Credit::firstOrCreate(['user_id' => $student->id], ['balance' => 0]);
        // ───────────────────────────────────────────────────────
        // NEW @noon.ly ACCOUNTS (for testing/development)
        // ───────────────────────────────────────────────────────
        
        // Create Admin User (@noon.ly)
        $adminNoon = User::firstOrCreate(
            ['email' => 'admin@noon.ly'],
            [
                'name'        => 'مدير النظام',
                'password'    => Hash::make('Admin1234'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $adminNoon->assignRole($roleAdmin);
        Credit::firstOrCreate(['user_id' => $adminNoon->id], ['balance' => 0]);

        // Create Teacher User (@noon.ly)
        $teacherNoon = User::firstOrCreate(
            ['email' => 'teacher@noon.ly'],
            [
                'name'        => 'معلم النظام',
                'password'    => Hash::make('Teacher1234'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $teacherNoon->assignRole($roleTeacher);
        Credit::firstOrCreate(['user_id' => $teacherNoon->id], ['balance' => 0]);

        // Create Student User (@noon.ly)
        $studentNoon = User::firstOrCreate(
            ['email' => 'student@noon.ly'],
            [
                'name'        => 'طالب النظام',
                'password'    => Hash::make('Student1234'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $studentNoon->assignRole($roleStudent);
        Credit::firstOrCreate(['user_id' => $studentNoon->id], ['balance' => 0]);
        // Categories
        $cat1 = \App\Models\Category::firstOrCreate(['slug' => 'programming'], ['name' => 'برمجة']);
        $cat2 = \App\Models\Category::firstOrCreate(['slug' => 'design'],      ['name' => 'تصميم']);
        $cat3 = \App\Models\Category::firstOrCreate(['slug' => 'languages'],   ['name' => 'لغات']);

        // Teacher 1
        $teacher1 = User::firstOrCreate(
            ['email' => 'ahmed@edlibya.com'],
            [
                'name'        => 'أحمد صالح',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $teacher1->assignRole('teacher');
        Credit::firstOrCreate(['user_id' => $teacher1->id], ['balance' => 0]);

        // Teacher 2
        $teacher2 = User::firstOrCreate(
            ['email' => 'sara@edlibya.com'],
            [
                'name'        => 'سارة محمد',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $teacher2->assignRole('teacher');
        Credit::firstOrCreate(['user_id' => $teacher2->id], ['balance' => 0]);

        // Student 1
        $student1 = User::firstOrCreate(
            ['email' => 'mohamed@edlibya.com'],
            [
                'name'        => 'محمد علي',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $student1->assignRole('student');
        Credit::firstOrCreate(['user_id' => $student1->id], ['balance' => 0]);

        // Student 2
        $student2 = User::firstOrCreate(
            ['email' => 'layla@edlibya.com'],
            [
                'name'        => 'ليلى يوسف',
                'password'    => Hash::make('password'),
                'is_verified' => true,
                'is_active'   => true,
            ]
        );
        $student2->assignRole('student');
        Credit::firstOrCreate(['user_id' => $student2->id], ['balance' => 0]);

        // Courses
        $course1 = \App\Models\Course::firstOrCreate(
            ['slug' => 'python-basics'],
            [
                'teacher_id'  => $teacher1->id,
                'category_id' => $cat1->id,
                'title'       => 'أساسيات البرمجة بلغة بايثون',
                'description' => 'دورة شاملة لتعلم أساسيات البرمجة باستخدام لغة بايثون من الصفر.',
                'price'       => 100,
                'level'       => 'beginner',
                'status'      => 'published',
            ]
        );

        $course2 = \App\Models\Course::firstOrCreate(
            ['slug' => 'photoshop-design'],
            [
                'teacher_id'  => $teacher2->id,
                'category_id' => $cat2->id,
                'title'       => 'تصميم الجرافيك باستخدام الفوتوشوب',
                'description' => 'تعلم أساسيات التصميم الجرافيكي واحتراف الفوتوشوب.',
                'price'       => 120,
                'level'       => 'intermediate',
                'status'      => 'published',
            ]
        );

        $course3 = \App\Models\Course::firstOrCreate(
            ['slug' => 'english-beginners'],
            [
                'teacher_id'  => $teacher1->id,
                'category_id' => $cat3->id,
                'title'       => 'اللغة الإنجليزية للمبتدئين',
                'description' => 'أساسيات اللغة الإنجليزية للمبتدئين مع تمارين عملية.',
                'price'       => 80,
                'level'       => 'beginner',
                'status'      => 'published',
            ]
        );

        // تسجيل الطلاب في الكورسات (syncWithoutDetaching لتجنب التكرار)
        $student1->enrolledCourses()->syncWithoutDetaching([
            $course1->id => ['status' => 'enrolled', 'progress_percentage' => 40, 'enrolled_at' => now()],
            $course2->id => ['status' => 'enrolled', 'progress_percentage' => 70, 'enrolled_at' => now()],
        ]);

        $student2->enrolledCourses()->syncWithoutDetaching([
            $course3->id => ['status' => 'enrolled', 'progress_percentage' => 20, 'enrolled_at' => now()],
        ]);

        // Run permission seeder first to create permissions and assign to roles
        $this->call(PermissionSeeder::class);
        $this->call(SettingsSeeder::class);
    }
}