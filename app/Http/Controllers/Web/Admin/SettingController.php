<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', SiteSetting::class);

        $settings = [
            'general' => $this->getSettingsByGroup(SiteSetting::GROUP_GENERAL),
            'appearance' => $this->getSettingsByGroup(SiteSetting::GROUP_APPEARANCE),
            'email' => $this->getSettingsByGroup(SiteSetting::GROUP_EMAIL),
            'reports' => $this->getSettingsByGroup(SiteSetting::GROUP_REPORTS),
            'features' => $this->getSettingsByGroup(SiteSetting::GROUP_FEATURES),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): JsonResponse
    {
        Gate::authorize('manage', SiteSetting::class);

        $group = $request->input('group');

        if (!in_array($group, [
            SiteSetting::GROUP_GENERAL,
            SiteSetting::GROUP_APPEARANCE,
            SiteSetting::GROUP_EMAIL,
            SiteSetting::GROUP_REPORTS,
            SiteSetting::GROUP_FEATURES,
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid settings group.'
            ], 422);
        }

        $validator = $this->getValidatorForGroup($group, $request);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            $this->saveSettingsByGroup($group, $request);
            SiteSetting::clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getSettingsByGroup(string $group): array
    {
        $settings = SiteSetting::byGroup($group)->get();
        $data = [];

        foreach ($settings as $setting) {
            if ($setting->type === SiteSetting::TYPE_IMAGE) {
                $collection = match ($setting->key) {
                    'site_logo' => 'logo',
                    'site_favicon' => 'favicon',
                    default => null,
                };

                if ($collection) {
                    $data[$setting->key] = [
                        'value' => $setting->value,
                        'type' => $setting->type,
                        'label' => $setting->label,
                        'description' => $setting->description,
                        'media_url' => $setting->getFirstMediaUrl($collection),
                    ];
                    continue;
                }
            }

            if ($setting->key === 'max_upload_size') {
                $data[$setting->key] = [
                    'value' => $setting->value / (1024 * 1024),
                    'type' => $setting->type,
                    'label' => $setting->label,
                    'description' => $setting->description,
                ];
                continue;
            }

            $data[$setting->key] = [
                'value' => $setting->value,
                'type' => $setting->type,
                'label' => $setting->label,
                'description' => $setting->description,
            ];
        }

        return $data;
    }

    private function getValidatorForGroup(string $group, Request $request): \Illuminate\Validation\Validator
    {
        $rules = match ($group) {
            SiteSetting::GROUP_GENERAL => [
                'site_name' => 'required|string|max:255',
                'site_description' => 'nullable|string|max:1000',
                'site_logo' => 'nullable|image|mimes:jpeg,jpg,png,svg,webp|max:2048',
                'site_favicon' => 'nullable|image|mimes:ico,png,jpg|max:512',
            ],
            SiteSetting::GROUP_APPEARANCE => [
                'primary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'secondary_color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'custom_css' => 'nullable|string|max:10000',
            ],
            SiteSetting::GROUP_EMAIL => [
                'mail_from_name' => 'required|string|max:255',
                'mail_from_address' => 'required|email|max:255',
                'email_signature' => 'nullable|string|max:2000',
            ],
            SiteSetting::GROUP_REPORTS => [
                'max_upload_size' => 'required|numeric|min:1|max:1024',
                'allowed_file_types' => 'required|string|max:500',
            ],
            SiteSetting::GROUP_FEATURES => [
                'enable_proposals' => 'nullable|boolean',
                'enable_email_notifications' => 'nullable|boolean',
                'enable_kingschat_notifications' => 'nullable|boolean',
            ],
            default => [],
        };

        return Validator::make($request->all(), $rules);
    }

    private function saveSettingsByGroup(string $group, Request $request): void
    {
        match ($group) {
            SiteSetting::GROUP_GENERAL => $this->saveGeneralSettings($request),
            SiteSetting::GROUP_APPEARANCE => $this->saveAppearanceSettings($request),
            SiteSetting::GROUP_EMAIL => $this->saveEmailSettings($request),
            SiteSetting::GROUP_REPORTS => $this->saveReportsSettings($request),
            SiteSetting::GROUP_FEATURES => $this->saveFeaturesSettings($request),
        };
    }

    private function saveGeneralSettings(Request $request): void
    {
        SiteSetting::set('site_name', $request->input('site_name'));
        SiteSetting::set('site_description', $request->input('site_description'));

        if ($request->hasFile('site_logo')) {
            SiteSetting::setMedia('site_logo', $request->file('site_logo'));
        }

        if ($request->hasFile('site_favicon')) {
            SiteSetting::setMedia('site_favicon', $request->file('site_favicon'));
        }
    }

    private function saveAppearanceSettings(Request $request): void
    {
        SiteSetting::set('primary_color', $request->input('primary_color'));
        SiteSetting::set('secondary_color', $request->input('secondary_color'));
        SiteSetting::set('custom_css', $request->input('custom_css'));
    }

    private function saveEmailSettings(Request $request): void
    {
        SiteSetting::set('mail_from_name', $request->input('mail_from_name'));
        SiteSetting::set('mail_from_address', $request->input('mail_from_address'));
        SiteSetting::set('email_signature', $request->input('email_signature'));
    }

    private function saveReportsSettings(Request $request): void
    {
        $maxUploadSizeMB = $request->input('max_upload_size');
        $maxUploadSizeBytes = $maxUploadSizeMB * 1024 * 1024;

        SiteSetting::set('max_upload_size', (string) $maxUploadSizeBytes);
        SiteSetting::set('allowed_file_types', $request->input('allowed_file_types'));
    }

    private function saveFeaturesSettings(Request $request): void
    {
        SiteSetting::set('enable_proposals', $request->boolean('enable_proposals') ? '1' : '0');
        SiteSetting::set('enable_email_notifications', $request->boolean('enable_email_notifications') ? '1' : '0');
        SiteSetting::set('enable_kingschat_notifications', $request->boolean('enable_kingschat_notifications') ? '1' : '0');
    }
}
