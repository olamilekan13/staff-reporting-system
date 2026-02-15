@extends('layouts.guest')

@section('title', 'Setup Password')

@section('content')
<div x-data="{
    loading: false,
    temporaryPassword: '',
    newPassword: '',
    newPasswordConfirmation: '',
    error: '',
    showTempPassword: false,
    showNewPassword: false,
    showNewPasswordConfirmation: false,

    async setupPassword() {
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
            await window.axios.post('{{ route('password.setup') }}', {
                temporary_password: this.temporaryPassword,
                new_password: this.newPassword,
                new_password_confirmation: this.newPasswordConfirmation
            });

            // Redirect to login
            window.location.href = '{{ route('login') }}?message=Password set successfully. Please login.';
        } catch (e) {
            if (e.response?.status === 422) {
                const errors = e.response.data.errors;
                this.error = errors?.temporary_password?.[0] || errors?.new_password?.[0] || 'Failed to set password.';
            } else {
                this.error = 'Something went wrong. Please try again.';
            }
            this.loading = false;
        }
    }
}">
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Setup Your Password</h2>
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
                    autofocus
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
                    @keydown.enter="setupPassword()"
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
            @click="setupPassword()"
            :disabled="loading"
            class="btn btn-primary w-full"
        >
            <span x-show="!loading">Setup Password</span>
            <span x-show="loading" class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Setting password...
            </span>
        </button>
    </div>
</div>
@endsection
