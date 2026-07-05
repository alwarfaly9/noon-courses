<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Credit;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List all users with search and role filter.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->latest()->paginate(20);
        return view('admin.users', compact('users'));
    }

    /**
     * Show create user form.
     */
    public function create()
    {
        return view('admin.users-form');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => bcrypt($request->password),
            'phone'       => $request->phone,
            'is_active'   => true,
            'is_verified' => true,
        ]);

        $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web']);
        $user->assignRole($role);

        if ($request->role === 'student') {
            Credit::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        }

        return redirect()->route('admin.users')->with('success', 'تم إنشاء المستخدم بنجاح');
    }

    /**
     * Show edit user form.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return view('admin.users-form', compact('user'));
    }

    /**
     * Update an existing user (name, email, role, phone, password, active).
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        // Sync role — remove old, assign new
        $newRoleName = $request->role;
        $role = Role::firstOrCreate(['name' => $newRoleName, 'guard_name' => 'web']);
        $user->syncRoles([$role]);

        // Ensure student credit record exists when switching to student
        if ($newRoleName === 'student') {
            Credit::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
        }

        return redirect()->route('admin.users')->with('success', 'تم تحديث بيانات المستخدم بنجاح');
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleActive($id)
    {
        $user = User::findOrFail($id);

        // Prevent admins from deactivating themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الشخصي');
        }

        $user->forceFill(['is_active' => !$user->is_active])->save();
        $status = $user->is_active ? 'تفعيل' : 'تعطيل';

        return back()->with('success', "تم {$status} حساب {$user->name}");
    }
}
