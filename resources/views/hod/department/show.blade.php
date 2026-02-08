@extends('layouts.app')

@section('title', 'My Department')
@section('page-title', 'My Department')

@section('content')
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
                            <p class="text-base text-gray-900">{{ $department->parent->name }}</p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Hierarchy</label>
                        <p class="text-base text-gray-900">{{ implode(' > ', $department->getFullHierarchy()) }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                        <x-badge :type="$department->is_active ? 'success' : 'danger'">
                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                        </x-badge>
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
                                    <p class="text-sm font-medium text-gray-900">{{ $child->name }}</p>
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
                                                {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $user->full_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($department->head_id === $user->id)
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
            {{-- Quick Stats --}}
            <x-card title="Quick Stats">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total Staff</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $department->users->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Active Staff</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $department->users->where('is_active', true)->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Sub-Departments</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $department->children->count() }}</span>
                    </div>
                </div>
            </x-card>

            {{-- Department Head --}}
            @if($department->head)
                <x-card title="Department Head">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-base font-medium text-primary-700">
                                    {{ substr($department->head->first_name, 0, 1) }}{{ substr($department->head->last_name, 0, 1) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $department->head->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $department->head->email }}</p>
                            <p class="text-xs text-gray-500">{{ $department->head->phone }}</p>
                        </div>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
@endsection
