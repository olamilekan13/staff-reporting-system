@extends('layouts.app')

@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
    {{-- Filters --}}
    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.activity-logs.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="action" class="label">Action</label>
                <select name="action" id="action" class="input">
                    <option value="">All Actions</option>
                    <option value="login" {{ request('action') === 'login' ? 'selected' : '' }}>Login</option>
                    <option value="logout" {{ request('action') === 'logout' ? 'selected' : '' }}>Logout</option>
                    <option value="create" {{ request('action') === 'create' ? 'selected' : '' }}>Create</option>
                    <option value="update" {{ request('action') === 'update' ? 'selected' : '' }}>Update</option>
                    <option value="delete" {{ request('action') === 'delete' ? 'selected' : '' }}>Delete</option>
                    <option value="view" {{ request('action') === 'view' ? 'selected' : '' }}>View</option>
                    <option value="download" {{ request('action') === 'download' ? 'selected' : '' }}>Download</option>
                    <option value="upload" {{ request('action') === 'upload' ? 'selected' : '' }}>Upload</option>
                </select>
            </div>

            <div>
                <label for="from_date" class="label">From Date</label>
                <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}" class="input">
            </div>

            <div>
                <label for="to_date" class="label">To Date</label>
                <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}" class="input">
            </div>

            <div class="flex items-end gap-2">
                <x-button type="submit" variant="primary" class="flex-1">Filter</x-button>
                <x-button variant="secondary" :href="route('admin.activity-logs.index')">Reset</x-button>
            </div>
        </form>
    </x-card>

    {{-- Activity Logs Table --}}
    <x-card>
        @if($logs->isEmpty())
            <x-empty-state
                title="No activity logs found"
                description="No activity logs match your current filters."
            />
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Model</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $log->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('h:i A') }}</div>
                                </td>
                                <td>
                                    @if($log->user)
                                        <div class="text-sm font-medium text-gray-900">{{ $log->user->full_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-sm text-gray-400">System</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-shrink-0">
                                            @php
                                                $colorClass = match($log->getActionColor()) {
                                                    'green' => 'text-green-500',
                                                    'red' => 'text-red-500',
                                                    'blue' => 'text-blue-500',
                                                    'yellow' => 'text-yellow-500',
                                                    'purple' => 'text-purple-500',
                                                    'indigo' => 'text-indigo-500',
                                                    default => 'text-gray-500',
                                                };
                                            @endphp
                                            <svg class="w-5 h-5 {{ $colorClass }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                @if($log->action === 'login')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                                                @elseif($log->action === 'logout')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                                                @elseif($log->action === 'create')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                @elseif($log->action === 'update')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                @elseif($log->action === 'delete')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                                @endif
                                            </svg>
                                        </div>
                                        <span class="text-sm text-gray-900">{{ $log->getActionLabel() }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($log->model_type)
                                        <div class="text-sm text-gray-900">{{ $log->getModelName() }}</div>
                                        @if($log->model_id)
                                            <div class="text-xs text-gray-500">ID: {{ $log->model_id }}</div>
                                        @endif
                                    @else
                                        <span class="text-sm text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="text-sm text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
@endsection
