@extends('layouts.app')

@section('title', $department->name)
@section('page-title', $department->name)

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <x-badge :type="$department->is_active ? 'success' : 'danger'">
                {{ $department->is_active ? 'Active' : 'Inactive' }}
            </x-badge>
        </div>
        <div class="flex items-center gap-2">
            <x-button variant="secondary" size="sm" :href="route('admin.departments.index')">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Back to Departments
            </x-button>
            <x-button variant="primary" size="sm" :href="route('admin.departments.edit', $department)">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
                Edit Department
            </x-button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Department Details --}}
            <x-card title="Department Information">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                        <p class="text-base text-gray-900">{{ $department->name }}</p>
                    </div>

                    @if($department->description)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Description</label>
                            <p class="text-base text-gray-900">{{ $department->description }}</p>
                        </div>
                    @endif

                    @if($department->parent)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Parent Department</label>
                            <a href="{{ route('admin.departments.show', $department->parent) }}" class="text-base text-primary-600 hover:text-primary-700">
                                {{ $department->parent->name }}
                            </a>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Hierarchy</label>
                        <p class="text-base text-gray-900">{{ implode(' > ', $department->getFullHierarchy()) }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Sub-Departments --}}
            @if($department->children->isNotEmpty())
                <x-card title="Sub-Departments ({{ $department->children->count() }})">
                    <div class="divide-y divide-gray-200">
                        @foreach($department->children as $child)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <a href="{{ route('admin.departments.show', $child) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                        {{ $child->name }}
                                    </a>
                                    @if($child->description)
                                        <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($child->description, 60) }}</p>
                                    @endif
                                </div>
                                <x-badge :type="$child->is_active ? 'success' : 'danger'" size="sm">
                                    {{ $child->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            {{-- Staff Members --}}
            <x-card title="Staff Members ({{ $department->users->count() }})">
                @if($department->users->isEmpty())
                    <x-empty-state
                        title="No staff members"
                        description="This department doesn't have any staff members yet."
                    />
                @else
                    <div class="divide-y divide-gray-200">
                        @foreach($department->users as $user)
                            <div class="py-3 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-primary-700">
                                                {{ substr($user->full_name, 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                            {{ $user->full_name }}
                                        </a>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($user->id === $department->head_id)
                                        <x-badge type="primary" size="sm">Head</x-badge>
                                    @endif
                                    <x-badge :type="$user->is_active ? 'success' : 'danger'" size="sm">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Department Head --}}
            <x-card title="Department Head">
                @if($department->head)
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-base font-medium text-primary-700">
                                    {{ substr($department->head->full_name, 0, 2) }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.users.show', $department->head) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                {{ $department->head->full_name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $department->head->email }}</p>
                            @if($department->head->phone)
                                <p class="text-xs text-gray-500 mt-0.5">{{ $department->head->masked_phone }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No head assigned</p>
                @endif
            </x-card>

            {{-- Statistics --}}
            <x-card title="Statistics">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Staff</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $department->users->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Active Staff</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $department->users->where('is_active', true)->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Sub-Departments</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $department->children->count() }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Metadata --}}
            <x-card title="Metadata">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Created</label>
                        <p class="text-sm text-gray-900">{{ $department->created_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $department->created_at->diffForHumans() }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Last Updated</label>
                        <p class="text-sm text-gray-900">{{ $department->updated_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-500">{{ $department->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
@endsection
