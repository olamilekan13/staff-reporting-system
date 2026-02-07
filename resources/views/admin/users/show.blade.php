@extends('layouts.app')

@section('title', $user->full_name)
@section('page-title', 'View User')

@section('content')
    {{-- Back link --}}
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
        Back to Users
    </a>

    {{-- User Details Card --}}
    <x-card>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $user->full_name }}</h2>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    @foreach($user->roles as $role)
                        @php
                            $roleBadgeType = match($role->name) {
                                'super_admin' => 'danger',
                                'admin' => 'warning',
                                'head_of_operations' => 'info',
                                'hod' => 'primary',
                                'staff' => 'success',
                                default => 'secondary',
                            };
                        @endphp
                        <x-badge :type="$roleBadgeType">{{ ucwords(str_replace('_', ' ', $role->name)) }}</x-badge>
                    @endforeach
                    <x-badge :type="$user->is_active ? 'success' : 'danger'">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </x-badge>
                </div>
                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm text-gray-500">
                    <span>KingsChat ID: <span class="font-medium text-gray-700">{{ $user->kingschat_id }}</span></span>
                    @if($user->last_login_at)
                        <span>&middot; Last login {{ $user->last_login_at->diffForHumans() }}</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                @can('update', $user)
                    <x-button variant="secondary" size="sm" :href="route('admin.users.edit', $user)">
                        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                        </svg>
                        Edit
                    </x-button>
                @endcan
                @can('activate', $user)
                    @if(auth()->id() !== $user->id)
                        @if($user->is_active)
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to deactivate this user?')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" size="sm">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    Deactivate
                                </x-button>
                            </form>
                        @endif
                    @endif
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 mt-6 pt-6 border-t border-gray-100">
            <div>
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->email ?? 'Not provided' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->phone ? $user->masked_phone : 'Not provided' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Department</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    @if($user->department)
                        <a href="{{ route('admin.departments.show', $user->department) }}" class="text-primary-600 hover:text-primary-800">
                            {{ $user->department->name }}
                        </a>
                    @else
                        Not assigned
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Last Login</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ $user->last_login_at ? $user->last_login_at->format('M d, Y \a\t g:i A') : 'Never' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('M d, Y') }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                <dd class="mt-1">
                    <x-badge :type="$user->is_active ? 'success' : 'danger'">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </x-badge>
                </dd>
            </div>
        </div>
    </x-card>

    {{-- Activity Summary --}}
    @if(isset($user->activity_summary))
        <x-card title="Activity Summary" class="mt-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $user->activity_summary['total_reports'] }}</div>
                            <div class="text-xs font-medium text-gray-600">Total Reports</div>
                        </div>
                    </div>
                </div>

                <div class="bg-yellow-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $user->activity_summary['submitted_reports'] }}</div>
                            <div class="text-xs font-medium text-gray-600">Submitted</div>
                        </div>
                    </div>
                </div>

                <div class="bg-green-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $user->activity_summary['approved_reports'] }}</div>
                            <div class="text-xs font-medium text-gray-600">Approved</div>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $user->activity_summary['total_proposals'] }}</div>
                            <div class="text-xs font-medium text-gray-600">Proposals</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900">{{ $user->activity_summary['total_comments'] }}</div>
                            <div class="text-xs font-medium text-gray-600">Comments</div>
                        </div>
                    </div>
                </div>
            </div>
        </x-card>
    @endif

    {{-- Recent Reports --}}
    <x-card title="Recent Reports" class="mt-6">
        @php
            $recentReports = $user->reports()->latest()->limit(5)->get();
        @endphp

        @if($recentReports->isEmpty())
            <x-empty-state title="No reports yet" description="This user hasn't created any reports." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Title</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Category</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentReports as $report)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.reports.show', $report) }}" class="text-primary-600 hover:text-primary-800 font-medium">
                                        {{ Str::limit($report->title, 50) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600 capitalize">{{ $report->report_category }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusBadge = match($report->status) {
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'submitted', 'reviewed' => 'warning',
                                            default => 'info',
                                        };
                                    @endphp
                                    <x-badge :type="$statusBadge">{{ ucfirst($report->status) }}</x-badge>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $report->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($user->reports()->count() > 5)
                <div class="mt-4 text-center">
                    <a href="{{ route('admin.reports.index', ['search' => $user->kingschat_id]) }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium">
                        View All Reports ({{ $user->reports()->count() }}) →
                    </a>
                </div>
            @endif
        @endif
    </x-card>

    {{-- Recent Activity --}}
    <x-card title="Recent Activity" class="mt-6">
        @php
            $recentActivity = $user->activityLogs()->with('subject')->latest()->limit(10)->get();
        @endphp

        @if($recentActivity->isEmpty())
            <x-empty-state title="No recent activity" description="No activity logs found for this user." />
        @else
            <div class="flow-root">
                <ul class="-mb-8">
                    @foreach($recentActivity as $index => $activity)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full {{ $activity->getActionColor() }} flex items-center justify-center ring-8 ring-white">
                                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $activity->getActionIcon() }}" />
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-900">
                                                {{ $activity->getActionLabel() }}
                                                @if($activity->subject)
                                                    <span class="font-medium text-gray-700">{{ $activity->getModelName() }}</span>
                                                @endif
                                            </p>
                                            @if($activity->ip_address)
                                                <p class="text-xs text-gray-500 mt-0.5">IP: {{ $activity->ip_address }}</p>
                                            @endif
                                        </div>
                                        <div class="whitespace-nowrap text-right text-sm text-gray-500">
                                            <time datetime="{{ $activity->created_at->toIso8601String() }}">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-card>
@endsection
