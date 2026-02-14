@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Welcome banner --}}
    <div class="card px-6 py-5 mb-8">
        <h2 class="text-xl font-bold text-gray-900">Welcome back, {{ Auth::user()->full_name }}!</h2>
        @if(Auth::user()->department)
            <p class="text-sm text-gray-500 mt-1">{{ Auth::user()->department->name }}</p>
        @endif
    </div>

    {{-- Quick actions --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
        <x-button variant="primary" size="sm" :href="route('staff.reports.create', ['category' => 'daily'])">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Daily Report
        </x-button>
        <x-button variant="primary" size="sm" :href="route('staff.reports.create', ['category' => 'weekly'])">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Weekly Report
        </x-button>
        <x-button variant="primary" size="sm" :href="route('staff.reports.create', ['category' => 'monthly'])">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Monthly Report
        </x-button>
        <x-button variant="secondary" size="sm" :href="route('staff.proposals.create')">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
            </svg>
            Create Proposal
        </x-button>
    </div>

    {{-- Report summary stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <x-stat-card title="Draft" :value="$reportCounts->get('draft', 0)">
            <x-slot:icon>
                <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Submitted" :value="$reportCounts->get('submitted', 0)">
            <x-slot:icon>
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Approved" :value="$reportCounts->get('approved', 0)">
            <x-slot:icon>
                <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>

        <x-stat-card title="Rejected" :value="$reportCounts->get('rejected', 0)">
            <x-slot:icon>
                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    {{-- Two-column layout: Reports + Sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent Reports (2/3) --}}
        <div class="lg:col-span-2">
            <x-card title="Recent Reports">
                <x-slot:actions>
                    <a href="{{ route('staff.reports.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                </x-slot:actions>

                @if($recentReports->isEmpty())
                    <x-empty-state title="No reports yet" description="Start by submitting your first report." />
                @else
                    <div class="-mx-6 -my-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentReports as $report)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3">
                                            <a href="{{ route('staff.reports.show', $report) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                                {{ Str::limit($report->title, 35) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 text-gray-500 capitalize">{{ $report->report_category }}</td>
                                        <td class="px-6 py-3">
                                            @php
                                                $badgeType = match($report->status) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'submitted', 'reviewed' => 'warning',
                                                    default => 'info',
                                                };
                                            @endphp
                                            <x-badge :type="$badgeType">{{ ucfirst($report->status) }}</x-badge>
                                        </td>
                                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $report->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        </div>

        {{-- Sidebar (1/3) --}}
        <div class="space-y-6">
            {{-- Announcements --}}
            <x-card title="Announcements">
                <x-slot:actions>
                    <a href="{{ route('announcements.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                </x-slot:actions>

                @if($announcements->isEmpty())
                    <x-empty-state title="No announcements" description="You're all caught up." />
                @else
                    <div class="-mx-6 -my-4">
                        <ul class="divide-y divide-gray-100">
                            @foreach($announcements as $announcement)
                                <li class="px-6 py-3">
                                    <div class="flex items-start gap-2">
                                        @if($announcement->is_pinned)
                                            <svg class="w-4 h-4 text-yellow-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                            </svg>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <a href="{{ route('announcements.show', $announcement) }}" class="text-sm font-medium text-gray-900 hover:text-primary-600">
                                                {{ Str::limit($announcement->title, 40) }}
                                            </a>
                                            <div class="flex items-center gap-2 mt-1">
                                                @php
                                                    $priorityType = match($announcement->priority) {
                                                        'urgent' => 'danger',
                                                        'high' => 'warning',
                                                        'medium' => 'primary',
                                                        default => 'info',
                                                    };
                                                @endphp
                                                <x-badge :type="$priorityType">{{ ucfirst($announcement->priority) }}</x-badge>
                                                <span class="text-xs text-gray-400">{{ $announcement->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-card>

            {{-- Report Links --}}
            @if($reportLinks->isNotEmpty())
                <x-card title="My Report Links" class="mb-6">
                    <div class="-mx-6 -my-4">
                        <ul class="divide-y divide-gray-100">
                            @foreach($reportLinks as $link)
                                <li class="px-6 py-3">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 text-sm group">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-600 transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span class="font-medium text-gray-700 group-hover:text-primary-600 transition-colors truncate">
                                            Report {{ $loop->iteration }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-card>
            @endif

            {{-- Notifications --}}
            <x-card title="Notifications">
                <x-slot:actions>
                    @if($unreadNotificationCount > 0)
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">{{ $unreadNotificationCount }}</span>
                    @endif
                    <a href="{{ route('notifications.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                </x-slot:actions>

                @if($latestNotifications->isEmpty())
                    <x-empty-state title="No new notifications" description="You're all caught up." />
                @else
                    <div class="-mx-6 -my-4">
                        <ul class="divide-y divide-gray-100">
                            @foreach($latestNotifications as $notification)
                                <li class="px-6 py-3">
                                    @php
                                        $colorClass = match($notification->getColor()) {
                                            'blue' => 'bg-blue-500',
                                            'purple' => 'bg-purple-500',
                                            'green' => 'bg-green-500',
                                            'yellow' => 'bg-yellow-500',
                                            default => 'bg-gray-400',
                                        };
                                    @endphp
                                    <div class="flex items-start gap-3">
                                        <div class="w-2 h-2 rounded-full {{ $colorClass }} mt-1.5 shrink-0"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900">{{ $notification->title }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $notification->message }}</p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
@endsection
