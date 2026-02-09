<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteSetting extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
    ];

    // Constants for groups
    const GROUP_GENERAL = 'general';
    const GROUP_APPEARANCE = 'appearance';
    const GROUP_EMAIL = 'email';
    const GROUP_REPORTS = 'reports';
    const GROUP_FEATURES = 'features';

    // Constants for types
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_IMAGE = 'image';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_COLOR = 'color';
    const TYPE_NUMBER = 'number';

    // Scopes
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    // Helper Methods
    public function getValueAttribute($value)
    {
        if ($this->type === self::TYPE_BOOLEAN) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->type === self::TYPE_JSON) {
            return json_decode($value, true);
        }

        if ($this->type === self::TYPE_NUMBER) {
            return is_numeric($value) ? (float) $value : 0;
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        if ($this->type === self::TYPE_BOOLEAN) {
            $this->attributes['value'] = $value ? '1' : '0';
            return;
        }

        if ($this->type === self::TYPE_JSON && is_array($value)) {
            $this->attributes['value'] = json_encode($value);
            return;
        }

        $this->attributes['value'] = $value;
    }

    // Static methods for getting/setting values
    public static function get(string $key, $default = null)
    {
        $cacheKey = "site_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value, array $attributes = []): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );

        Cache::forget("site_setting_{$key}");
        Cache::forget('all_site_settings');

        return $setting;
    }

    public static function getAll(): array
    {
        return Cache::remember('all_site_settings', 3600, function () {
            return self::all()->pluck('value', 'key')->toArray();
        });
    }

    public static function getByGroup(string $group): array
    {
        return self::byGroup($group)->get()->pluck('value', 'key')->toArray();
    }

    public static function clearCache(): void
    {
        $settings = self::all();

        foreach ($settings as $setting) {
            Cache::forget("site_setting_{$setting->key}");
        }

        Cache::forget('all_site_settings');
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp']);

        $this->addMediaCollection('favicon')
            ->singleFile()
            ->acceptsMimeTypes(['image/x-icon', 'image/png', 'image/jpeg']);
    }

    // Media Accessors
    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo') ?: null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('favicon') ?: null;
    }

    // Media Helper
    public static function setMedia(string $key, $file): void
    {
        $setting = self::firstOrCreate(['key' => $key]);

        $collection = match ($key) {
            'site_logo' => 'logo',
            'site_favicon' => 'favicon',
            default => null,
        };

        if ($collection && $file) {
            $setting->clearMediaCollection($collection);
            $setting->addMedia($file)->toMediaCollection($collection);
            $setting->update(['value' => $setting->getFirstMediaUrl($collection)]);
            self::clearCache();
        }
    }

    // Default settings seed data
    public static function getDefaultSettings(): array
    {
        return [
            // General
            ['key' => 'site_name', 'value' => 'Staff Reporting Management', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_GENERAL, 'label' => 'Site Name'],
            ['key' => 'site_description', 'value' => 'Staff reporting and management system', 'type' => self::TYPE_TEXTAREA, 'group' => self::GROUP_GENERAL, 'label' => 'Site Description'],
            ['key' => 'site_logo', 'value' => null, 'type' => self::TYPE_IMAGE, 'group' => self::GROUP_GENERAL, 'label' => 'Site Logo'],
            ['key' => 'site_favicon', 'value' => null, 'type' => self::TYPE_IMAGE, 'group' => self::GROUP_GENERAL, 'label' => 'Favicon'],

            // Appearance
            ['key' => 'primary_color', 'value' => '#3b82f6', 'type' => self::TYPE_COLOR, 'group' => self::GROUP_APPEARANCE, 'label' => 'Primary Color'],
            ['key' => 'secondary_color', 'value' => '#64748b', 'type' => self::TYPE_COLOR, 'group' => self::GROUP_APPEARANCE, 'label' => 'Secondary Color'],
            ['key' => 'custom_css', 'value' => '', 'type' => self::TYPE_TEXTAREA, 'group' => self::GROUP_APPEARANCE, 'label' => 'Custom CSS'],

            // Email
            ['key' => 'mail_from_name', 'value' => 'Staff Reporting', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'From Name'],
            ['key' => 'mail_from_address', 'value' => 'noreply@example.com', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'From Address'],
            ['key' => 'email_signature', 'value' => 'Best regards,\nThe Staff Reporting Team', 'type' => self::TYPE_TEXTAREA, 'group' => self::GROUP_EMAIL, 'label' => 'Email Signature'],
            ['key' => 'mail_mailer', 'value' => 'log', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'Mail Driver'],
            ['key' => 'smtp_host', 'value' => '', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'SMTP Host'],
            ['key' => 'smtp_port', 'value' => '587', 'type' => self::TYPE_NUMBER, 'group' => self::GROUP_EMAIL, 'label' => 'SMTP Port'],
            ['key' => 'smtp_username', 'value' => '', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'SMTP Username'],
            ['key' => 'smtp_password', 'value' => '', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'SMTP Password'],
            ['key' => 'smtp_encryption', 'value' => 'tls', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_EMAIL, 'label' => 'SMTP Encryption'],

            // Reports
            ['key' => 'max_upload_size', 'value' => '52428800', 'type' => self::TYPE_NUMBER, 'group' => self::GROUP_REPORTS, 'label' => 'Max Upload Size (bytes)'],
            ['key' => 'allowed_file_types', 'value' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mov', 'type' => self::TYPE_TEXT, 'group' => self::GROUP_REPORTS, 'label' => 'Allowed File Types'],
            ['key' => 'report_categories', 'value' => json_encode(['daily', 'weekly', 'monthly', 'quarterly', 'annual']), 'type' => self::TYPE_JSON, 'group' => self::GROUP_REPORTS, 'label' => 'Report Categories'],

            // Features
            ['key' => 'enable_proposals', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_FEATURES, 'label' => 'Enable Proposals'],
            ['key' => 'enable_kingschat_notifications', 'value' => '0', 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_FEATURES, 'label' => 'Enable KingsChat Notifications'],
            ['key' => 'enable_email_notifications', 'value' => '1', 'type' => self::TYPE_BOOLEAN, 'group' => self::GROUP_FEATURES, 'label' => 'Enable Email Notifications'],
        ];
    }
}
