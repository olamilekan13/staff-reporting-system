@extends('layouts.app')

@section('title', 'Create Department')
@section('page-title', 'Create Department')

@section('content')
    <div class="max-w-3xl">
        <form method="POST" action="{{ route('admin.departments.store') }}">
            @csrf

            <x-card>
                <div class="space-y-6">
                    {{-- Department Name --}}
                    <x-input
                        name="name"
                        label="Department Name"
                        placeholder="e.g., Human Resources"
                        :value="old('name')"
                        required
                    />

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Description
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Brief description of the department's responsibilities..."
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parent Department --}}
                    <x-select
                        name="parent_id"
                        label="Parent Department"
                        placeholder="None (Root Department)"
                        :selected="old('parent_id')"
                        :options="$parentDepartments->pluck('name', 'id')->toArray()"
                    />

                    {{-- Department Head --}}
                    <x-select
                        name="head_id"
                        label="Department Head"
                        placeholder="Select a user"
                        :selected="old('head_id')"
                        :options="$users->mapWithKeys(fn($user) => [$user->id => $user->full_name . ' (' . $user->email . ')'])->toArray()"
                    />

                    {{-- Status --}}
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}
                                class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                            >
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                    <x-button variant="secondary" :href="route('admin.departments.index')">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create Department
                    </x-button>
                </div>
            </x-card>
        </form>
    </div>
@endsection
