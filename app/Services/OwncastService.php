<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class OwncastService
{
    public function getStreamInfo(): array
    {
        return Cache::remember('owncast_stream_status', 30, function () {
            try {
                $response = Http::timeout(5)->get(
                    config('services.owncast.url') . '/api/status'
                );

                $data = $response->json() ?? [];

                return [
                    'is_live'      => $data['online'] ?? false,
                    'viewer_count' => $data['viewerCount'] ?? 0,
                    'stream_title' => $data['name'] ?? 'Live Stream',
                    'started_at'   => $data['lastConnectTime'] ?? null,
                    'embed_url'    => config('services.owncast.embed_url'),
                ];
            } catch (Throwable) {
                return [
                    'is_live'      => false,
                    'viewer_count' => 0,
                    'stream_title' => 'Live Stream',
                    'started_at'   => null,
                    'embed_url'    => config('services.owncast.embed_url'),
                ];
            }
        });
    }

    public function isLive(): bool
    {
        return $this->getStreamInfo()['is_live'];
    }

    public function clearCache(): void
    {
        Cache::forget('owncast_stream_status');
    }
}
