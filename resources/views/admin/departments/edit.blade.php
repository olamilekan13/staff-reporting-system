@extends('layouts.app')

@section('title', 'Edit Department')
@section('page-title', 'Edit Department')

@section('content')
    <div class="max-w-3xl">
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
            @csrf
            @method('PUT')

            <x-card>
                <div class="space-y-6">
                    {{-- Department Name --}}
                    <x-input
                        name="name"
                        label="Department Name"
                        placeholder="e.g., Human Resources"
                        :value="old('name', $department->name)"
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
                        >{{ old('description', $department->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Parent Department --}}
                    <x-select
                        name="parent_id"
                        label="Parent Department"
                        placeholder="None (Root Department)"
                        :selected="old('parent_id', $department->parent_id)"
                        :options="$parentDepartments->pluck('name', 'id')->toArray()"
                    />

                    {{-- Department Head --}}
                    <x-select
                        name="head_id"
                        label="Department Head"
                        placeholder="Select a user"
                        :selected="old('head_id', $department->head_id)"
                        :options="$users->mapWithKeys(fn($user) => [$user->id => $user->full_name . ' (' . $user->email . ')'])->toArray()"
                    />

                    {{-- Status --}}
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ old('is_active', $department->is_active) ? 'checked' : '' }}
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
                <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                    <form method="POST" action="{{ route('admin.departments.destroy', $department) }}" onsubmit="return confirm('Are you sure you want to delete this department? This action cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Delete Department
                        </x-button>
                    </form>

                    <div class="flex items-center gap-3">
                        <x-button variant="secondary" :href="route('admin.departments.index')">
                            Cancel
                        </x-button>
                        <x-button type="submit" variant="primary">
                            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Update Department
                        </x-button>
                    </div>
                </div>
            </x-card>
        </form>
    </div>
@endsection
