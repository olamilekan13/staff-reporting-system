@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<div x-data="{
    step: 1,
    loading: false,
    kingschatId: '',
    phone: '',
    temporaryPassword: '',
    newPassword: '',
    newPasswordConfirmation: '',
    passwordCopied: false,
    tempPasswordExpiry: '',
    error: '',
    showTempPassword: false,
    showNewPassword: false,
    showNewPasswordConfirmation: false,

    async requestReset() {
        this.error = '';

        if (!this.kingschatId.trim()) {
            this.error = 'Please enter your KingsChat ID.';
            return;
        }

        if (!this.phone.trim() || this.phone.length !== 4) {
            this.error = 'Please enter the last 4 digits of your phone number.';
            return;
        }

        this.loading = true;
        try {
            const response = await window.axios.post('{{ url('forgot-password') }}', {
                kingschat_id: this.kingschatId,
                phone: this.phone
            });

            this.temporaryPassword = response.data.temporary_password;
            this.tempPasswordExpiry = response.data.expires_at;
            this.step = 2;
        } catch (e) {
            if (e.response?.status === 422) {
                const errors = e.response.data.errors;
                this.error = errors?.kingschat_id?.[0] || errors?.phone?.[0] || 'Verification failed.';
            } else if (e.response?.status === 404) {
                this.error = 'User not found.';
            } else if (e.response?.status === 401) {
                this.error = 'Phone verification failed.';
            } else {
                this.error = 'Something went wrong. Please try again.';
            }
        } finally {
            this.loading = false;
        }
    },

    copyToClipboard() {
        navigator.clipboard.writeText(this.temporaryPassword);
        alert('Password copied to clipboard!');
    },

    confirmPasswordCopied() {
        this.step = 3;
        this.error = '';
    },

    async resetPassword() {
        this.error = '';

        if (!this.temporaryPassword.trim()) {
            this.error = 'Please enter the temporary password.';
            return;
        }

        if (!this.newPassword.trim()) {
            this.error = 'Please enter a new password.';
            return;
        }

        if (this.newPassword !== this.newPasswordConfirmation) {
            this.error = 'Passwords do not match.';
            return;
        }

        this.loading = true;
        try {
            // First login with kingschat_id + temp password (we need to be authenticated to change password)
            const loginResponse = await window.axios.post('{{ route('login') }}', {
                kingschat_id: this.kingschatId,
                password: this.temporaryPassword
            });

            // Update CSRF token after session regeneration
            if (loginResponse.data.csrf_token) {
                document.querySelector('meta[name=&quot;csrf-token&quot;]').setAttribute('content', loginResponse.data.csrf_token);
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = loginResponse.data.csrf_token;
            }

            // Now change password
            await window.axios.post('{{ route('password.change') }}', {
                current_password: this.temporaryPassword,
                new_password: this.newPassword,
                new_password_confirmation: this.newPasswordConfirmation
            });

            // Redirect to login
            window.location.href = '{{ route('login') }}?message=Password reset successfully. Please login.';
        } catch (e) {
            if (e.response?.status === 422) {
                const errors = e.response.data.errors;
                this.error = errors?.temporary_password?.[0] || errors?.current_password?.[0] || errors?.new_password?.[0] || 'Failed to reset password.';
            } else {
                this.error = 'Something went wrong. Please try again.';
            }
            this.loading = false;
        }
    }
}">
    {{-- Step 1: Request password reset --}}
    <div x-show="step === 1" x-transition>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Forgot Password</h2>
        <p class="text-sm text-gray-500 mb-6">Enter your KingsChat ID and last 4 digits of your phone number.</p>

        {{-- Error message --}}
        <div x-show="error" x-transition x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
            <p class="text-sm text-red-700" x-text="error"></p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="kingschat_id" class="label">KingsChat ID</label>
                <input
                    type="text"
                    id="kingschat_id"
                    x-model="kingschatId"
                    class="input"
                    placeholder="Enter your KingsChat ID"
                    autofocus
                >
            </div>

            <div>
                <label for="phone" class="label">Last 4 Digits of Phone Number</label>
                <input
                    type="text"
                    id="phone"
                    x-model="phone"
                    @keydown.enter="requestReset()"
                    class="input"
                    placeholder="Enter last 4 digits"
                    maxlength="4"
                    pattern="[0-9]{4}"
                >
                <p class="text-xs text-gray-500 mt-1">
                    For security, please enter the last 4 digits of your registered phone number.
                </p>
            </div>

            <button
                @click="requestReset()"
                :disabled="loading"
                class="btn btn-primary w-full"
            >
                <span x-show="!loading">Request Password Reset</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Processing...
                </span>
            </button>

            <a href="{{ route('login') }}" class="block w-full text-center text-sm text-gray-500 hover:text-gray-700">
                &larr; Back to Login
            </a>
        </div>
    </div>

    {{-- Step 2: Temporary Password Display --}}
    <div x-show="step === 2" x-transition x-cloak>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Temporary Password</h2>

        <div class="bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4 mb-4">
            <p class="text-sm text-yellow-800 mb-3 font-medium">
                Please copy this password. You'll use it to set your new password.
            </p>
            <div class="flex items-center gap-3 bg-gray-900 rounded-lg px-4 py-4 border-2 border-gray-700">
                <span x-text="temporaryPassword" class="flex-1 font-mono text-2xl font-bold text-yellow-400 tracking-wider select-all"></span>
                <button
                    @click="copyToClipboard()"
                    type="button"
                    class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-3 py-2 rounded-md font-medium text-sm transition-colors"
                    title="Copy to clipboard"
                >
                    Copy
                </button>
            </div>
            <p class="text-xs text-yellow-800 mt-2 font-medium">
                ⏰ This password expires in 24 hours.
            </p>
        </div>

        <label class="flex items-center gap-2 mb-4 cursor-pointer">
            <input type="checkbox" x-model="passwordCopied" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
            <span class="text-sm text-gray-700">I have copied my temporary password</span>
        </label>

        <button
            @click="confirmPasswordCopied()"
            :disabled="!passwordCopied"
            class="btn btn-primary w-full"
            :class="{ 'opacity-50 cursor-not-allowed': !passwordCopied }"
        >
            Continue
        </button>
    </div>

    {{-- Step 3: Set New Password --}}
    <div x-show="step === 3" x-transition x-cloak>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Set New Password</h2>
        <p class="text-sm text-gray-500 mb-6">Create a secure password for your account.</p>

        {{-- Error message --}}
        <div x-show="error" x-transition x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
            <p class="text-sm text-red-700" x-text="error"></p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="temp_password" class="label">Temporary Password</label>
                <div class="relative">
                    <input
                        :type="showTempPassword ? 'text' : 'password'"
                        id="temp_password"
                        x-model="temporaryPassword"
                        class="input pr-10"
                        placeholder="Enter temporary password"
                        readonly
                    >
                    <button
                        type="button"
                        @click="showTempPassword = !showTempPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                    >
                        <svg x-show="!showTempPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg x-show="showTempPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label for="new_password" class="label">New Password</label>
                <div class="relative">
                    <input
                        :type="showNewPassword ? 'text' : 'password'"
                        id="new_password"
                        x-model="newPassword"
                        class="input pr-10"
                        placeholder="Enter new password"
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        @click="showNewPassword = !showNewPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                    >
                        <svg x-show="!showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg x-show="showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Min 8 characters, 1 uppercase, 1 lowercase, 1 number
                </p>
            </div>

            <div>
                <label for="new_password_confirmation" class="label">Confirm Password</label>
                <div class="relative">
                    <input
                        :type="showNewPasswordConfirmation ? 'text' : 'password'"
                        id="new_password_confirmation"
                        x-model="newPasswordConfirmation"
                        @keydown.enter="resetPassword()"
                        class="input pr-10"
                        placeholder="Confirm new password"
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        @click="showNewPasswordConfirmation = !showNewPasswordConfirmation"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700"
                    >
                        <svg x-show="!showNewPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg x-show="showNewPasswordConfirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <button
                @click="resetPassword()"
                :disabled="loading"
                class="btn btn-primary w-full"
            >
                <span x-show="!loading">Reset Password</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Resetting password...
                </span>
            </button>
        </div>
    </div>
</div>
@endsection
