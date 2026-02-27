<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class LiveStreamService
{
    public function getStreamInfo(): array
    {
        return Cache::remember('livestream_status', 30, function () {
            return [
                'is_live'      => (bool) SiteSetting::get('livestream_enabled', false),
                'viewer_count' => 0,
                'stream_title' => SiteSetting::get('livestream_title', 'Live Stream'),
                'mode'         => SiteSetting::get('livestream_mode', 'embed'),
                'm3u8_url'     => SiteSetting::get('livestream_m3u8_url', ''),
                'embed_code'   => SiteSetting::get('livestream_embed_code', ''),
            ];
        });
    }

    public function isLive(): bool
    {
        return $this->getStreamInfo()['is_live'];
    }

    public function clearCache(): void
    {
        Cache::forget('livestream_status');
    }
}
