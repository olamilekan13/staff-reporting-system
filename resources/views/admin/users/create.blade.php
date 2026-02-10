@extends('layouts.app')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('content')
        {{-- Back link --}}
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Users
        </a>

        <x-card title="New User">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="space-y-5">
                    <x-input
                        name="kingschat_id"
                        label="KingsChat ID"
                        :value="old('kingschat_id')"
                        :error="$errors->first('kingschat_id')"
                        required
                        placeholder="e.g., john.doe" />

                    <x-select
                        name="title"
                        label="Title"
                        placeholder="Select Title"
                        :selected="old('title')"
                        :error="$errors->first('title')"
                        required
                        :options="[
                            'Pastor' => 'Pastor',
                            'Deacon' => 'Deacon',
                            'Deaconess' => 'Deaconess',
                            'Brother' => 'Brother',
                            'Sister' => 'Sister',
                        ]" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input
                            name="first_name"
                            label="First Name"
                            :value="old('first_name')"
                            :error="$errors->first('first_name')"
                            required
                            placeholder="John" />

                        <x-input
                            name="last_name"
                            label="Last Name"
                            :value="old('last_name')"
                            :error="$errors->first('last_name')"
                            required
                            placeholder="Doe" />
                    </div>

                    <x-input
                        name="email"
                        label="Email"
                        type="email"
                        :value="old('email')"
                        :error="$errors->first('email')"
                        placeholder="john.doe@example.com (optional)" />

                    <x-input
                        name="phone"
                        label="Phone Number"
                        type="tel"
                        :value="old('phone')"
                        :error="$errors->first('phone')"
                        required
                        placeholder="e.g., +234801234567" />

                    <x-select
                        name="department_id"
                        label="Department"
                        placeholder="Select Department (Optional)"
                        :selected="old('department_id')"
                        :error="$errors->first('department_id')"
                        :options="$departments->pluck('name', 'id')->toArray()" />

                    <div>
                        <x-select
                            name="role"
                            label="Role"
                            :selected="old('role', 'staff')"
                            :error="$errors->first('role')"
                            required
                            :options="$roles" />
                        <p class="mt-1.5 text-xs text-gray-500">
                            Select the user's role to define their permissions in the system.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Create User</x-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>
@endsection
