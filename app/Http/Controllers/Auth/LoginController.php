<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'has_password' => $user->hasPassword(),
            'masked_phone' => $user->masked_phone,
            'user_name' => $user->first_name,
        ]);
    }

    public function login(Request $request)
    {
        $user = User::where('kingschat_id', $request->kingschat_id)->first();

        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'Invalid credentials.',
            ]);
        }

        // Password-based login
        if ($user->hasPassword()) {
            $request->validate([
                'kingschat_id' => 'required|string',
                'password' => 'required|string',
            ]);

            $passwordService = app(PasswordService::class);
            $isValidPassword = Hash::check($request->password, $user->password);
            $isValidTempPassword = $passwordService->verifyTemporaryPassword($user, $request->password);

            if (!$isValidPassword && !$isValidTempPassword) {
                throw ValidationException::withMessages([
                    'password' => 'The password is incorrect.',
                ]);
            }
        } else {
            // Legacy phone-based login (first-time flow)
            $request->validate([
                'kingschat_id' => 'required|string',
                'phone' => 'required|string',
            ]);

            $inputPhone = preg_replace('/[^0-9]/', '', $request->phone);
            $storedPhone = preg_replace('/[^0-9]/', '', $user->phone);

            if ($inputPhone !== $storedPhone) {
                throw ValidationException::withMessages([
                    'phone' => 'The phone number does not match our records.',
                ]);
            }
        }

        // Log the user in
        Auth::login($user, $request->boolean('remember'));

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Log activity
        ActivityLog::log(ActivityLog::ACTION_LOGIN);

        $request->session()->regenerate();

        // If first-time login, generate temporary password
        $tempPasswordData = null;
        if (!$user->hasPassword()) {
            $passwordService = app(PasswordService::class);
            $tempPasswordData = $passwordService->generateTemporaryPassword($user);
            ActivityLog::log(ActivityLog::ACTION_TEMPORARY_PASSWORD_GENERATED, $user);
        }

        // Redirect based on role
        $redirectTo = $this->getRedirectPath($user);

        if ($request->wantsJson()) {
            $response = [
                'success' => true,
                'redirect' => $redirectTo,
                'requires_password_setup' => !$user->hasPassword(),
                'csrf_token' => csrf_token(), // Return new CSRF token after session regeneration
            ];

            if ($tempPasswordData) {
                $response['temporary_password'] = $tempPasswordData['password'];
                $response['expires_at'] = $tempPasswordData['expires_at']->toIso8601String();
            }

            return response()->json($response);
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

    public function generateTemporaryPassword(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            throw ValidationException::withMessages([
                'error' => 'User not authenticated.',
            ]);
        }

        $passwordService = app(PasswordService::class);
        $tempPassword = $passwordService->generateTemporaryPassword($user);

        ActivityLog::log(ActivityLog::ACTION_TEMPORARY_PASSWORD_GENERATED, $user);

        return response()->json([
            'success' => true,
            'temporary_password' => $tempPassword['password'],
            'expires_at' => $tempPassword['expires_at']->toIso8601String(),
        ]);
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'kingschat_id' => 'required|string',
            'phone' => 'required|string|size:4',
        ]);

        $user = User::where('kingschat_id', $request->kingschat_id)->active()->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'kingschat_id' => 'User not found.',
            ]);
        }

        // Verify last 4 digits of phone
        if (substr($user->phone, -4) !== $request->phone) {
            throw ValidationException::withMessages([
                'phone' => 'Phone verification failed.',
            ]);
        }

        $passwordService = app(PasswordService::class);
        $tempPassword = $passwordService->generateTemporaryPassword($user);

        ActivityLog::log(ActivityLog::ACTION_PASSWORD_RESET_REQUESTED, $user);

        return response()->json([
            'success' => true,
            'temporary_password' => $tempPassword['password'],
            'expires_at' => $tempPassword['expires_at']->toIso8601String(),
        ]);
    }

    public function showSetupPassword()
    {
        $user = Auth::user();

        if ($user->hasPassword()) {
            return redirect()->route('staff.dashboard');
        }

        return view('auth.setup-password');
    }

    public function setupPassword(Request $request)
    {
        $request->validate([
            'temporary_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);

        $user = Auth::user();
        $passwordService = app(PasswordService::class);

        // Verify temporary password
        if (!$passwordService->verifyTemporaryPassword($user, $request->temporary_password)) {
            throw ValidationException::withMessages([
                'temporary_password' => 'Invalid or expired temporary password.',
            ]);
        }

        $passwordService->changePassword($user, $request->new_password);

        ActivityLog::log(ActivityLog::ACTION_PASSWORD_SET, $user);

        return response()->json([
            'success' => true,
            'message' => 'Password set successfully.',
        ]);
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);

        $user = Auth::user();
        $passwordService = app(PasswordService::class);

        // Verify current password (could be temp or regular)
        $isValid = Hash::check($request->current_password, $user->password) ||
                   $passwordService->verifyTemporaryPassword($user, $request->current_password);

        if (!$isValid) {
            throw ValidationException::withMessages([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $passwordService->changePassword($user, $request->new_password);

        ActivityLog::log(ActivityLog::ACTION_PASSWORD_CHANGED, $user);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
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
