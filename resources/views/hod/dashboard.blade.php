@extends('layouts.app')

@section('title', 'HOD Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @if(!$department)
        <x-card>
            <x-empty-state title="No department assigned" description="You are not currently assigned to any department. Please contact an administrator." />
        </x-card>
    @else
        {{-- Department header --}}
        <div class="card px-6 py-5 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $department->name }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $stats['staff_count'] }} staff {{ Str::plural('member', $stats['staff_count']) }}</p>
                </div>
                <a href="{{ route('hod.department.show') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View Department</a>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat-card title="Department Reports" :value="$stats['total_department_reports']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card title="To Review" :value="$stats['reports_to_review']">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card title="My Reports" :value="$stats['my_reports']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>

            <x-stat-card title="Staff Members" :value="$stats['staff_count']">
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </x-slot:icon>
            </x-stat-card>
        </div>

        {{-- Two-column: Reports to Review + Quick Actions --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            {{-- Reports to Review (2/3) --}}
            <div class="lg:col-span-2">
                <x-card title="Reports to Review">
                    <x-slot:actions>
                        <a href="{{ route('hod.reports.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">View all</a>
                    </x-slot:actions>

                    @if($reportsToReview->isEmpty())
                        <x-empty-state title="No reports to review" description="All submitted reports have been reviewed." />
                    @else
                        <div class="-mx-6 -my-4 overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/50">
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($reportsToReview as $report)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-3 text-gray-900 font-medium">{{ $report->user->full_name }}</td>
                                            <td class="px-6 py-3 text-gray-500">{{ Str::limit($report->title, 30) }}</td>
                                            <td class="px-6 py-3 text-gray-500 capitalize">{{ $report->report_category }}</td>
                                            <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $report->created_at->format('M d, Y') }}</td>
                                            <td class="px-6 py-3">
                                                <x-button variant="primary" size="sm" :href="route('hod.reports.show', $report)">View</x-button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Quick Actions (1/3) --}}
            <div>
                <x-card title="Quick Actions">
                    <div class="space-y-3">
                        <x-button variant="primary" class="w-full justify-center" :href="route('hod.reports.create', ['type' => 'department'])">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Department Report
                        </x-button>

                        <x-button variant="secondary" class="w-full justify-center" :href="route('hod.reports.create', ['type' => 'personal'])">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Personal Report
                        </x-button>

                        <x-button variant="secondary" class="w-full justify-center" :href="route('hod.department.show')">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            View Department Staff
                        </x-button>
                    </div>
                </x-card>
            </div>
        </div>

        {{-- Recent Department Reports with filter tabs --}}
        <div x-data="{ activeTab: 'all' }">
            <x-card title="Recent Department Reports">
                <x-slot:actions>
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
                        <button @click="activeTab = 'all'"
                            :class="activeTab === 'all' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors">
                            All
                        </button>
                        <button @click="activeTab = 'personal'"
                            :class="activeTab === 'personal' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors">
                            Personal
                        </button>
                        <button @click="activeTab = 'department'"
                            :class="activeTab === 'department' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-colors">
                            Department
                        </button>
                    </div>
                </x-slot:actions>

                @if($recentDepartmentReports->isEmpty())
                    <x-empty-state title="No reports yet" description="Department reports will appear here." />
                @else
                    <div class="-mx-6 -my-4 overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($recentDepartmentReports as $report)
                                    <tr class="hover:bg-gray-50"
                                        x-show="activeTab === 'all' || activeTab === '{{ $report->report_type }}'"
                                        x-transition>
                                        <td class="px-6 py-3 text-gray-900 font-medium">{{ $report->user->full_name }}</td>
                                        <td class="px-6 py-3">
                                            <a href="{{ route('hod.reports.show', $report) }}" class="text-gray-700 hover:text-primary-600">
                                                {{ Str::limit($report->title, 30) }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-3">
                                            <x-badge :type="$report->report_type === 'department' ? 'primary' : 'info'">{{ ucfirst($report->report_type) }}</x-badge>
                                        </td>
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
    @endif
@endsection
