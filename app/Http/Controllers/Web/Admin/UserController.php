<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ReportLink;
use App\Models\User;
use App\Services\ReportLinkService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected ReportLinkService $reportLinkService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->userService->getUsers([
            'department_id' => $request->query('department_id'),
            'role' => $request->query('role'),
            'is_active' => $request->query('is_active'),
            'search' => $request->query('search'),
            'per_page' => $request->query('per_page', 15),
        ]);

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $roles = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'head_of_operations' => 'Head of Operations',
            'hod' => 'Head of Department',
            'staff' => 'Staff',
        ];

        return view('admin.users.index', compact('users', 'departments', 'roles'));
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $roles = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'head_of_operations' => 'Head of Operations',
            'hod' => 'Head of Department',
            'staff' => 'Staff',
        ];

        return view('admin.users.create', compact('departments', 'roles'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', User::class);

        $validator = Validator::make($request->all(), [
            'kingschat_id' => 'required|string|max:100|unique:users,kingschat_id',
            'title' => 'required|string|in:Pastor,Deacon,Deaconess,Brother,Sister',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|in:super_admin,admin,head_of_operations,hod,staff',
            'report_links' => 'nullable|array',
            'report_links.*' => 'nullable|url|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = $this->userService->createUser($validator->validated());

        // Add report links if provided and user is super_admin
        if (auth()->user()->isSuperAdmin() && $request->has('report_links')) {
            foreach ($request->report_links as $url) {
                if (!empty($url)) {
                    $this->reportLinkService->createLink($user, ['url' => $url]);
                }
            }
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user = $this->userService->getUserWithActivitySummary($user);
        $reportLinks = $this->reportLinkService->getLinksForUser($user);

        return view('admin.users.show', compact('user', 'reportLinks'));
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        $user->load(['department', 'roles']);

        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $roles = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'head_of_operations' => 'Head of Operations',
            'hod' => 'Head of Department',
            'staff' => 'Staff',
        ];

        $reportLinks = $this->reportLinkService->getLinksForUser($user);

        return view('admin.users.edit', compact('user', 'departments', 'roles', 'reportLinks'));
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|in:Pastor,Deacon,Deaconess,Brother,Sister',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'required|in:super_admin,admin,head_of_operations,hod,staff',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->userService->updateUser($user, $validator->validated());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        if (Auth::id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $this->userService->deactivateUser($user);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deactivated successfully.');
    }

    public function importTemplate()
    {
        Gate::authorize('import', User::class);

        return $this->userService->getImportTemplate();
    }

    public function importPreview(Request $request)
    {
        Gate::authorize('import', User::class);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first('file'),
            ], 422);
        }

        $result = $this->userService->previewImport($request->file('file'));

        return response()->json($result);
    }

    public function import(Request $request)
    {
        Gate::authorize('import', User::class);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $result = $this->userService->importUsers($request->file('file'));

        session()->put('import_result', $result);

        $message = "Successfully imported {$result['success_count']} user(s).";
        if (count($result['failures']) > 0) {
            $message .= " " . count($result['failures']) . " row(s) failed.";
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    public function export(Request $request)
    {
        Gate::authorize('viewAny', User::class);

        $filters = [
            'department_id' => $request->query('department_id'),
            'role' => $request->query('role'),
            'is_active' => $request->query('is_active'),
            'search' => $request->query('search'),
        ];

        return $this->userService->exportUsers($filters);
    }

    public function toggleActivation(User $user)
    {
        Gate::authorize('activate', $user);

        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        if ($user->is_active) {
            $this->userService->deactivateUser($user);
            $message = 'User deactivated successfully.';
        } else {
            $this->userService->activateUser($user);
            $message = 'User activated successfully.';
        }

        return response()->json([
            'success' => true,
            'is_active' => $user->fresh()->is_active,
            'message' => $message,
        ]);
    }

    /**
     * Store a new report link for a user (AJAX).
     */
    public function storeReportLink(Request $request, User $user)
    {
        Gate::authorize('create', ReportLink::class);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $link = $this->reportLinkService->createLink($user, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Report link added successfully',
            'data' => [
                'id' => $link->id,
                'url' => $link->url,
                'created_at' => $link->created_at->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Update a report link (AJAX).
     */
    public function updateReportLink(Request $request, ReportLink $reportLink)
    {
        Gate::authorize('update', $reportLink);

        $validator = Validator::make($request->all(), [
            'url' => 'required|url|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $link = $this->reportLinkService->updateLink($reportLink, $validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Report link updated successfully',
            'data' => [
                'id' => $link->id,
                'url' => $link->url,
                'updated_at' => $link->updated_at->format('M d, Y'),
            ],
        ]);
    }

    /**
     * Delete a report link (AJAX).
     */
    public function destroyReportLink(ReportLink $reportLink)
    {
        Gate::authorize('delete', $reportLink);

        $this->reportLinkService->deleteLink($reportLink);

        return response()->json([
            'success' => true,
            'message' => 'Report link deleted successfully',
        ]);
    }
}
