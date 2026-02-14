@extends('layouts.app')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('content')
        {{-- Back link --}}
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Users
        </a>

        <x-card title="New User">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf

                <div class="space-y-5">
                    <x-input
                        name="kingschat_id"
                        label="KingsChat ID"
                        :value="old('kingschat_id')"
                        :error="$errors->first('kingschat_id')"
                        required
                        placeholder="e.g., john.doe" />

                    <x-select
                        name="title"
                        label="Title"
                        placeholder="Select Title"
                        :selected="old('title')"
                        :error="$errors->first('title')"
                        required
                        :options="[
                            'Pastor' => 'Pastor',
                            'Deacon' => 'Deacon',
                            'Deaconess' => 'Deaconess',
                            'Brother' => 'Brother',
                            'Sister' => 'Sister',
                        ]" />

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input
                            name="first_name"
                            label="First Name"
                            :value="old('first_name')"
                            :error="$errors->first('first_name')"
                            required
                            placeholder="John" />

                        <x-input
                            name="last_name"
                            label="Last Name"
                            :value="old('last_name')"
                            :error="$errors->first('last_name')"
                            required
                            placeholder="Doe" />
                    </div>

                    <x-input
                        name="email"
                        label="Email"
                        type="email"
                        :value="old('email')"
                        :error="$errors->first('email')"
                        placeholder="john.doe@example.com (optional)" />

                    <x-input
                        name="phone"
                        label="Phone Number"
                        type="tel"
                        :value="old('phone')"
                        :error="$errors->first('phone')"
                        required
                        placeholder="e.g., +234801234567" />

                    <x-select
                        name="department_id"
                        label="Department"
                        placeholder="Select Department (Optional)"
                        :selected="old('department_id')"
                        :error="$errors->first('department_id')"
                        :options="$departments->pluck('name', 'id')->toArray()" />

                    <div>
                        <x-select
                            name="role"
                            label="Role"
                            :selected="old('role', 'staff')"
                            :error="$errors->first('role')"
                            required
                            :options="$roles" />
                        <p class="mt-1.5 text-xs text-gray-500">
                            Select the user's role to define their permissions in the system.
                        </p>
                    </div>

                    {{-- Report Links Section --}}
                    @can('create', App\Models\ReportLink::class)
                        <div class="pt-6 mt-6 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Report Links (Optional)</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Add Google Docs links for this user to access their reports</p>
                                </div>
                                <button type="button" onclick="addReportLinkField()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-600 hover:text-primary-700">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    Add Link
                                </button>
                            </div>

                            <div id="report-links-container" class="space-y-3">
                                {{-- Links will be added here dynamically --}}
                            </div>
                        </div>
                    @endcan
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Create User</x-button>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>

        @can('create', App\Models\ReportLink::class)
            @push('scripts')
            <script>
                let linkCounter = 0;

                function addReportLinkField() {
                    linkCounter++;
                    const container = document.getElementById('report-links-container');
                    const linkDiv = document.createElement('div');
                    linkDiv.className = 'flex items-center gap-2';
                    linkDiv.id = `link-field-${linkCounter}`;

                    linkDiv.innerHTML = `
                        <input
                            type="url"
                            name="report_links[]"
                            placeholder="https://docs.google.com/document/d/..."
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        >
                        <button type="button" onclick="removeReportLinkField(${linkCounter})" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    `;

                    container.appendChild(linkDiv);
                }

                function removeReportLinkField(id) {
                    const field = document.getElementById(`link-field-${id}`);
                    if (field) {
                        field.remove();
                    }
                }

                // Add one field by default
                addReportLinkField();
            </script>
            @endpush
        @endcan
@endsection
