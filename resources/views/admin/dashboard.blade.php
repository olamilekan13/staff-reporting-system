@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Stats row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @if(!$isHeadOfOperations)
            <x-stat-card title="Total Users" :value="$stats['total_users']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </x-slot:icon>
                <x-slot:trend>{{ $stats['active_users'] }} active</x-slot:trend>
            </x-stat-card>
        @endif

        <x-stat-card title="Total Reports" :value="$stats['total_reports']">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
            </x-slot:icon>
            <x-slot:trend>{{ $stats['pending_reports'] }} pending</x-slot:trend>
        </x-stat-card>

        @if(!$isHeadOfOperations)
            <x-stat-card title="Pending Proposals" :value="$stats['pending_proposals']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        @else
            <x-stat-card title="Pending Reports" :value="$stats['pending_reports']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        @endif

        <x-stat-card title="Active Announcements" :value="$stats['active_announcements']">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46" />
                </svg>
            </x-slot:icon>
        </x-stat-card>
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Recent Reports (2/3 width) --}}
        <div class="lg:col-span-2">
            <x-card title="Recent Reports">
                <x-slot:actions>
                    <a href="{{ route('admin.reports.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                </x-slot:actions>

                @if($recentReports->isEmpty())
                    <x-empty-state title="No reports yet" description="Reports will appear here once staff submit them." />
                @else
                    <div class="-mx-6 -my-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentReports as $report)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3">
                                            <a href="{{ route('admin.reports.show', $report) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                                {{ Str::limit($report->title, 35) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3 text-gray-500">{{ $report->user->full_name }}</td>
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

        {{-- Recent Activity (1/3 width) - Only for Admin --}}
        @if(!$isHeadOfOperations)
            <div>
                <x-card title="Recent Activity">
                    <x-slot:actions>
                        <a href="{{ route('admin.activity-logs.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                    </x-slot:actions>

                    @if($recentActivity->isEmpty())
                        <x-empty-state title="No activity yet" description="Activity logs will appear here." />
                    @else
                        <div class="-mx-6 -my-4">
                            <ul class="divide-y divide-gray-100">
                                @foreach($recentActivity as $log)
                                    <li class="px-6 py-3 flex items-start gap-3">
                                        {{-- Action color dot --}}
                                        @php
                                            $dotColor = match($log->getActionColor()) {
                                                'green' => 'bg-green-500',
                                                'blue' => 'bg-blue-500',
                                                'yellow' => 'bg-yellow-500',
                                                'red' => 'bg-red-500',
                                                'purple' => 'bg-purple-500',
                                                'indigo' => 'bg-indigo-500',
                                                default => 'bg-gray-400',
                                            };
                                        @endphp
                                        <div class="w-2 h-2 rounded-full {{ $dotColor }} mt-1.5 shrink-0"></div>

                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                <span class="font-medium">{{ $log->user?->full_name ?? 'System' }}</span>
                                                {{ strtolower($log->getActionLabel()) }}
                                                @if($log->getModelName())
                                                    <span class="text-gray-500">{{ $log->getModelName() }}</span>
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-card>
            </div>
        @else
            {{-- Recent Notifications (1/3 width) - For Head of Operations --}}
            <div>
                <x-card title="Recent Notifications">
                    <x-slot:actions>
                        <a href="{{ route('notifications.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                    </x-slot:actions>

                    @if($recentNotifications->isEmpty())
                        <x-empty-state title="No notifications" description="You're all caught up!" />
                    @else
                        <div class="-mx-6 -my-4">
                            <ul class="divide-y divide-gray-100">
                                @foreach($recentNotifications as $notification)
                                    <li class="px-6 py-3">
                                        <a href="{{ route('notifications.view', $notification) }}" class="flex items-start gap-3 hover:bg-gray-50 -mx-6 -my-3 px-6 py-3 transition-colors">
                                            {{-- Unread indicator --}}
                                            @if(!$notification->read_at)
                                                <div class="w-2 h-2 rounded-full bg-primary-500 mt-1.5 shrink-0"></div>
                                            @else
                                                <div class="w-2 h-2 mt-1.5 shrink-0"></div>
                                            @endif

                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900 font-medium' }}">
                                                    {{ $notification->data['title'] ?? 'New notification' }}
                                                </p>
                                                @if(isset($notification->data['message']))
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ Str::limit($notification->data['message'], 60) }}</p>
                                                @endif
                                                <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </x-card>
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <x-card title="Quick Actions">
        <div class="flex flex-wrap gap-3">
            @if(!$isHeadOfOperations)
                <x-button variant="primary" :href="route('admin.users.create')">
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Add User
                </x-button>
            @endif

            <x-button variant="primary" :href="route('admin.announcements.create')">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create Announcement
            </x-button>
        </div>
    </x-card>
@endsection
