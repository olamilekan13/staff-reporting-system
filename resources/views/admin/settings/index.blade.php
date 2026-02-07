@extends('layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="max-w-6xl" x-data="settingsPage()">
    {{-- Tab Navigation --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px overflow-x-auto">
                <button
                    @click="activeTab = 'general'"
                    :class="activeTab === 'general' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    General
                </button>
                <button
                    @click="activeTab = 'appearance'"
                    :class="activeTab === 'appearance' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Appearance
                </button>
                <button
                    @click="activeTab = 'email'"
                    :class="activeTab === 'email' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Email
                </button>
                <button
                    @click="activeTab = 'reports'"
                    :class="activeTab === 'reports' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Reports
                </button>
                <button
                    @click="activeTab = 'features'"
                    :class="activeTab === 'features' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm transition-colors">
                    Features
                </button>
            </nav>
        </div>
    </div>

    {{-- General Tab --}}
    <div x-show="activeTab === 'general'" x-transition>
        <x-card title="General Settings" subtitle="Configure basic site information">
            <form @submit.prevent="saveTab('general')" enctype="multipart/form-data">
                <div class="space-y-5">
                    <x-input
                        name="site_name"
                        label="Site Name"
                        placeholder="Staff Reporting Management"
                        :value="old('site_name', $settings['general']['site_name']['value'] ?? '')"
                        required
                    />

                    <div>
                        <label for="site_description" class="label">Site Description</label>
                        <textarea
                            name="site_description"
                            id="site_description"
                            rows="3"
                            class="input"
                            placeholder="Describe your site..."
                        >{{ old('site_description', $settings['general']['site_description']['value'] ?? '') }}</textarea>
                    </div>

                    {{-- Logo Upload --}}
                    <div>
                        <label class="label">Site Logo</label>
                        @if(!empty($settings['general']['site_logo']['media_url']))
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $settings['general']['site_logo']['media_url'] }}"
                                     alt="Current logo"
                                     class="h-16 w-auto rounded border border-gray-200 p-2 bg-white">
                                <span class="text-sm text-gray-600">Current logo</span>
                            </div>
                        @endif
                        <input
                            type="file"
                            name="site_logo"
                            id="site_logo"
                            accept="image/jpeg,image/jpg,image/png,image/svg+xml,image/webp"
                            @change="previewImage($event, 'logo-preview')"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                        >
                        <p class="text-xs text-gray-500 mt-1">Recommended: PNG, JPG, SVG (max 2MB)</p>
                        <img id="logo-preview" class="hidden mt-3 h-16 w-auto rounded border border-gray-200 p-2 bg-white">
                    </div>

                    {{-- Favicon Upload --}}
                    <div>
                        <label class="label">Favicon</label>
                        @if(!empty($settings['general']['site_favicon']['media_url']))
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $settings['general']['site_favicon']['media_url'] }}"
                                     alt="Current favicon"
                                     class="h-8 w-8 rounded border border-gray-200 p-1 bg-white">
                                <span class="text-sm text-gray-600">Current favicon</span>
                            </div>
                        @endif
                        <input
                            type="file"
                            name="site_favicon"
                            id="site_favicon"
                            accept=".ico,image/x-icon,image/png,image/jpeg"
                            @change="previewImage($event, 'favicon-preview')"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                        >
                        <p class="text-xs text-gray-500 mt-1">Recommended: ICO, PNG (16x16 or 32x32, max 512KB)</p>
                        <img id="favicon-preview" class="hidden mt-3 h-8 w-8 rounded border border-gray-200 p-1 bg-white">
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="btn btn-primary" :disabled="loading.general">
                        <span x-show="!loading.general">Save Changes</span>
                        <span x-show="loading.general" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Appearance Tab --}}
    <div x-show="activeTab === 'appearance'" x-transition>
        <x-card title="Appearance Settings" subtitle="Customize the look and feel of your site">
            <form @submit.prevent="saveTab('appearance')">
                <div class="space-y-5">
                    <div>
                        <label for="primary_color" class="label">Primary Color</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                name="primary_color"
                                id="primary_color"
                                value="{{ old('primary_color', $settings['appearance']['primary_color']['value'] ?? '#3b82f6') }}"
                                class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
                            >
                            <input
                                type="text"
                                x-model="$el.previousElementSibling.value"
                                @input="$el.previousElementSibling.value = $event.target.value"
                                value="{{ old('primary_color', $settings['appearance']['primary_color']['value'] ?? '#3b82f6') }}"
                                class="input flex-1"
                                placeholder="#3b82f6"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="secondary_color" class="label">Secondary Color</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                name="secondary_color"
                                id="secondary_color"
                                value="{{ old('secondary_color', $settings['appearance']['secondary_color']['value'] ?? '#64748b') }}"
                                class="h-10 w-20 rounded border border-gray-300 cursor-pointer"
                            >
                            <input
                                type="text"
                                x-model="$el.previousElementSibling.value"
                                @input="$el.previousElementSibling.value = $event.target.value"
                                value="{{ old('secondary_color', $settings['appearance']['secondary_color']['value'] ?? '#64748b') }}"
                                class="input flex-1"
                                placeholder="#64748b"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="custom_css" class="label">Custom CSS</label>
                        <textarea
                            name="custom_css"
                            id="custom_css"
                            rows="8"
                            class="input font-mono text-xs"
                            placeholder="/* Add your custom CSS here */"
                        >{{ old('custom_css', $settings['appearance']['custom_css']['value'] ?? '') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Add custom CSS to override default styles. Be careful!</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="btn btn-primary" :disabled="loading.appearance">
                        <span x-show="!loading.appearance">Save Changes</span>
                        <span x-show="loading.appearance" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Email Tab --}}
    <div x-show="activeTab === 'email'" x-transition>
        <x-card title="Email Settings" subtitle="Configure email sender information">
            <form @submit.prevent="saveTab('email')">
                <div class="space-y-5">
                    <x-input
                        name="mail_from_name"
                        label="From Name"
                        placeholder="Staff Reporting"
                        :value="old('mail_from_name', $settings['email']['mail_from_name']['value'] ?? '')"
                        required
                    />

                    <x-input
                        name="mail_from_address"
                        label="From Email Address"
                        type="email"
                        placeholder="noreply@example.com"
                        :value="old('mail_from_address', $settings['email']['mail_from_address']['value'] ?? '')"
                        required
                    />

                    <div>
                        <label for="email_signature" class="label">Email Signature</label>
                        <textarea
                            name="email_signature"
                            id="email_signature"
                            rows="5"
                            class="input"
                            placeholder="Best regards,&#10;The Staff Reporting Team"
                        >{{ old('email_signature', $settings['email']['email_signature']['value'] ?? '') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="btn btn-primary" :disabled="loading.email">
                        <span x-show="!loading.email">Save Changes</span>
                        <span x-show="loading.email" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Reports Tab --}}
    <div x-show="activeTab === 'reports'" x-transition>
        <x-card title="Reports Settings" subtitle="Configure report upload and file handling">
            <form @submit.prevent="saveTab('reports')">
                <div class="space-y-5">
                    <div>
                        <label for="max_upload_size" class="label">Max Upload Size (MB)</label>
                        <input
                            type="number"
                            name="max_upload_size"
                            id="max_upload_size"
                            value="{{ old('max_upload_size', $settings['reports']['max_upload_size']['value'] ?? 50) }}"
                            min="1"
                            max="1024"
                            step="1"
                            class="input"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Maximum file size for uploads (1-1024 MB)</p>
                    </div>

                    <div>
                        <label for="allowed_file_types" class="label">Allowed File Types</label>
                        <input
                            type="text"
                            name="allowed_file_types"
                            id="allowed_file_types"
                            value="{{ old('allowed_file_types', $settings['reports']['allowed_file_types']['value'] ?? '') }}"
                            class="input"
                            placeholder="pdf,doc,docx,xls,xlsx,jpg,png"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Comma-separated file extensions (no spaces)</p>
                    </div>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="btn btn-primary" :disabled="loading.reports">
                        <span x-show="!loading.reports">Save Changes</span>
                        <span x-show="loading.reports" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Features Tab --}}
    <div x-show="activeTab === 'features'" x-transition>
        <x-card title="Feature Toggles" subtitle="Enable or disable platform features">
            <form @submit.prevent="saveTab('features')">
                <div class="space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <input
                            type="checkbox"
                            name="enable_proposals"
                            value="1"
                            {{ old('enable_proposals', $settings['features']['enable_proposals']['value'] ?? false) ? 'checked' : '' }}
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Enable Proposals</div>
                            <p class="text-sm text-gray-500">Allow users to submit and manage proposals</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <input
                            type="checkbox"
                            name="enable_email_notifications"
                            value="1"
                            {{ old('enable_email_notifications', $settings['features']['enable_email_notifications']['value'] ?? false) ? 'checked' : '' }}
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Enable Email Notifications</div>
                            <p class="text-sm text-gray-500">Send notifications via email</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 cursor-pointer p-3 rounded-lg hover:bg-gray-50 transition-colors">
                        <input
                            type="checkbox"
                            name="enable_kingschat_notifications"
                            value="1"
                            {{ old('enable_kingschat_notifications', $settings['features']['enable_kingschat_notifications']['value'] ?? false) ? 'checked' : '' }}
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <div class="flex-1">
                            <div class="font-medium text-gray-900">Enable KingsChat Notifications</div>
                            <p class="text-sm text-gray-500">Send notifications via KingsChat</p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center gap-3 mt-6 pt-5 border-t border-gray-100">
                    <button type="submit" class="btn btn-primary" :disabled="loading.features">
                        <span x-show="!loading.features">Save Changes</span>
                        <span x-show="loading.features" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsPage() {
    return {
        activeTab: 'general',
        loading: {
            general: false,
            appearance: false,
            email: false,
            reports: false,
            features: false,
        },

        async saveTab(group) {
            this.loading[group] = true;

            const form = event.target;
            const formData = new FormData(form);

            formData.append('group', group);
            formData.append('_method', 'PUT');

            try {
                const response = await fetch('{{ route('admin.settings.update') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok) {
                    this.$dispatch('toast', {
                        type: 'success',
                        title: data.message || 'Settings saved successfully.'
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    this.$dispatch('toast', {
                        type: 'error',
                        title: data.message || 'Failed to save settings.'
                    });
                }
            } catch (error) {
                this.$dispatch('toast', {
                    type: 'error',
                    title: 'An error occurred while saving settings.'
                });
                console.error('Error:', error);
            } finally {
                this.loading[group] = false;
            }
        },

        previewImage(event, previewId) {
            const file = event.target.files[0];
            const preview = document.getElementById(previewId);

            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }
    }
}
</script>
@endpush
