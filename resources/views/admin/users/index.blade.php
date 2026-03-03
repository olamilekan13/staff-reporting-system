@extends('layouts.app')

@section('title', 'Manage Users')
@section('page-title', 'Manage Users')

@section('content')
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">Manage user accounts and permissions</p>
        </div>
        <div class="flex items-center gap-2">
            @can('import', App\Models\User::class)
                <x-button variant="secondary" size="sm" @click="$dispatch('open-modal', 'import-users')">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                    </svg>
                    Import Users
                </x-button>
                <x-button variant="secondary" size="sm" :href="route('admin.users.export', request()->query())">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export to Excel
                </x-button>
            @endcan
            @can('create', App\Models\User::class)
                <x-button variant="primary" size="sm" :href="route('admin.users.create')">
                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New User
                </x-button>
            @endcan
        </div>
    </div>

    {{-- Import Result --}}
    @if(session('import_result'))
        @php
            $result = session('import_result');
        @endphp
        <div class="mb-6">
            <x-alert type="success">
                Successfully imported {{ $result['success_count'] }} user(s).
                @if(count($result['failures']) > 0)
                    {{ count($result['failures']) }} row(s) failed validation.
                @endif
            </x-alert>

            @if(count($result['failures']) > 0)
                <x-card title="Import Errors" class="mt-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Row</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Attribute</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Errors</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($result['failures'] as $failure)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-900">{{ $failure['row'] }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $failure['attribute'] }}</td>
                                        <td class="px-4 py-2 text-red-600">{{ implode(', ', $failure['errors']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    @endif

    {{-- Filters --}}
    <div x-data="usersIndex()" class="mb-6">
        <button @click="showFilters = !showFilters" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 mb-3">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
            </svg>
            <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
        </button>

        <div x-show="showFilters" x-transition class="card px-6 py-4">
            <form method="GET" action="{{ route('admin.users.index') }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-input name="search" label="Search" placeholder="Name, email, KingsChat ID..." :value="request('search')" />

                    <x-select name="department_id" label="Department" placeholder="All Departments" :selected="request('department_id')"
                        :options="$departments->pluck('name', 'id')->toArray()" />

                    <x-select name="role" label="Role" placeholder="All Roles" :selected="request('role')"
                        :options="$roles" />

                    <x-select name="is_active" label="Status" placeholder="All Statuses" :selected="request('is_active')"
                        :options="['1' => 'Active', '0' => 'Inactive']" />
                </div>

                <div class="flex items-center gap-3 mt-4">
                    <x-button type="submit" variant="primary" size="sm">Filter</x-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                </div>
            </form>
        </div>

        {{-- Bulk Delete Action Bar --}}
        @can('bulkDelete', App\Models\User::class)
            <div x-show="selectedUsers.length > 0" style="display:none;" class="mt-6 flex items-center justify-between gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                <span class="text-sm font-medium text-red-800">
                    <span x-text="selectedUsers.length"></span> user(s) selected
                </span>
                <div class="flex items-center gap-2">
                    <button @click="selectedUsers = []" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 rounded-lg border border-gray-300 bg-white">
                        Clear
                    </button>
                    <button @click="showDeleteModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Delete Permanently (<span x-text="selectedUsers.length"></span>)
                    </button>
                </div>
            </div>
        @endcan

        {{-- Users table --}}
        @if($users->isEmpty())
            <x-card class="mt-6">
                <x-empty-state title="No users found" description="No users match your current filters.">
                    <x-slot:action>
                        @can('create', App\Models\User::class)
                            <x-button variant="primary" size="sm" :href="route('admin.users.create')">Create User</x-button>
                        @endcan
                    </x-slot:action>
                </x-empty-state>
            </x-card>
        @else
            <div class="card overflow-hidden mt-6">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50">
                                @can('bulkDelete', App\Models\User::class)
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" @change="toggleSelectAll($event)"
                                            :checked="selectedUsers.length > 0 && selectedUsers.length === selectableUserIds.length"
                                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </th>
                                @endcan
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KingsChat ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">

                @foreach($users as $user)
                    <tr class="hover:bg-gray-50" :class="selectedUsers.includes({{ $user->id }}) ? 'bg-primary-50/50' : ''">
                        @can('bulkDelete', App\Models\User::class)
                            <td class="px-4 py-3">
                                @if(auth()->id() !== $user->id && !$user->hasRole('super_admin'))
                                    <input type="checkbox" value="{{ $user->id }}" x-model.number="selectedUsers"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                @endif
                            </td>
                        @endcan
                        <td class="px-6 py-3">
                            <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-gray-900 hover:text-primary-600">
                                {{ $user->full_name }}
                            </a>
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->kingschat_id }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->email ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600">{{ $user->department?->name ?? '-' }}</td>
                        <td class="px-6 py-3">
                            @php
                                $roleName = $user->roles->first()?->name ?? '';
                                $roleBadgeType = match($roleName) {
                                    'super_admin' => 'danger',
                                    'admin' => 'warning',
                                    'head_of_operations' => 'info',
                                    'hod' => 'primary',
                                    'staff' => 'success',
                                    default => 'secondary',
                                };
                            @endphp
                            <x-badge :type="$roleBadgeType">{{ $roles[$roleName] ?? 'No Role' }}</x-badge>
                        </td>
                        <td class="px-6 py-3">
                            <x-badge :type="$user->is_active ? 'success' : 'danger'">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-3">
                            <x-dropdown>
                                <x-slot:trigger>
                                    <button class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
                                        </svg>
                                    </button>
                                </x-slot:trigger>

                                <a href="{{ route('admin.users.show', $user) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">View</a>
                                @can('update', $user)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
                                @endcan
                                @can('activate', $user)
                                    <button
                                        @click="toggleActivation({{ $user->id }}, {{ $user->is_active ? 'true' : 'false' }}, '{{ $user->full_name }}')"
                                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                @endcan
                                @can('delete', $user)
                                    @if(auth()->id() !== $user->id && !$user->hasRole('super_admin'))
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to PERMANENTLY DELETE this user? This will remove all their data and cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Delete Permanently</button>
                                        </form>
                                    @endif
                                @endcan
                            </x-dropdown>
                        </td>
                    </tr>
                @endforeach

                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 border-t border-gray-100">
                    {{ $users->withQueryString()->links() }}
                </div>
            </div>
        @endif

    {{-- Bulk Delete Confirmation Modal --}}
    @can('bulkDelete', App\Models\User::class)
        <div x-show="showDeleteModal" style="display:none;" class="fixed inset-0 z-[60] overflow-y-auto">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full" @click.stop>
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-red-600">Permanently Delete Users</h3>
                    </div>
                    <div class="px-6 py-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                            <p class="text-sm text-red-800 font-medium">This will permanently delete <span x-text="selectedUsers.length"></span> user(s) and ALL their data:</p>
                            <ul class="text-xs text-red-700 mt-2 space-y-0.5 list-disc list-inside">
                                <li>Reports and proposals</li>
                                <li>Comments and notifications</li>
                                <li>Watch history and activity logs</li>
                            </ul>
                            <p class="text-sm text-red-900 font-bold mt-2">This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
                        <button @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button @click="executeBulkDelete()" :disabled="bulkDeleting"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2">
                            <svg x-show="bulkDeleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Delete Permanently
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    </div>

    {{-- Import Modal --}}
    <x-modal name="import-users" maxWidth="2xl">
        <x-slot:title>Import Users</x-slot:title>

        <div x-data="importModal()">
            <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
                    <h4 class="font-semibold text-blue-900 mb-2">Instructions:</h4>
                    <ol class="list-decimal list-inside space-y-1 text-blue-800">
                        <li>Download the import template below</li>
                        <li>Fill in the user details (KingsChat ID, names, email, phone, department, role)</li>
                        <li>Upload the completed file</li>
                        <li>Review the preview and fix any errors</li>
                        <li>Confirm to import the users</li>
                    </ol>
                    <div class="mt-3">
                        <a href="{{ route('admin.users.import.template') }}" class="text-blue-600 hover:text-blue-800 font-medium underline">
                            Download Import Template
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data" id="import-form">
                    @csrf

                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                        <input
                            type="file"
                            name="file"
                            id="import-file"
                            accept=".xlsx,.xls"
                            @change="selectFile($event)"
                            class="hidden"
                        >
                        <label for="import-file" class="cursor-pointer">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold text-primary-600 hover:text-primary-500">Click to upload</span>
                                or drag and drop
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Excel files only (XLSX, XLS) up to 5MB</p>
                        </label>
                        <p x-show="fileName" class="mt-3 text-sm font-medium text-gray-700" x-text="fileName"></p>
                    </div>

                    {{-- Preview Table --}}
                    <div x-show="previewData && previewData.length > 0" x-transition class="mt-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Preview Data:</h4>
                        <div class="overflow-x-auto max-h-64 overflow-y-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full text-xs">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Row</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">KingsChat ID</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Name</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Email</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Department</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Role</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <template x-for="row in previewData" :key="row.row">
                                        <tr>
                                            <td class="px-3 py-2 text-gray-600" x-text="row.row"></td>
                                            <td class="px-3 py-2 text-gray-900" x-text="row.kingschat_id"></td>
                                            <td class="px-3 py-2 text-gray-900" x-text="row.first_name + ' ' + row.last_name"></td>
                                            <td class="px-3 py-2 text-gray-600" x-text="row.email || '-'"></td>
                                            <td class="px-3 py-2 text-gray-600" x-text="row.department_name || '-'"></td>
                                            <td class="px-3 py-2 text-gray-600" x-text="row.role"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Preview Errors --}}
                    <div x-show="previewErrors && previewErrors.length > 0" x-transition class="mt-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="font-semibold text-red-900 mb-2">Validation Errors:</h4>
                            <ul class="space-y-1 text-sm text-red-800">
                                <template x-for="error in previewErrors" :key="error.row">
                                    <li>
                                        <strong>Row <span x-text="error.row"></span>:</strong>
                                        <span x-text="error.errors.join(', ')"></span>
                                    </li>
                                </template>
                            </ul>
                            <p class="text-xs text-red-700 mt-2">Fix these errors in your Excel file before importing.</p>
                        </div>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="loading" class="text-center py-4">
                        <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-gray-600 mt-2">Loading preview...</p>
                    </div>
                </form>
            </div>
        </div>

        <x-slot:footer>
            <x-button @click="$dispatch('close-modal', 'import-users')" variant="secondary">Cancel</x-button>
            <x-button
                type="submit"
                form="import-form"
                variant="primary"
                x-bind:disabled="!fileName || (previewErrors && previewErrors.length > 0)">
                Import Users
            </x-button>
        </x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
<script>
    function usersIndex() {
        return {
            showFilters: {{ request()->hasAny(['department_id', 'role', 'is_active', 'search']) ? 'true' : 'false' }},

            // Bulk selection
            selectedUsers: [],
            selectableUserIds: [
                @foreach($users as $u)
                    @if(auth()->id() !== $u->id && !$u->hasRole('super_admin'))
                        {{ $u->id }},
                    @endif
                @endforeach
            ],
            showDeleteModal: false,
            bulkDeleting: false,

            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectedUsers = [...this.selectableUserIds];
                } else {
                    this.selectedUsers = [];
                }
            },

            async executeBulkDelete() {
                if (this.bulkDeleting) return;
                this.bulkDeleting = true;

                try {
                    const response = await fetch('{{ route("admin.users.bulk-delete") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ user_ids: this.selectedUsers }),
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        this.$dispatch('toast', { type: 'success', title: data.message });
                        this.showDeleteModal = false;
                        this.selectedUsers = [];
                        window.location.reload();
                    } else {
                        this.$dispatch('toast', { type: 'error', title: data.message || 'Failed to delete users.' });
                    }
                } catch (e) {
                    this.$dispatch('toast', { type: 'error', title: 'An error occurred.' });
                } finally {
                    this.bulkDeleting = false;
                }
            },

            async toggleActivation(userId, currentStatus, userName) {
                const action = currentStatus ? 'deactivate' : 'activate';
                if (!confirm(`Are you sure you want to ${action} ${userName}?`)) {
                    return;
                }

                try {
                    const response = await fetch(`/admin/users/${userId}/toggle-activation`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.$dispatch('toast', { type: 'success', title: data.message });
                        window.location.reload();
                    } else {
                        this.$dispatch('toast', { type: 'error', title: data.message || 'Failed to update status.' });
                    }
                } catch (e) {
                    this.$dispatch('toast', { type: 'error', title: 'An error occurred.' });
                }
            }
        }
    }

    function importModal() {
        return {
            file: null,
            fileName: '',
            previewData: null,
            previewErrors: null,
            loading: false,

            async selectFile(event) {
                this.file = event.target.files[0];
                this.fileName = this.file?.name || '';

                if (this.file) {
                    await this.fetchPreview();
                }
            },

            async fetchPreview() {
                this.loading = true;
                this.previewData = null;
                this.previewErrors = null;

                const formData = new FormData();
                formData.append('file', this.file);

                try {
                    const response = await fetch('/admin/users/import/preview', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok) {
                        this.previewData = data.preview;
                        this.previewErrors = data.errors;
                    } else {
                        this.$dispatch('toast', { type: 'error', title: data.message || 'Failed to preview file.' });
                    }
                } catch (e) {
                    this.$dispatch('toast', { type: 'error', title: 'An error occurred while previewing the file.' });
                }  finally {
                    this.loading = false;
                }
            },

            resetModal() {
                this.file = null;
                this.fileName = '';
                this.previewData = null;
                this.previewErrors = null;
            }
        }
    }
</script>
@endpush
