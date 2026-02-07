@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div x-data="{
    step: 1,
    loading: false,
    kingschatId: '',
    phone: '',
    remember: false,
    userName: '',
    maskedPhone: '',
    error: '',

    async verifyKingsChatId() {
        this.error = '';
        if (!this.kingschatId.trim()) {
            this.error = 'Please enter your KingsChat ID.';
            return;
        }

        this.loading = true;
        try {
            const response = await window.axios.post('{{ route('login.verify') }}', {
                kingschat_id: this.kingschatId
            });
            this.userName = response.data.user_name;
            this.maskedPhone = response.data.masked_phone;
            this.step = 2;
        } catch (e) {
            if (e.response?.status === 422) {
                const errors = e.response.data.errors;
                this.error = errors?.kingschat_id?.[0] || 'Verification failed.';
            } else {
                this.error = 'Something went wrong. Please try again.';
            }
        } finally {
            this.loading = false;
        }
    },

    async login() {
        this.error = '';
        if (!this.phone.trim()) {
            this.error = 'Please enter your phone number.';
            return;
        }

        this.loading = true;
        try {
            const response = await window.axios.post('{{ route('login') }}', {
                kingschat_id: this.kingschatId,
                phone: this.phone,
                remember: this.remember
            });

            if (response.data.redirect) {
                window.location.href = response.data.redirect;
            } else {
                window.location.href = '/';
            }
        } catch (e) {
            if (e.response?.status === 422) {
                const errors = e.response.data.errors;
                this.error = errors?.kingschat_id?.[0] || errors?.phone?.[0] || 'Login failed.';
            } else {
                this.error = 'Something went wrong. Please try again.';
            }
            this.loading = false;
        }
    },

    goBack() {
        this.step = 1;
        this.phone = '';
        this.error = '';
    }
}">
    {{-- Step 1: KingsChat ID --}}
    <div x-show="step === 1" x-transition>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Sign in</h2>
        <p class="text-sm text-gray-500 mb-6">Enter your KingsChat ID to continue.</p>

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
                    @keydown.enter="verifyKingsChatId()"
                    class="input"
                    placeholder="Enter your KingsChat ID"
                    autocomplete="username"
                    autofocus
                >
            </div>

            <button
                @click="verifyKingsChatId()"
                :disabled="loading"
                class="btn btn-primary w-full"
            >
                <span x-show="!loading">Continue</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Verifying...
                </span>
            </button>
        </div>
    </div>

    {{-- Step 2: Phone verification --}}
    <div x-show="step === 2" x-transition x-cloak>
        <h2 class="text-lg font-semibold text-gray-900 mb-1">
            Welcome, <span x-text="userName"></span>!
        </h2>
        <p class="text-sm text-gray-500 mb-6">
            Your phone number ending in <span class="font-medium text-gray-700" x-text="maskedPhone"></span>
        </p>

        {{-- Error message --}}
        <div x-show="error" x-transition x-cloak class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
            <p class="text-sm text-red-700" x-text="error"></p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="phone" class="label">Phone Number</label>
                <input
                    type="tel"
                    id="phone"
                    x-model="phone"
                    @keydown.enter="login()"
                    class="input"
                    placeholder="Enter your full phone number"
                    autocomplete="tel"
                >
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    id="remember"
                    x-model="remember"
                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                >
                <label for="remember" class="text-sm text-gray-600">Remember me</label>
            </div>

            <button
                @click="login()"
                :disabled="loading"
                class="btn btn-primary w-full"
            >
                <span x-show="!loading">Login</span>
                <span x-show="loading" class="flex items-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Logging in...
                </span>
            </button>

            <button
                @click="goBack()"
                type="button"
                class="w-full text-center text-sm text-gray-500 hover:text-gray-700"
            >
                &larr; Back
            </button>
        </div>
    </div>
</div>
@endsection
