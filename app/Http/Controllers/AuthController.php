<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // A new session id on login defeats session fixation.
            $request->session()->regenerate();

            if (Auth::user()->isStaff()) {
                return redirect()->intended(route('admin.dashboard'));
            }

            return redirect()->intended(route('buyer.dashboard'));
        }

        // One message for unknown email and wrong password, so the form
        // cannot be used to discover which addresses have accounts.
        return back()
            ->withErrors(['email' => 'The provided credentials do not match our records.'])
            ->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
        ]);

        // Hash explicitly rather than leaning on the "hashed" cast: the cast
        // stores any input that already looks like a bcrypt hash verbatim.
        // Role is assigned directly because it is not mass assignable.
        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);
        $user->role = 'buyer';
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('buyer.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
