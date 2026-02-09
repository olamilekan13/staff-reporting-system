<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->input('q', '');

        if (strlen($search) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->where('is_active', true)
            ->whereNotNull('kingschat_id')
            ->where('kingschat_id', '!=', '')
            ->where('id', '!=', $request->user()->id)
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('kingschat_id', 'like', "%{$search}%");
            })
            ->with('department:id,name')
            ->select('id', 'first_name', 'last_name', 'kingschat_id', 'department_id')
            ->limit(20)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'kingschat_id' => $user->kingschat_id,
                'department' => $user->department?->name,
                'profile_photo_url' => $user->getFirstMediaUrl('profile_photo'),
            ]);

        return response()->json(['users' => $users]);
    }
}
