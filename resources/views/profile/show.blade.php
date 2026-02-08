@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
    <div class="max-w-3xl">
        <div class="grid grid-cols-1 gap-6">
            {{-- Profile Information --}}
            <x-card title="Profile Information">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <x-input
                                name="first_name"
                                label="First Name"
                                :value="old('first_name', $user->first_name)"
                                :error="$errors->first('first_name')"
                                required
                            />

                            <x-input
                                name="last_name"
                                label="Last Name"
                                :value="old('last_name', $user->last_name)"
                                :error="$errors->first('last_name')"
                                required
                            />
                        </div>

                        <x-input
                            name="email"
                            type="email"
                            label="Email Address"
                            :value="old('email', $user->email)"
                            :error="$errors->first('email')"
                            required
                        />

                        <x-input
                            name="phone"
                            type="tel"
                            label="Phone Number"
                            :value="old('phone', $user->phone)"
                            :error="$errors->first('phone')"
                            required
                        />

                        <div>
                            <label class="label">KingsChat ID</label>
                            <input type="text" value="{{ $user->kingschat_id }}" class="input bg-gray-50" disabled readonly>
                            <p class="mt-1.5 text-xs text-gray-500">Your KingsChat ID cannot be changed.</p>
                        </div>

                        @if($user->department)
                            <div>
                                <label class="label">Department</label>
                                <input type="text" value="{{ $user->department->name }}" class="input bg-gray-50" disabled readonly>
                            </div>
                        @endif

                        <div>
                            <label class="label">Role</label>
                            <div class="flex flex-wrap gap-2 mt-1">
                                @foreach($user->roles as $role)
                                    <x-badge type="primary">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</x-badge>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                        <x-button type="submit" variant="primary">Update Profile</x-button>
                        <x-button variant="secondary" :href="request()->header('referer') ?? route(auth()->user()->getDefaultRoute())">
                            Cancel
                        </x-button>
                    </div>
                </form>
            </x-card>

            {{-- Account Information --}}
            <x-card title="Account Information">
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Account Status</p>
                            <p class="text-xs text-gray-500 mt-0.5">Your account is currently {{ $user->is_active ? 'active' : 'inactive' }}</p>
                        </div>
                        <x-badge :type="$user->is_active ? 'success' : 'danger'">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </x-badge>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Member Since</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $user->created_at->format('F d, Y') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Last Updated</p>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $user->updated_at->format('F d, Y') }} at {{ $user->updated_at->format('h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
@endsection
