<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
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
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = $this->userService->createUser($validator->validated());

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user = $this->userService->getUserWithActivitySummary($user);

        return view('admin.users.show', compact('user'));
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

        return view('admin.users.edit', compact('user', 'departments', 'roles'));
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
}
