<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    // tampil login
    public function showLogin()
    {
        return view('login');
    }

    // proses login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', Rule::in(['admin', 'santri'])],
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return Auth::user()->role === 'admin'
                ? redirect()->route('dbAdmin')
                : redirect()->route('dbSantri');
        }

        return back()
            ->withInput($request->only('email', 'role'))
            ->with('error', 'Email, password, atau peran tidak sesuai');
    }

    // tampil register
    public function showRegister()
    {
        return view('register');
    }

    // proses register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['required', 'string', 'max:20', 'unique:users,nis'],
            'name' => ['required', 'string', 'max:255'],
            'tgl_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(['admin', 'santri'])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'nis' => $validated['nis'],
            'name' => $validated['name'],
            'tgl_lahir' => $validated['tgl_lahir'],
            'alamat' => $validated['alamat'],
            'role' => $validated['role'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return $user->role === 'admin'
            ? redirect()->route('dbAdmin')
            : redirect()->route('dbSantri');
    }

    public function showAdminDashboard()
    {
        if (Auth::user()?->role !== 'admin') {
            abort(403, 'Akses ditolak untuk role ini.');
        }

        return view('dbAdmin');
    }

    public function showSantriDashboard()
    {
        if (Auth::user()?->role !== 'santri') {
            abort(403, 'Akses ditolak untuk role ini.');
        }

        return view('dbSantri');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
