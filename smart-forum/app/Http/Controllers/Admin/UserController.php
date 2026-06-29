<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,lecturer,student',
            'is_approved' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_approved' => $request->has('is_approved') ? true : false,
            'status' => 'active',
            'last_communicated_at' => now(),
        ]);

        // Audit log
        $this->logAction($user->id, 'user_created', "Created user '{$user->name}' with role '{$user->role}'");

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' created successfully.");
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,lecturer,student',
            'status' => 'required|in:active,warned_once,warned_twice,blacklisted',
            'is_approved' => 'sometimes|boolean',
        ]);

        $oldRole = $user->role;
        $oldStatus = $user->status;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
            'is_approved' => $request->has('is_approved'),
        ]);

        // Audit log
        $details = [];
        if ($oldRole != $request->role) {
            $details[] = "Role changed from '{$oldRole}' to '{$request->role}'";
        }
        if ($oldStatus != $request->status) {
            $details[] = "Status changed from '{$oldStatus}' to '{$request->status}'";
        }
        if (!empty($details)) {
            $this->logAction($user->id, 'user_updated', implode(', ', $details));
        }

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user)
    {
        $name = $user->name;
        $user->delete();

        $this->logAction($user->id, 'user_deleted', "Deleted user '{$name}'");

        return redirect()->route('admin.users.index')
            ->with('success', "User '{$name}' deleted.");
    }

    public function approve(User $user)
    {
        $user->update(['is_approved' => true]);
        $this->logAction($user->id, 'user_approved', "Approved user '{$user->name}'");
        return redirect()->back()->with('success', "User '{$user->name}' approved.");
    }

    public function reject(User $user)
    {
        $name = $user->name;
        $user->delete();
        $this->logAction(null, 'user_rejected', "Rejected user '{$name}'");
        return redirect()->back()->with('success', "User '{$name}' rejected and removed.");
    }

    public function promote(Request $request, User $user)
    {
        $newRole = $request->input('role');
        if (!in_array($newRole, ['admin', 'lecturer', 'student'])) {
            return redirect()->back()->with('error', 'Invalid role.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $newRole, 'is_approved' => true]);

        $this->logAction($user->id, 'role_promoted', "Promoted from '{$oldRole}' to '{$newRole}'");

        return redirect()->back()->with('success', "User '{$user->name}' promoted to '{$newRole}'.");
    }

    protected function logAction($userId, $action, $details)
    {
        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}