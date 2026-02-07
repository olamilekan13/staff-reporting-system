@extends('layouts.app')

@section('title', 'My Proposals')
@section('page-title', 'My Proposals')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Manage your proposals</p>
        </div>
        @can('create', App\Models\Proposal::class)
            <x-button variant="primary" :href="route('staff.proposals.create')">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                New Proposal
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
            <form method="GET" action="{{ route('staff.proposals.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-input name="search" label="Search" placeholder="Search proposals..." :value="request('search')" />

                    <x-select name="status" label="Status" placeholder="All Statuses" :selected="request('status')"
                        :options="['pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('staff.proposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Proposals table --}}
    @if($proposals->isEmpty())
        <x-card>
            <x-empty-state title="No proposals found" description="Start by creating your first proposal.">
                @can('create', App\Models\Proposal::class)
                    <x-slot:action>
                        <x-button variant="primary" size="sm" :href="route('staff.proposals.create')">Create Proposal</x-button>
                    </x-slot:action>
                @endcan
            </x-empty-state>
        </x-card>
    @else
        <x-data-table :headers="['title' => 'Title', 'status' => 'Status', 'date' => 'Date', 'actions' => '']">
            @foreach($proposals as $proposal)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @if($proposal->file_name)
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            @endif
                            <a href="{{ route('staff.proposals.show', $proposal) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                {{ Str::limit($proposal->title, 40) }}
                            </a>
                        </div>
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $badgeType = match($proposal->status) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'under_review' => 'primary',
                                'pending' => 'warning',
                                default => 'info',
                            };
                        @endphp
                        <x-badge :type="$badgeType">{{ ucfirst(str_replace('_', ' ', $proposal->status)) }}</x-badge>
                    </td>
                    <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $proposal->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-3">
                        <x-dropdown>
                            <x-slot:trigger>
                                <button class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                    </svg>
                                </button>
                            </x-slot:trigger>

                            <a href="{{ route('staff.proposals.show', $proposal) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
                            @can('update', $proposal)
                                <a href="{{ route('staff.proposals.edit', $proposal) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                            @endcan
                            @can('delete', $proposal)
                                <form method="POST" action="{{ route('staff.proposals.destroy', $proposal) }}" onsubmit="return confirm('Are you sure you want to delete this proposal?')">
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
                {{ $proposals->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
