<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function verifyKingsChatId(Request $request)
    {
        $request->validate([
            'kingschat_id' => 'required|string|max:255',
        ]);

        $user = User::where('kingschat_id', $request->kingschat_id)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'This KingsChat ID is not registered in our system. Please contact your administrator.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'Your account has been deactivated. Please contact your administrator.',
            ]);
        }

        // Return masked phone for verification step
        return response()->json([
            'success' => true,
            'masked_phone' => $user->masked_phone,
            'user_name' => $user->first_name,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'kingschat_id' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $user = User::where('kingschat_id', $request->kingschat_id)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'Invalid KingsChat ID.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'Your account has been deactivated.',
            ]);
        }

        // Verify phone number
        $inputPhone = preg_replace('/[^0-9]/', '', $request->phone);
        $storedPhone = preg_replace('/[^0-9]/', '', $user->phone);

        if ($inputPhone !== $storedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'The phone number does not match our records.',
            ]);
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log activity
        ActivityLog::log(ActivityLog::ACTION_LOGIN);

        $request->session()->regenerate();

        // Redirect based on role
        $redirectTo = $this->getRedirectPath($user);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $redirectTo,
            ]);
        }

        return redirect()->intended($redirectTo);
    }

    public function logout(Request $request)
    {
        // Only log if user is authenticated
        if (Auth::check()) {
            ActivityLog::log(ActivityLog::ACTION_LOGOUT);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    protected function getRedirectPath(User $user): string
    {
        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('head_of_operations')) {
            return route('admin.dashboard');
        }

        if ($user->hasRole('hod')) {
            return route('hod.dashboard');
        }

        return route('staff.dashboard');
    }
}
