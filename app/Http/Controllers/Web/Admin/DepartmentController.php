<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index()
    {
        $query = Department::with(['head', 'parent', 'users']);

        // Search
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if (request('is_active') !== null) {
            $query->where('is_active', request('is_active'));
        }

        // Filter by parent
        if (request('parent_id')) {
            $query->where('parent_id', request('parent_id'));
        } elseif (request('root_only')) {
            $query->root();
        }

        $departments = $query->latest()->paginate(15);
        $parentDepartments = Department::root()->active()->get();
        $users = User::active()->orderBy('first_name')->get();

        return view('admin.departments.index', compact('departments', 'parentDepartments', 'users'));
    }

    public function create()
    {
        $parentDepartments = Department::active()->get();
        $users = User::active()->orderBy('first_name')->get();

        return view('admin.departments.create', compact('parentDepartments', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Department::create($validator->validated());

        return redirect()->route('admin.departments.index')->with('success', 'Department created successfully');
    }

    public function show(Department $department)
    {
        $department->load(['head', 'parent', 'children', 'users']);

        return view('admin.departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $parentDepartments = Department::where('id', '!=', $department->id)->active()->get();
        $users = User::active()->orderBy('first_name')->get();

        return view('admin.departments.edit', compact('department', 'parentDepartments', 'users'));
    }

    public function update(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
            'parent_id' => 'nullable|exists:departments,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $department->update($validator->validated());

        return redirect()->route('admin.departments.index')->with('success', 'Department updated successfully');
    }

    public function destroy(Department $department)
    {
        // Check if department has users
        if ($department->users()->count() > 0) {
            return back()->with('error', 'Cannot delete department with active users');
        }

        // Check if department has children
        if ($department->children()->count() > 0) {
            return back()->with('error', 'Cannot delete department with sub-departments');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')->with('success', 'Department deleted successfully');
    }
}
