@extends('layouts.app')

@section('title', 'Manage Announcements')
@section('page-title', 'Manage Announcements')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Create and manage announcements for your organization</p>
        </div>
        @can('create', App\Models\Announcement::class)
            <x-button variant="primary" :href="route('admin.announcements.create')">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Announcement
            </x-button>
        @endcan
    </div>

    {{-- Filters --}}
    <div x-data="{ showFilters: {{ request()->hasAny(['status', 'search']) ? 'true' : 'false' }} }" class="mb-6">
        <button @click="showFilters = !showFilters" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
        </button>

        <div x-show="showFilters" x-transition class="card px-6 py-4">
            <form method="GET" action="{{ route('admin.announcements.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-input name="search" label="Search" placeholder="Search by title..." :value="request('search')" />

                    <x-select name="status" label="Status" placeholder="All Statuses" :selected="request('status')"
                        :options="['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired']" />
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Announcements table --}}
    @if($announcements->isEmpty())
        <x-card>
            <x-empty-state title="No announcements found" description="No announcements match your current filters.">
                <x-slot:action>
                    @can('create', App\Models\Announcement::class)
                        <x-button variant="primary" size="sm" :href="route('admin.announcements.create')">Create Announcement</x-button>
                    @endcan
                </x-slot:action>
            </x-empty-state>
        </x-card>
    @else
        <x-data-table :headers="['title' => 'Title', 'priority' => 'Priority', 'target' => 'Target', 'status' => 'Status', 'created_by' => 'Created By', 'date' => 'Date', 'actions' => '']">
            @foreach($announcements as $announcement)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-2">
                            @if($announcement->is_pinned)
                                <svg class="w-4 h-4 text-primary-500 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z"/>
                                </svg>
                            @endif
                            <a href="{{ route('announcements.show', $announcement) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                {{ Str::limit($announcement->title, 40) }}
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        <span class="badge {{ $announcement->getPriorityBadgeClass() }}">{{ ucfirst($announcement->priority) }}</span>
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        @if($announcement->target_type === 'all')
                            All Users
                        @elseif($announcement->target_type === 'departments')
                            {{ $announcement->departments->count() }} {{ Str::plural('Department', $announcement->departments->count()) }}
                        @elseif($announcement->target_type === 'users')
                            {{ $announcement->users->count() }} {{ Str::plural('User', $announcement->users->count()) }}
                        @else
                            {{ ucfirst($announcement->target_type) }}
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        @if($announcement->isScheduled())
                            <x-badge type="info">Scheduled</x-badge>
                        @elseif($announcement->isExpired())
                            <x-badge type="danger">Expired</x-badge>
                        @else
                            <x-badge type="success">Active</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $announcement->creator?->full_name }}</td>
                    <td class="px-6 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $announcement->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-3">
                        <x-dropdown>
                            <x-slot:trigger>
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                    </svg>
                                </button>
                            </x-slot:trigger>

                            <a href="{{ route('announcements.show', $announcement) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
                            @can('update', $announcement)
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                            @endcan
                            @can('delete', $announcement)
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Are you sure you want to delete this announcement?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Delete</button>
                                </form>
                            @endcan
                        </x-dropdown>
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $announcements->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
