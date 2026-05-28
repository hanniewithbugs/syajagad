<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function redirectToDashboard()
    {
        return Auth::user()?->role === 'admin'
            ? redirect()->route('dbAdmin')
            : redirect()->route('dbSantri');
    }

    // tampil login
    public function showLogin()
    {
        return view('login');
    }

    // proses login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required'],
            'role' => ['required', Rule::in(['admin', 'santri'])],
        ]);

        $login = trim($validated['email']);
        $user = User::where('role', $validated['role'])
            ->where(function ($query) use ($login) {
                if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $login);
                    return;
                }

                $nisCompact = preg_replace('/\D+/', '', $login);
                $query->where('username', $login)
                    ->orWhere('nis', $login)
                    ->orWhereRaw("REPLACE(nis, ' ', '') = ?", [$nisCompact]);
            })
            ->first();

        if ($user && Hash::check($validated['password'], $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();

            return Auth::user()->role === 'admin'
                ? redirect()->route('dbAdmin')
                : redirect()->route('dbSantri');
        }

        return back()
            ->withInput($request->only('email', 'role'))
            ->with('error', 'Email, username, NIS/NIP, password, atau peran tidak sesuai');
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
            'role' => ['nullable', Rule::in(['santri'])],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'nis' => $validated['nis'],
            'name' => $validated['name'],
            'tgl_lahir' => $validated['tgl_lahir'],
            'alamat' => $validated['alamat'],
            'role' => 'santri',
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect()->route('dbSantri');
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

        $paymentData = PaymentController::buildPaymentData(Auth::user());

        return view('dbSantri', [
            'paymentData' => $paymentData,
        ]);
    }

    public function updateSantriProfile(Request $request)
    {
        $user = Auth::user();

        if ($user?->role !== 'santri') {
            abort(403, 'Akses ditolak untuk role ini.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'alamat' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'name' => $user->name,
                'nis' => $user->nis,
                'email' => $user->email,
                'username' => $user->username,
                'alamat' => $user->alamat,
                'tgl_lahir' => optional($user->tgl_lahir)->format('Y-m-d'),
            ],
        ]);
    }

    public function changeSantriPassword(Request $request)
    {
        $user = Auth::user();

        if ($user?->role !== 'santri') {
            abort(403, 'Akses ditolak untuk role ini.');
        }

        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Kata sandi lama tidak sesuai.',
                'errors' => [
                    'old_password' => ['Kata sandi lama tidak sesuai.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'message' => 'Password berhasil diganti.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
