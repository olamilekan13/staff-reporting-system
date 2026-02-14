<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ReportLink;
use App\Models\User;
use Illuminate\Support\Collection;

class ReportLinkService
{
    /**
     * Get all report links for a specific user.
     */
    public function getLinksForUser(User $user): Collection
    {
        return $user->reportLinks()->latest()->get();
    }

    /**
     * Create a new report link for a user.
     */
    public function createLink(User $user, array $data): ReportLink
    {
        $link = ReportLink::create([
            'user_id' => $user->id,
            'url' => $data['url'],
        ]);

        ActivityLog::log(
            ActivityLog::ACTION_CREATE,
            $link,
            null,
            ['url' => $link->url, 'user' => $user->full_name]
        );

        return $link;
    }

    /**
     * Update an existing report link.
     */
    public function updateLink(ReportLink $link, array $data): ReportLink
    {
        $oldValues = ['url' => $link->url];

        $link->update([
            'url' => $data['url'],
        ]);

        ActivityLog::log(
            ActivityLog::ACTION_UPDATE,
            $link,
            $oldValues,
            ['url' => $link->url]
        );

        return $link;
    }

    /**
     * Delete a report link.
     */
    public function deleteLink(ReportLink $link): void
    {
        $oldValues = [
            'url' => $link->url,
            'user' => $link->user->full_name ?? 'Unknown'
        ];

        ActivityLog::log(
            ActivityLog::ACTION_DELETE,
            $link,
            $oldValues,
            null
        );

        $link->delete();
    }
}
