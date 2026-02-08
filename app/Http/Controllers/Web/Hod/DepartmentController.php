<?php

namespace App\Http\Controllers\Web\Hod;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $department = $user->department;

        if (!$department) {
            return redirect()->route('hod.dashboard')
                ->with('error', 'You are not assigned to any department.');
        }

        $department->load(['users', 'head']);

        return view('hod.department.show', compact('department'));
    }
}
