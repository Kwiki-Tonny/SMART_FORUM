<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['sometimes', 'in:student,lecturer'], // Allow role selection if added to form
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'student', // Default to student
            'status' => 'active',
            'is_approved' => false, // MUST be approved by admin
            'last_communicated_at' => now(),
        ]);

        event(new Registered($user));

        // ============================================
        // DO NOT AUTO-LOGIN – user must be approved first
        // ============================================
        // Auth::login($user); // <-- REMOVED

        // Redirect to login with a success message
        return redirect()->route('login')
            ->with('success', 'Registration successful! Your account is pending admin approval. You will be notified when approved.');
    }
}