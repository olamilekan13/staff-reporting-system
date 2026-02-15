<?php

namespace App\Services;

use App\Models\TemporaryPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PasswordService
{
    /**
     * Generate a temporary password for the user
     */
    public function generateTemporaryPassword(User $user): array
    {
        $password = $this->generateRandomPassword();

        $tempPassword = TemporaryPassword::create([
            'user_id' => $user->id,
            'password' => $password,
            'expires_at' => now()->addHours(24),
        ]);

        return [
            'password' => $password,
            'expires_at' => $tempPassword->expires_at,
        ];
    }

    /**
     * Verify a temporary password for the user
     * Note: Does not mark as used - that happens in changePassword()
     */
    public function verifyTemporaryPassword(User $user, string $password): bool
    {
        $tempPassword = TemporaryPassword::where('user_id', $user->id)
            ->valid()
            ->latest()
            ->first();

        if (!$tempPassword) {
            return false;
        }

        return Hash::check($password, $tempPassword->password);
    }

    /**
     * Change user password and cleanup
     */
    public function changePassword(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
            'password_set' => true,
        ]);

        // Invalidate all temporary passwords
        TemporaryPassword::where('user_id', $user->id)->delete();

        // Revoke all existing tokens for security
        $user->tokens()->delete();
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        return $errors;
    }

    /**
     * Generate a random password (8 characters alphanumeric)
     * Excludes confusing characters: 0/O, 1/l/I
     */
    private function generateRandomPassword(int $length = 8): string
    {
        // Exclude confusing characters: 0, O, 1, l, I
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnopqrstuvwxyz';
        $numbers = '23456789';
        $characters = $uppercase . $lowercase . $numbers;

        $password = '';

        // Ensure at least one of each required type
        $password .= $uppercase[rand(0, strlen($uppercase) - 1)];
        $password .= $lowercase[rand(0, strlen($lowercase) - 1)];
        $password .= $numbers[rand(0, strlen($numbers) - 1)];

        // Fill remaining characters
        for ($i = 0; $i < $length - 3; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Shuffle to randomize position of required characters
        return str_shuffle($password);
    }
}
