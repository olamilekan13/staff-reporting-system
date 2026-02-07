@extends('layouts.app')

@section('title', 'All Proposals')
@section('page-title', 'All Proposals')

@section('content')
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">View and manage all proposals across the organization</p>
        </div>
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
            <form method="GET" action="{{ route('admin.proposals.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <x-input name="search" label="Search" placeholder="Search proposals..." :value="request('search')" />

                    <x-select name="status" label="Status" placeholder="All Statuses" :selected="request('status')"
                        :options="['pending' => 'Pending', 'under_review' => 'Under Review', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('admin.proposals.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Proposals table --}}
    @if($proposals->isEmpty())
        <x-card>
            <x-empty-state title="No proposals found" description="No proposals match your current filters." />
        </x-card>
    @else
        <x-data-table :headers="['author' => 'Author', 'title' => 'Title', 'status' => 'Status', 'date' => 'Date', 'actions' => '']">
            @foreach($proposals as $proposal)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 text-gray-900 font-medium whitespace-nowrap">{{ $proposal->user->full_name }}</td>
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            @if($proposal->file_name)
                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                            @endif
                            <a href="{{ route('admin.proposals.show', $proposal) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                {{ Str::limit($proposal->title, 30) }}
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
                        <a href="{{ route('admin.proposals.show', $proposal) }}" class="text-sm text-primary-600 hover:text-primary-700">View</a>
                    </td>
                </tr>
            @endforeach

            <x-slot:pagination>
                {{ $proposals->withQueryString()->links() }}
            </x-slot:pagination>
        </x-data-table>
    @endif
@endsection
