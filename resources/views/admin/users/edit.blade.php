@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
        {{-- Back link --}}
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            Back to Users
        </a>

        <x-card title="Edit User">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="space-y-5">
                    <div>
                        <x-input
                            name="kingschat_id"
                            label="KingsChat ID"
                            :value="old('kingschat_id', $user->kingschat_id)"
                            :error="$errors->first('kingschat_id')"
                            readonly
                            disabled />
                        <p class="mt-1.5 text-xs text-gray-500">KingsChat ID cannot be changed after creation.</p>
                    </div>

                    <x-select
                        name="title"
                        label="Title"
                        placeholder="Select Title"
                        :selected="old('title', $user->title)"
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
                            :value="old('first_name', $user->first_name)"
                            :error="$errors->first('first_name')"
                            required
                            placeholder="John" />

                        <x-input
                            name="last_name"
                            label="Last Name"
                            :value="old('last_name', $user->last_name)"
                            :error="$errors->first('last_name')"
                            required
                            placeholder="Doe" />
                    </div>

                    <x-input
                        name="email"
                        label="Email"
                        type="email"
                        :value="old('email', $user->email)"
                        :error="$errors->first('email')"
                        placeholder="john.doe@example.com (optional)" />

                    <x-input
                        name="phone"
                        label="Phone Number"
                        type="tel"
                        :value="old('phone', $user->phone)"
                        :error="$errors->first('phone')"
                        required
                        placeholder="e.g., +234801234567" />

                    <x-select
                        name="department_id"
                        label="Department"
                        placeholder="Select Department (Optional)"
                        :selected="old('department_id', $user->department_id)"
                        :error="$errors->first('department_id')"
                        :options="$departments->pluck('name', 'id')->toArray()" />

                    <div>
                        <x-select
                            name="role"
                            label="Role"
                            :selected="old('role', $user->roles->first()?->name)"
                            :error="$errors->first('role')"
                            required
                            :options="$roles" />
                        <p class="mt-1.5 text-xs text-gray-500">
                            Select the user's role to define their permissions in the system.
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Account Status</h4>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $user->is_active ? 'Account is currently active' : 'Account is currently inactive' }}
                                </p>
                            </div>
                            <x-badge :type="$user->is_active ? 'success' : 'danger'">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Use the "Activate" or "Deactivate" action in the user list to change account status.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-8 pt-5 border-t border-gray-100">
                    <x-button type="submit" variant="primary">Update User</x-button>
                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-gray-500 hover:text-gray-700">View User</a>
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-gray-700 ml-auto">Cancel</a>
                </div>
            </form>
        </x-card>

        {{-- Report Links Management --}}
        @can('create', App\Models\ReportLink::class)
            <x-card title="Report Links" class="mt-6" id="report-links-section">
                <x-slot:actions>
                    <button type="button" onclick="showAddLinkModal()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Add Link
                    </button>
                </x-slot:actions>

                @if($reportLinks->isEmpty())
                    <x-empty-state
                        title="No report links"
                        description="Add Google Docs links for {{ $user->first_name }} to access their reports quickly."
                    />
                @else
                    <div class="space-y-3" id="links-list">
                        @foreach($reportLinks as $link)
                            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors" data-link-id="{{ $link->id }}">
                                <div class="flex-1 min-w-0">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-primary-600 hover:text-primary-800 truncate block">
                                        {{ $link->url }}
                                    </a>
                                    <p class="text-xs text-gray-500 mt-0.5">Added {{ $link->created_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button type="button" onclick="editLink({{ $link->id }}, '{{ $link->url }}')" class="text-gray-600 hover:text-primary-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </button>
                                    <button type="button" onclick="deleteLink({{ $link->id }})" class="text-gray-600 hover:text-red-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            {{-- Add/Edit Link Modal --}}
            <x-modal name="link-modal" maxWidth="lg">
                <x-slot:title>
                    <span id="modal-title">Add Report Link</span>
                </x-slot:title>

                <form id="link-form" onsubmit="submitLinkForm(event)">
                    <input type="hidden" id="link-id" name="link_id" value="">
                    <input type="hidden" name="_method" id="form-method" value="POST">

                    <div class="space-y-4">
                        <div>
                            <label for="link-url" class="block text-sm font-medium text-gray-700 mb-1">
                                Google Docs URL <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="url"
                                id="link-url"
                                name="url"
                                required
                                placeholder="https://docs.google.com/document/d/..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                            <p class="text-xs text-gray-500 mt-1">Enter the full Google Docs URL</p>
                            <p class="text-xs text-red-600 mt-1 hidden" id="url-error"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
                            <span id="submit-text">Add Link</span>
                        </button>
                    </div>
                </form>
            </x-modal>

            @push('scripts')
            <script>
                const userId = {{ $user->id }};
                let editingLinkId = null;

                function showAddLinkModal() {
                    editingLinkId = null;
                    document.getElementById('modal-title').textContent = 'Add Report Link';
                    document.getElementById('link-form').reset();
                    document.getElementById('link-id').value = '';
                    document.getElementById('form-method').value = 'POST';
                    document.getElementById('submit-text').textContent = 'Add Link';
                    document.getElementById('url-error').classList.add('hidden');
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'link-modal' }));
                }

                function editLink(linkId, currentUrl) {
                    editingLinkId = linkId;
                    document.getElementById('modal-title').textContent = 'Edit Report Link';
                    document.getElementById('link-id').value = linkId;
                    document.getElementById('link-url').value = currentUrl;
                    document.getElementById('form-method').value = 'PUT';
                    document.getElementById('submit-text').textContent = 'Update Link';
                    document.getElementById('url-error').classList.add('hidden');
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'link-modal' }));
                }

                async function submitLinkForm(event) {
                    event.preventDefault();

                    const formData = new FormData(event.target);
                    const url = formData.get('url');
                    const method = formData.get('_method');
                    const linkId = formData.get('link_id');

                    let endpoint = '';
                    let fetchOptions = {
                        method: method === 'PUT' ? 'POST' : method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ url }),
                    };

                    if (method === 'POST') {
                        endpoint = `/admin/users/${userId}/report-links`;
                    } else {
                        endpoint = `/admin/report-links/${linkId}`;
                        fetchOptions.headers['X-HTTP-Method-Override'] = 'PUT';
                    }

                    try {
                        const response = await fetch(endpoint, fetchOptions);
                        const data = await response.json();

                        if (response.ok && data.success) {
                            showNotification(data.message, 'success');
                            closeModal();
                            window.location.reload();
                        } else {
                            if (data.errors && data.errors.url) {
                                document.getElementById('url-error').textContent = data.errors.url[0];
                                document.getElementById('url-error').classList.remove('hidden');
                            } else {
                                showNotification(data.message || 'An error occurred', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    }
                }

                async function deleteLink(linkId) {
                    if (!confirm('Are you sure you want to delete this report link?')) {
                        return;
                    }

                    try {
                        const response = await fetch(`/admin/report-links/${linkId}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'X-HTTP-Method-Override': 'DELETE',
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            showNotification(data.message, 'success');
                            document.querySelector(`[data-link-id="${linkId}"]`).remove();

                            if (document.querySelectorAll('[data-link-id]').length === 0) {
                                window.location.reload();
                            }
                        } else {
                            showNotification(data.message || 'Failed to delete link', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    }
                }

                function closeModal() {
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'link-modal' }));
                }

                function showNotification(message, type = 'success') {
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, type);
                    } else {
                        alert(message);
                    }
                }
            </script>
            @endpush
        @endcan
@endsection
