<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $query = auth()->user()->notifications()->with('notifiable')->latestFirst();

        // Filter by read/unread
        if (request('filter') === 'unread') {
            $query->unread();
        } elseif (request('filter') === 'read') {
            $query->read();
        }

        // Filter by type
        if (request('type')) {
            $query->byType(request('type'));
        }

        $notifications = $query->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read');
    }

    public function view(Notification $notification)
    {
        // Mark as read when viewing
        $notification->markAsRead();

        // Redirect to the notification's link
        $link = $notification->getLink();

        if ($link) {
            return redirect($link);
        }

        return back();
    }

    public function markAllAsRead()
    {
        auth()->user()->notifications()->unread()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read');
    }
}
